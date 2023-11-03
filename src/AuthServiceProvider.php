<?php

namespace Cierra\Auth;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Cierra\Auth\Commands\CierraAuthCommand;

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
            ->hasCommand(CierraAuthCommand::class);
    }


    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
