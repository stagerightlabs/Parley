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
        // Nothing to see here...
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Register the Vinkla/Hashids Service Provider
        $this->app->register('Vinkla\Hashids\HashidsServiceProvider');

        // Register the Parley Manager to the IOC
        $this->app['parley'] = $this->app->share(function ($app) {
            return new ParleyManager;
        });

        // Load the Parley and Hashids Facade Aliases
        $loader = AliasLoader::getInstance();
        $loader->alias('Parley', 'Parley\Facades\Parley');
        $loader->alias('Hashids', 'Vinkla\Hashids\Facades\Hashids');

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
