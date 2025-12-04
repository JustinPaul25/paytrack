<?php

namespace App\Filesystem;

use Cloudinary\Cloudinary;
use Cloudinary\Api\Upload\UploadApi;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToRetrieveMetadata;

class CloudinaryAdapter implements \League\Flysystem\FilesystemAdapter
{
    protected Cloudinary $cloudinary;
    protected UploadApi $uploadApi;
    protected PathPrefixer $prefixer;
    protected string $prefix;

    public function __construct(array $config)
    {
        // Validate required config
        if (empty($config['cloud_name']) || empty($config['api_key']) || empty($config['api_secret'])) {
            throw new \InvalidArgumentException('Cloudinary credentials (cloud_name, api_key, api_secret) are required');
        }

        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => $config['cloud_name'],
                'api_key' => $config['api_key'],
                'api_secret' => $config['api_secret'],
            ],
            'url' => [
                'secure' => $config['secure'] ?? true,
            ],
        ]);

        $this->uploadApi = $this->cloudinary->uploadApi();
        $this->prefix = $config['prefix'] ?? '';
        $this->prefixer = new PathPrefixer($this->prefix);
    }

    public function fileExists(string $path): bool
    {
        try {
            $prefixedPath = $this->prefixer->prefixPath($path);
            $publicId = $this->getPublicId($prefixedPath);
            $this->cloudinary->adminApi()->asset($publicId);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function directoryExists(string $path): bool
    {
        // Cloudinary doesn't have directories in the traditional sense
        return true;
    }

    public function has(string $path): bool
    {
        return $this->fileExists($path);
    }

    public function read(string $path): string
    {
        try {
            $prefixedPath = $this->prefixer->prefixPath($path);
            $publicId = $this->getPublicId($prefixedPath);
            $asset = $this->cloudinary->adminApi()->asset($publicId);
            return file_get_contents($asset['secure_url']);
        } catch (\Exception $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage());
        }
    }

    public function readStream(string $path)
    {
        $content = $this->read($path);
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);
        return $stream;
    }

    public function listContents(string $path, bool $deep): iterable
    {
        // Cloudinary doesn't support listing in the traditional sense
        // This would require using the Admin API search
        return [];
    }

    public function getMetadata(string $path): array
    {
        try {
            $prefixedPath = $this->prefixer->prefixPath($path);
            $publicId = $this->getPublicId($prefixedPath);
            $asset = $this->cloudinary->adminApi()->asset($publicId);
            
            return [
                'type' => 'file',
                'path' => $path,
                'timestamp' => strtotime($asset['created_at'] ?? 'now'),
                'size' => $asset['bytes'] ?? 0,
                'mimetype' => $asset['resource_type'] === 'image' ? 'image/' . $asset['format'] : 'application/octet-stream',
            ];
        } catch (\Exception $e) {
            throw UnableToRetrieveMetadata::create($path, 'metadata', $e->getMessage());
        }
    }

    public function getSize(string $path): array
    {
        $metadata = $this->getMetadata($path);
        return ['path' => $path, 'size' => $metadata['size']];
    }

    public function getMimetype(string $path): array
    {
        $metadata = $this->getMetadata($path);
        return ['path' => $path, 'mimetype' => $metadata['mimetype']];
    }

    public function getTimestamp(string $path): array
    {
        $metadata = $this->getMetadata($path);
        return ['path' => $path, 'timestamp' => $metadata['timestamp']];
    }

    public function getVisibility(string $path): array
    {
        // Cloudinary files are public by default
        return ['path' => $path, 'visibility' => 'public'];
    }

    public function setVisibility(string $path, string $visibility): void
    {
        // Cloudinary visibility is managed through signed URLs or access control
        // This is a no-op for now
    }

    public function visibility(string $path): FileAttributes
    {
        // Cloudinary files are public by default
        return new FileAttributes($path, null, null, null, 'public');
    }

    public function mimeType(string $path): FileAttributes
    {
        $metadata = $this->getMetadata($path);
        return new FileAttributes($path, null, null, $metadata['mimetype']);
    }

    public function lastModified(string $path): FileAttributes
    {
        $metadata = $this->getMetadata($path);
        return new FileAttributes($path, null, $metadata['timestamp']);
    }

    public function fileSize(string $path): FileAttributes
    {
        $metadata = $this->getMetadata($path);
        return new FileAttributes($path, $metadata['size']);
    }

    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $prefixedPath = $this->prefixer->prefixPath($path);
            $extension = pathinfo($prefixedPath, PATHINFO_EXTENSION);
            
            // Create temporary file with proper extension so Cloudinary can detect file type
            $tempDir = sys_get_temp_dir();
            $tempFile = tempnam($tempDir, 'cloudinary_');
            if ($extension) {
                $tempPath = $tempFile . '.' . $extension;
                rename($tempFile, $tempPath);
            } else {
                $tempPath = $tempFile;
            }
            
            file_put_contents($tempPath, $contents);
            
            // Get public_id: remove extension and leading slash, use full path structure
            $pathWithoutExt = $extension ? substr($prefixedPath, 0, -(strlen($extension) + 1)) : $prefixedPath;
            $publicId = ltrim($pathWithoutExt, '/');
            
            $uploadOptions = [
                'public_id' => $publicId,
                'resource_type' => $config->get('resource_type', 'auto'),
            ];
            
            // Don't set folder separately - the public_id already includes the full path
            $this->uploadApi->upload($tempPath, $uploadOptions);
            
            // Clean up temporary file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        } catch (\Exception $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage());
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $content = stream_get_contents($contents);
        $this->write($path, $content, $config);
    }

    public function update(string $path, string $contents, Config $config): void
    {
        $this->write($path, $contents, $config);
    }

    public function updateStream(string $path, $contents, Config $config): void
    {
        $this->writeStream($path, $contents, $config);
    }

    public function delete(string $path): void
    {
        try {
            $prefixedPath = $this->prefixer->prefixPath($path);
            $publicId = $this->getPublicId($prefixedPath);
            $this->cloudinary->adminApi()->deleteAssets($publicId);
        } catch (\Exception $e) {
            throw UnableToDeleteFile::atLocation($path, $e->getMessage());
        }
    }

    public function deleteDirectory(string $path): void
    {
        // Cloudinary doesn't have directories, but we can delete by folder prefix
        try {
            $prefixedPath = $this->prefixer->prefixPath($path);
            $this->cloudinary->adminApi()->deleteAssets([
                'prefix' => $prefixedPath,
            ]);
        } catch (\Exception $e) {
            // Silently fail if folder doesn't exist
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        // Cloudinary creates folders automatically when uploading
    }

    public function move(string $source, string $destination, Config $config): void
    {
        // Read from source and write to destination, then delete source
        $contents = $this->read($source);
        $this->write($destination, $contents, $config);
        $this->delete($source);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $contents = $this->read($source);
        $this->write($destination, $contents, $config);
    }

    public function getUrl(string $path): string
    {
        $prefixedPath = $this->prefixer->prefixPath($path);
        $publicId = $this->getPublicId($prefixedPath);
        return $this->cloudinary->image($publicId)->secure()->toUrl();
    }

    public function publicUrl(string $path): string
    {
        return $this->getUrl($path);
    }

    public function temporaryUrl(string $path, \DateTimeInterface $expiration, array $options = []): string
    {
        $prefixedPath = $this->prefixer->prefixPath($path);
        $publicId = $this->getPublicId($prefixedPath);
        return $this->cloudinary->image($publicId)->secure()->signUrl()->toUrl();
    }

    protected function getPublicId(string $path): string
    {
        // Remove leading slash
        $path = ltrim($path, '/');
        
        // Remove the extension for Cloudinary public_id
        // Cloudinary stores files without extensions in the public_id
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $pathWithoutExt = $extension ? substr($path, 0, -(strlen($extension) + 1)) : $path;
        
        // Return the full path without extension as the public_id
        // This matches how we upload: folder structure + filename without extension
        return $pathWithoutExt;
    }
}

