# Cloudinary Setup Guide

This project is configured to use Cloudinary for media storage with Spatie Media Library.

## Installation

The Cloudinary PHP package has already been installed. You need to configure your Cloudinary credentials.

## Configuration

### 1. Get Cloudinary Credentials

1. Sign up for a free account at [Cloudinary](https://cloudinary.com/)
2. Go to your Dashboard
3. Copy your Cloud Name, API Key, and API Secret

### 2. Add Environment Variables

Add the following to your `.env` file:

```env
# Cloudinary Configuration
CLOUDINARY_CLOUD_NAME=your_cloud_name
CLOUDINARY_API_KEY=your_api_key
CLOUDINARY_API_SECRET=your_api_secret
CLOUDINARY_SECURE=true
CLOUDINARY_PREFIX=

# Media Library Configuration
MEDIA_DISK=cloudinary
```

### 3. Update Media Library Disk

The media library is configured to use Cloudinary when `MEDIA_DISK=cloudinary` is set in your `.env` file.

## Usage

Once configured, Spatie Media Library will automatically use Cloudinary for all media uploads. No changes are needed to your existing code.

### Example Usage

```php
// Upload a file (works the same as before)
$product->addMediaFromRequest('image')->toMediaCollection('images');

// Get the URL (automatically uses Cloudinary)
$url = $product->getFirstMediaUrl('images');

// Get URL with conversion
$thumbUrl = $product->getFirstMediaUrl('images', 'thumb');
```

## Features

- **Automatic Upload**: Files are automatically uploaded to Cloudinary when added via Media Library
- **URL Generation**: URLs are automatically generated using Cloudinary's CDN
- **Image Transformations**: Cloudinary's image transformations work with media conversions
- **Responsive Images**: Supports responsive images through Cloudinary

## File Structure

The following files have been created/modified:

- `app/Filesystem/CloudinaryAdapter.php` - Custom filesystem adapter for Cloudinary
- `app/MediaLibrary/CloudinaryUrlGenerator.php` - Custom URL generator for Media Library
- `app/Services/CloudinaryService.php` - Service class for Cloudinary operations
- `app/Providers/AppServiceProvider.php` - Registers Cloudinary filesystem driver
- `config/filesystems.php` - Added Cloudinary disk configuration
- `config/media-library.php` - Updated to support custom URL generator

## Switching Back to Local Storage

To switch back to local storage, simply change in your `.env`:

```env
MEDIA_DISK=public
```

## Troubleshooting

### Files not uploading

- Verify your Cloudinary credentials are correct
- Check that `MEDIA_DISK=cloudinary` is set in `.env`
- Clear config cache: `php artisan config:clear`

### URLs not generating correctly

- Ensure the Cloudinary URL generator is being used
- Check that the media disk is set to `cloudinary`
- Verify file paths match Cloudinary's public_id format

### Security Advisory Warning

If you see a security advisory warning during composer install, it's related to Symfony dependencies and has been temporarily ignored in `composer.json`. This should be addressed by updating Laravel/Symfony packages when possible.

