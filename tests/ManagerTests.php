<?php namespace SRLabs\tests;

use Carbon\Carbon;
use Illuminate\Database\Eloquent;
use Illuminate\Database\Eloquent\Collection;
use SRLabs\Parley\Models\Thread;
use SRLabs\Parley\tests\prep\Group;
use SRLabs\Parley\tests\prep\User;
use SRLabs\Parley\tests\prep\Widget;

class ManagerTests extends \Orchestra\Testbench\TestCase {
    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        parent::setUp();

        // uncomment to enable route filters if your package defines routes with filters
        // $this->app['router']->enableFilters();

        // create an artisan object for calling migrations
        $artisan = $this->app->make('artisan');

        // call migrations for packages upon which our package depends, e.g. Cartalyst/Sentry
        // not necessary if your package doesn't depend on another package that requires
        // running migrations for proper installation
        /* uncomment as necessary
        $artisan->call('migrate', [
            '--database' => 'testbench',
            '--path'     => '../vendor/cartalyst/sentry/src/migrations',
        ]);
        */

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

        // call migrations specific to our tests, e.g. to seed the db
//        $artisan->call('db:seed', array(
//            '--database' => 'testbench',
//            '--path'     => '../tests/migrations',
//        ));
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
            'SRLabs\Parley\ParleyServiceProvider',
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
            'Parley' => 'SRLabs\Parley\ParleyManager',
        );
    }


    /*
     * ParleyManager Tests
     */
    public function testParleyConversation()
    {
        $user1 = User::create(['email' => 'test1@test.com', 'first_name' => 'Test', 'last_name' => 'User']);
        $user2 = User::create(['email' => 'test2@test.com', 'first_name' => 'Another', 'last_name' => 'User']);

        $thread = \Parley::discuss('This is an important message')->amongst([$user1, $user2])->message([
            'body'   => "There was a problem with your order",
            'alias'  => $user1->first_name . ' ' . $user1->last_name,
            'author' => $user1
        ]);

        sleep(5);

        $thread->reply([
            'body'   => "Yes, I see that there is a mistake. Please cancel my order.",
            'alias'  => $user2->first_name . ' ' . $user2->last_name,
            'author' => $user2
        ]);

        $members = $thread->members();

        $this->assertInstanceOf('SRLabs\Parley\Models\Thread', $thread);
        $this->assertInstanceOf('Illuminate\Support\Collection', $members);
        $this->assertEquals(2, $members->count());
        $this->assertEquals($thread->subject, 'This is an important message');

        $message = $thread->newestMessage();

        $this->assertInstanceOf('SRLabs\Parley\Models\Message', $message);
        $this->assertEquals('Yes, I see that there is a mistake. Please cancel my order.', $message->body);
        $this->assertEquals(2, $thread->messages()->count());
    }


    public function testParleyDiscussWithReferenceObject()
    {
        $widget = Widget::create(['name' => 'Widget1']);

        $thread = \Parley::discuss('This is a Parley', $widget);

        $this->assertInstanceOf('SRLabs\Parley\Models\Thread', $thread);
        $this->assertInstanceOf('SRLabs\Parley\tests\prep\Widget', $thread->getReferenceObject());
        $this->assertEquals($thread->subject, 'This is a Parley');
    }

}