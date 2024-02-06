<?php

namespace Bkremenovic\EloquentTokens;

use Bkremenovic\EloquentTokens\Facades\Token;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class EloquentTokensServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Check if the configuration is not cached
        if (!app()->configurationIsCached()) {
            // Merge the configuration from the eloquent-tokens.php file
            $this->mergeConfigFrom(__DIR__.'/../config/eloquent-tokens.php', 'eloquent-tokens');
        }

        // Register the TokenConfigManager singleton instance
        $this->app->singleton(TokenConfigManager::class, function () {
            // Get the values from the config file
            $useAllDrivers = config('eloquent-tokens.use_all_drivers', true);
            $supportedDrivers = config('eloquent-tokens.drivers.supported', []);
            $defaultDriver = config('eloquent-tokens.drivers.default');
            $tokenBlacklistTable = config('eloquent-tokens.blacklist_table');
            $tokensTable = config('eloquent-tokens.tokens_table');

            // Initialize the config manager
            return new TokenConfigManager(
                $supportedDrivers,
                $defaultDriver,
                $useAllDrivers,
                $tokenBlacklistTable,
                $tokensTable,
            );
        });

        // Bind the TokenService class to the container
        $this->app->bind(TokenService::class, function ($app) {
            return new TokenService($app->make(TokenConfigManager::class));
        });

        // Create an alias for the TokenService class
        $this->app->alias(TokenService::class, Token::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function boot()
    {
        // Check if the application is running in console
        if (app()->runningInConsole()) {
            // Publish the migrations to the 'migrations' directory
            $this->publishes([
                __DIR__.'/../database/migrations/create_token_blacklists_table.php.stub' => $this->getMigrationFileName('create_token_blacklists_table.php'),
            ], 'migrations');
            $this->publishes([
                __DIR__.'/../database/migrations/create_tokens_table.php.stub' => $this->getMigrationFileName('create_tokens_table.php'),
            ], 'migrations');

            // Publish the config file to the 'config' directory
            $this->publishes([
                __DIR__.'/../config/eloquent-tokens.php' => config_path('eloquent-tokens.php'),
            ], 'config');
        }
    }

    /**
     * Returns existing migration file if found, else uses a new one with the current timestamp.
     *
     * Thanks @drbyte and everyone who contributed to the development of this method.
     *
     * @param string $migrationFileName The base name of the migration file to search for or generate.
     *
     * @return string The full path and filename of the existing or newly generated migration file.
     *
     * @throws BindingResolutionException
     */
    protected function getMigrationFileName(string $migrationFileName): string
    {
        $timestamp = date('Y_m_d_His');

        $filesystem = $this->app->make(Filesystem::class);

        return Collection::make([$this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR])
            ->flatMap(fn ($path) => $filesystem->glob($path.'*_'.$migrationFileName))
            ->push($this->app->databasePath()."/migrations/{$timestamp}_$migrationFileName")
            ->first();
    }
}
