<?php

namespace Cierra\Auth;

use Cierra\Auth\Commands\CierraAuthCommand;
use Cierra\Auth\Commands\GenerateOAuthClientCommand;
use Cierra\Auth\Contracts\TeamResolver;
use Cierra\Auth\Middleware\EnforceLicense;
use Cierra\Auth\Services\ContextService;
use Cierra\Auth\Services\JetstreamTeamResolver;
use Illuminate\Routing\Router;
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
        $this->registerMiddleware();

        // Register facade accessor binding
        $this->app->singleton('cierra-auth.license', ContextService::class);
        $this->app->singleton(ContextService::class, ContextService::class);

        // Default team resolver: legacy Jetstream-style behavior.
        // Host apps with a non-Jetstream team model can rebind this
        // contract (e.g. to NullTeamResolver) in their own service provider.
        $this->app->bindIf(TeamResolver::class, JetstreamTeamResolver::class);
    }

    protected function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('license', EnforceLicense::class);
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
