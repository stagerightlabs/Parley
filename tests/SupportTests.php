<?php namespace SRLabs\tests;

use Carbon\Carbon;
use Illuminate\Database\Eloquent;
use SRLabs\Parley\Models\Thread;
use SRLabs\Parley\tests\prep\Group;
use SRLabs\Parley\tests\prep\User;
use SRLabs\Parley\Support\Collection;

class SupportTests extends \Orchestra\Testbench\TestCase {
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
     * @param  \Illuminate\Foundation\Application $app
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
     * ParleySelector Tests
     */
    public function testGetMemberThreads()
    {
        $user1 = User::create(['email' => 'test1@test.com', 'first_name' => 'Test', 'last_name' => 'User']);
        $user2 = User::create(['email' => 'test2@test.com', 'first_name' => 'Another', 'last_name' => 'User']);
        $group = Group::create(['name' => 'testGroup']);

        $thread1 = \Parley::discuss('Test Thread 1')->amongst([$user1, $user2])->message([
            'body'   => "This is one Thread",
            'alias'  => $user1->first_name . ' ' . $user1->last_name,
            'author' => $user1
        ]);

        $thread2 = \Parley::discuss('Test Thread 2')->amongst($user1)->message([
            'body'   => "This is another Thread",
            'alias'  => $user1->first_name . ' ' . $user1->last_name,
            'author' => $user1
        ]);

        $thread2->close($user1);

        $thread3 = \Parley::discuss('Test Thread 3')->amongst($user1)->message([
            'body'   => "This thread will be 'deleted'",
            'alias'  => $user1->first_name . ' ' . $user1->last_name,
            'author' => $user1
        ]);

        $thread3->delete();

        $thread4 = \Parley::discuss('Test Thread 4')->amongst($group)->message([
            'body'   => "This is a fourth",
            'alias'  => $group->name,
            'author' => $group
        ]);

        $thread4->reply([
            'body'   => "Here is a response.",
            'alias'  => $group->name,
            'author' => $group
        ]);

        $thread4->markReadForMembers( $user1 );

        $user1Threads            = \Parley::gather()->belongingTo($user1)->get();
        $user1OpenThreads        = \Parley::gatherOpen()->belongingTo($user1)->get();
        $user1ClosedThreads      = \Parley::gatherClosed()->belongingTo($user1)->get();
        $user1ThreadsWithTrashed = \Parley::gather()->withTrashed()->belongingTo($user1)->get();
        $user1ThreadsOnlyTrashed = \Parley::gather()->onlyTrashed()->belongingTo($user1)->get();
        $multiGatherThreads      = \Parley::gather()->belongingTo([$user1, $group])->get();

        $this->assertEquals(2, $user1Threads->count());
        $this->assertEquals(1, $user1OpenThreads->count());
        $this->assertEquals(1, $user1ClosedThreads->count());
        $this->assertEquals(3, $user1ThreadsWithTrashed->count());
        $this->assertEquals(1, $user1ThreadsOnlyTrashed->count());
        $this->assertEquals(3, $multiGatherThreads->count());
        $this->assertInstanceOf('SRLabs\Parley\Support\Collection', $multiGatherThreads);
        $this->assertEquals(3, $multiGatherThreads->unread());
    }

    public function testGetMemberThreadCounts()
    {
        $user1 = User::create(['email' => 'test1@test.com', 'first_name' => 'Test', 'last_name' => 'User']);
        $user2 = User::create(['email' => 'test2@test.com', 'first_name' => 'Another', 'last_name' => 'User']);
        $group = Group::create(['name' => 'testGroup']);

        $thread1 = \Parley::discuss('Test Thread 1')->amongst([$user1, $user2])->message([
            'body'   => "This is one Thread",
            'alias'  => $user1->first_name . ' ' . $user1->last_name,
            'author' => $user1
        ]);

        $thread2 = \Parley::discuss('Test Thread 2')->amongst([$user1, $user2])->message([
            'body'   => "This is another Thread",
            'alias'  => $user1->first_name . ' ' . $user1->last_name,
            'author' => $user1
        ]);

        $thread2->markReadForMembers($user1);

        $thread3 = \Parley::discuss('Test Thread 3')->amongst([$user1, $user2])->message([
            'body'   => "This thread will be 'deleted'",
            'alias'  => $user1->first_name . ' ' . $user1->last_name,
            'author' => $user1
        ]);

        $thread3->delete();

        $user1All  = \Parley::gather()->belongingTo($user1)->count();
        $user1Unread = \Parley::gather()->unread()->belongingTo($user1)->count();
        $user1Read = \Parley::gather()->read()->belongingTo($user1)->count();

        $user2Unread = \Parley::gather()->unread()->belongingTo($user2)->count();
        $user2Read = \Parley::gather()->read()->belongingTo($user2)->count();

        $this->assertEquals(2, $user1All);
        $this->assertEquals(1, $user1Unread);
        $this->assertEquals(1, $user1Read);
        $this->assertEquals(2, $user2Unread);
        $this->assertEquals(0, $user2Read);
    }


    public function testGatherThreadsForInvalidMemberObject()
    {
        $group = null;

        $threads = \Parley::gather()->belongingTo($group)->get();

        $this->assertInstanceOf('SRLabs\Parley\Support\Collection', $threads);
        $this->assertEquals(0, $threads->count());
    }

}