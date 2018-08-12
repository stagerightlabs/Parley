<?php

namespace Parley;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class ParleyServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        // Set up Migration Publishing
        $this->publishes([
            __DIR__.'/../migrations/' => database_path('migrations')
        ], 'migrations');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Register the Parley Manager to the IOC Container
        $this->app->singleton('parley',function ($app) {
            return new ParleyManager;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['parley'];
    }
}
