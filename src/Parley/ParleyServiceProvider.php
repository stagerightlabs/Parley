<?php namespace SRLabs\Parley;

use Illuminate\Support\ServiceProvider;

class ParleyServiceProvider extends ServiceProvider {

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

        // Register the Hashids service provider
        $this->app->register('Mitch\Hashids\HashidsServiceProvider');

	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        // Register 'Parley Manager' instance container to our ParleyManager class
        $this->app['parley'] = $this->app->share(function($app)
        {
            return new ParleyManager;
        });

        // Regiser Aliases
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('Parley', 'SRLabs\Parley\Facades\Parley');
        $loader->alias('Hashids', 'Mitch\Hashids\Hashids');

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
