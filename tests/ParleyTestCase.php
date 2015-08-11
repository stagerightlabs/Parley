<?php

class ParleyTestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Setup the test environment, per the Orchestra\Testbench\TestCase documentation
     */
    public function setUp()
    {
        parent::setUp();

        // create an artisan object for calling migrations
        $artisan = $this->app->make('artisan');

        // call migrations that will be part of your package, assumes your migrations are in src/migrations
        // not neccessary if your package doesn't require any migrations to be run for
        // proper installation
        $artisan->call('migrate', [
            '--database' => 'testbench',
            '--path'     => 'migrations',
        ]);

        $artisan->call('migrate', [
            '--database' => 'testbench',
            '--path'     => '../tests/prep/migrations',
        ]);
    }

    /*
     *  Make sure we clean up after ourselves
     */
    public function tearDown()
    {
        parent::tearDown();
        Mockery::close();
    }

    /**
     * Define environment setup.
     *
     * @param  Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // reset base path to point to our package's src directory
        $app['path.base'] = __DIR__ . '/../src';

        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', array(
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ));
    }

    /**
     * Get package providers.  At a minimum this is the package being tested, but also
     * would include packages upon which our package depends, e.g. Cartalyst/Sentry
     * In a normal app environment these would be added to the 'providers' array in
     * the config/app.php file.
     *
     * @return array
     */
    protected function getPackageProviders()
    {
        return array(
            'Parley\ParleyServiceProvider',
        );
    }

    /**
     * Get package aliases.  In a normal app environment these would be added to
     * the 'aliases' array in the config/app.php file.  If your package exposes an
     * aliased facade, you should add the alias here, along with aliases for
     * facades upon which your package depends, e.g. Cartalyst/Sentry
     *
     * @return array
     */
    protected function getPackageAliases()
    {
        return array(
            'Parley' => 'Parley\Facades\Parley',
        );
    }

    /**
     * Call artisan command and return code.
     *
     * @param string $command
     * @param array $parameters
     *
     * @return int
     */
    public function artisan($command, $parameters = [])
    {
        // TODO: Implement artisan() method.
    }
}
