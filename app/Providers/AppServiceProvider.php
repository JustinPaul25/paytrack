<?php

namespace App\Providers;

use App\Filesystem\CloudinaryAdapter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
		// Grant all abilities to users with the 'Admin' role
		Gate::before(function ($user, string $ability) {
			// Return true to grant, null to defer to normal checks
			return method_exists($user, 'hasRole') && $user->hasRole('Admin') ? true : null;
		});

        // Register Cloudinary filesystem driver
        // Only register if Cloudinary credentials are provided
        if (config('filesystems.disks.cloudinary.cloud_name') &&
            config('filesystems.disks.cloudinary.api_key') &&
            config('filesystems.disks.cloudinary.api_secret')) {
            Storage::extend('cloudinary', function ($app, $config) {
                $adapter = new CloudinaryAdapter($config);
                $filesystem = new Filesystem($adapter);
                
                return new \Illuminate\Filesystem\FilesystemAdapter(
                    $filesystem,
                    $adapter,
                    $config
                );
            });
        }
    }
}
