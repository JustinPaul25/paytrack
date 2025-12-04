<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Api\Upload\UploadApi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CloudinaryService
{
    protected Cloudinary $cloudinary;
    protected UploadApi $uploadApi;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => config('filesystems.disks.cloudinary.cloud_name'),
                'api_key' => config('filesystems.disks.cloudinary.api_key'),
                'api_secret' => config('filesystems.disks.cloudinary.api_secret'),
            ],
            'url' => [
                'secure' => config('filesystems.disks.cloudinary.secure', true),
            ],
        ]);

        $this->uploadApi = $this->cloudinary->uploadApi();
    }

    /**
     * Upload a file to Cloudinary
     */
    public function uploadFile($file, string $path, array $options = []): array
    {
        $publicId = $this->getPublicId($path);
        $folder = $this->getFolder($path);

        $uploadOptions = array_merge([
            'public_id' => $publicId,
            'folder' => $folder ?: null,
            'resource_type' => 'auto',
        ], $options);

        if ($file instanceof UploadedFile) {
            $result = $this->uploadApi->upload($file->getRealPath(), $uploadOptions);
        } elseif (is_string($file) && file_exists($file)) {
            $result = $this->uploadApi->upload($file, $uploadOptions);
        } else {
            // Assume it's a file path or stream
            $result = $this->uploadApi->upload($file, $uploadOptions);
        }

        return $result;
    }

    /**
     * Delete a file from Cloudinary
     */
    public function deleteFile(string $path): bool
    {
        try {
            $publicId = $this->getPublicId($path);
            $result = $this->cloudinary->adminApi()->deleteAssets($publicId);
            return isset($result['deleted']) && count($result['deleted']) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the public URL for a file
     */
    public function getUrl(string $path, array $transformations = []): string
    {
        $publicId = $this->getPublicId($path);
        $image = $this->cloudinary->image($publicId)->secure();

        foreach ($transformations as $key => $value) {
            $image = $image->$key($value);
        }

        return $image->toUrl();
    }

    /**
     * Extract public ID from path
     */
    protected function getPublicId(string $path): string
    {
        // Remove prefix if exists
        $prefix = config('filesystems.disks.cloudinary.prefix', '');
        if ($prefix && str_starts_with($path, $prefix)) {
            $path = substr($path, strlen($prefix));
        }

        // Remove leading slash
        $path = ltrim($path, '/');

        // Remove extension for public_id
        $publicId = pathinfo($path, PATHINFO_FILENAME);

        // Include folder structure if it exists
        $folder = dirname($path);
        if ($folder !== '.' && $folder !== '/') {
            $publicId = $folder . '/' . $publicId;
        }

        return $publicId;
    }

    /**
     * Extract folder from path
     */
    protected function getFolder(string $path): ?string
    {
        $prefix = config('filesystems.disks.cloudinary.prefix', '');
        if ($prefix && str_starts_with($path, $prefix)) {
            $path = substr($path, strlen($prefix));
        }

        $path = ltrim($path, '/');
        $folder = dirname($path);

        if ($folder === '.' || $folder === '/') {
            return null;
        }

        return $folder;
    }
}

