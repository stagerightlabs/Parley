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
		$this->package('srlabs/parley');
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

        $this->app->booting(function()
        {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('Parley', 'SRLabs\Parley\Facades\Parley');
        });
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
