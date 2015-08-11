<?php

namespace Parley;

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
        // Register the Parley Package
        $this->package('srlabs/parley');

        // Register the Hashids service provider, if it hasn't been registered already
        $this->app->register('Mitch\Hashids\HashidsServiceProvider');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Register the Parley Manager to the IOC
        $this->app['parley'] = $this->app->share(function ($app) {
            return new ParleyManager;
        });

        // Regiser Facade Aliases
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $aliases = $loader->getAliases();

        if (!array_key_exists('Parley', $aliases)) {
            $loader->alias('Parley', 'Parley\Facades\Parley');
        }

        if (!array_key_exists('Hashids', $aliases)) {
            $loader->alias('Hashids', 'Mitch\Hashids\Hashids');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array();
    }
}
