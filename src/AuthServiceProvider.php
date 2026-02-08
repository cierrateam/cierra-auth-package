<?php

namespace Cierra\Auth;

use Cierra\Auth\Commands\CierraAuthCommand;
use Cierra\Auth\Commands\GenerateOAuthClientCommand;
use Illuminate\Support\Facades\File;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AuthServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('cierra-auth-package')
            ->hasConfigFile()
            ->hasViews()
            // ->hasMigration('create_skeleton_table')
            ->hasCommand(CierraAuthCommand::class)
            ->hasCommand(GenerateOAuthClientCommand::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadOAuthCredentialsFromStorageFile();
    }

    /**
     * When the enrollment secret is set, load OAuth credentials from the
     * storage file instead of from the environment configuration.
     */
    protected function loadOAuthCredentialsFromStorageFile(): void
    {
        if (! config('cierra-auth-package.client_enrollment_secret')) {
            return;
        }

        $storagePath = storage_path('cierra-auth-oauth.json');

        if (! File::exists($storagePath)) {
            return;
        }

        $data = json_decode(File::get($storagePath), true);

        if (! is_array($data)) {
            return;
        }

        if (isset($data['client_id'])) {
            config(['cierra-auth-package.client_id' => $data['client_id']]);
        }

        if (isset($data['client_secret'])) {
            config(['cierra-auth-package.client_secret' => $data['client_secret']]);
        }
    }
}
