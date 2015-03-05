<?php namespace SRLabs\tests;

use Illuminate\Database\Eloquent;
use Illuminate\Support\Collection;
use SRLabs\Parley\Models\Thread;
use SRLabs\Parley\tests\prep\User;
use SRLabs\Parley\tests\prep\Widget;
use SRLabs\Parley\Exceptions\NonParleyableMemberException;

class ThreadTests extends \Orchestra\Testbench\TestCase
{
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

//         // call migrations specific to our tests, e.g. to seed the db
//        $artisan->call('db:seed', array(
//            '--database' => 'testbench',
//            '--path'     => '../tests/migrations',
//        ));
    }

    /**
     * Define environment setup.
     *
     * @param  Illuminate\Foundation\Application    $app
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
            //'Cartalyst\Sentry\SentryServiceProvider',
            //'YourProject\YourPackage\YourPackageServiceProvider',
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
            //'Sentry'      => 'Cartalyst\Sentry\Facades\Laravel\Sentry',
            //'YourPackage' => 'YourProject\YourPackage\Facades\YourPackage',
        );
    }


    /*
     * Thread Object Tests
     */

    public function testAddMember()
    {
        $user1 = User::create(['email' => 'test1@test.com']);
        $user2 = User::create(['email' => 'test2@test.com']);

        $thread = Thread::create(['subject' => 'Test Message']);
        $thread->amongst($user1);
        $thread->addMember($user2);

        $members = $thread->members();

        $this->assertInstanceOf('SRLabs\Parley\Models\Thread', $thread);
        $this->assertEquals($thread->subject, 'Test Message');
        $this->assertEquals($members->count(), 2);
    }

    public function testAmongstMembers()
    {
        $user1 = User::create(['email' => 'test1@test.com']);
        $user2 = User::create(['email' => 'test2@test.com']);

        $thread = Thread::create(['subject' => 'Test Message']);
        $thread->amongst([$user1, $user2]);

        $members = $thread->members();

        $this->assertEquals($members->count(), 2);
    }

    /**
     * @expectedException SRLabs\Parley\Exceptions\NonParleyableMemberException
     */
    public function testWithInvalidMember()
    {
        $widget = Widget::create(['name' => 'Widget1']);

        $thread = Thread::create(['subject' => 'Test Message']);
        $thread->addMember($widget);
    }

    public function testRemoveMember()
    {
        $user1 = User::create(['email' => 'test1@test.com']);
        $user2 = User::create(['email' => 'test2@test.com']);

        $thread = Thread::create(['subject' => 'Test Message']);
        $thread->amongst([$user1, $user2]);

        $thread->removeMember($user2);

        $members = $thread->members();

        $this->assertEquals($members->count(), 1);
    }

    public function testIsMember()
    {
        $user1 = User::create(['email' => 'test1@test.com']);
        $user2 = User::create(['email' => 'test2@test.com']);

        $thread = Thread::create(['subject' => 'Test Message']);
        $thread->addMember($user1);

        $this->assertTrue($thread->isMember($user1));
        $this->assertFalse($thread->isMember($user2));
    }

    public function testReferenceObjectHandling()
    {
        $user1 = User::create(['email' => 'test1@test.com']);
        $user2 = User::create(['email' => 'test2@test.com']);
        $widget = Widget::create(['name' => 'Widget1']);

        $thread = Thread::create(['subject' => 'Test Message']);
        $thread->amongst([$user1, $user2]);

        $thread->setReferenceObject($widget);

        $this->assertInstanceOf('SRLabs\Parley\tests\prep\Widget', $thread->getReferenceObject() );

        $thread->clearReferenceObject();

        $this->assertNull($thread->getReferenceObject());

    }

    /**
     * @expectedException SRLabs\Parley\Exceptions\NonReferableObjectException
     */
    public function testNonReferableObjectException()
    {
        $thread = Thread::create(['subject' => 'Test Message']);

        $thread->setReferenceObject(new Widget);
    }

    public function testThreadClosing()
    {
        $user1 = User::create(['email' => 'test1@test.com']);
        $user2 = User::create(['email' => 'test2@test.com']);

        $thread = Thread::create(['subject' => 'Test Message']);
        $thread->amongst([$user1, $user2]);

        $thread->close($user1);

        $this->assertEquals($thread->isClosed(), true);
        $this->assertInstanceOf('SRLabs\Parley\tests\prep\User', $thread->getCloser());
    }

    public function testOpenAClosedTest()
    {
        $user1 = User::create(['email' => 'test1@test.com']);
        $user2 = User::create(['email' => 'test2@test.com']);

        $thread = Thread::create(['subject' => 'Test Message']);
        $thread->amongst([$user1, $user2]);

        $thread->close($user1);

        $thread->open();

        $this->assertEquals($thread->isClosed(), false);
        $this->assertNull($thread->getCloser());
    }

    public function testRetrieveNewestMessage()
    {
        $user1 = User::create(['email' => 'test1@test.com', 'first_name' => 'Test', 'last_name' => 'User']);
        $user2 = User::create(['email' => 'test2@test.com', 'first_name' => 'Another', 'last_name' => 'User']);

        $thread = Thread::create(['subject' => 'Test Message'])->amongst([$user1, $user2])->message([
            'body'   => "There was a problem with your order",
            'alias'  => $user1->first_name . ' ' . $user1->last_name,
            'author' => $user1
        ]);

        // Simulate a reply message
        $thread->reply([
            'body' => "Yes, I see that there is a mistake. Please cancel my order.",
            'alias' => $user2->first_name . ' ' . $user2->last_name,
            'author' => $user2
        ]);

        // This is the code we are testing
        $message = $thread->newestMessage();

        $this->assertInstanceOf('SRLabs\Parley\Models\Message', $message);
        $this->assertEquals("Yes, I see that there is a mistake. Please cancel my order.", $message->body);
    }

    public function testRetrieveOriginalMessage()
    {
        $user1 = User::create(['email' => 'test1@test.com', 'first_name' => 'Test', 'last_name' => 'User']);
        $user2 = User::create(['email' => 'test2@test.com', 'first_name' => 'Another', 'last_name' => 'User']);

        $thread = Thread::create(['subject' => 'Test Message'])->amongst([$user1, $user2])->message([
            'body'   => "There was a problem with your order",
            'alias'  => $user1->first_name . ' ' . $user1->last_name,
            'author' => $user1
        ]);

        // Simulate a reply message
        $thread->reply([
            'body' => "Oh dear - what happened?",
            'alias' => $user2->first_name . ' ' . $user2->last_name,
            'author' => $user2
        ]);

        // This si the code we are testing.    
        $message = $thread->originalMessage();

        $this->assertInstanceOf('SRLabs\Parley\Models\Message', $message);
        $this->assertEquals('There was a problem with your order', $message->body);
    }

    public function testRetrieveAllMessages()
    {
        $user1 = User::create(['email' => 'test1@test.com', 'first_name' => 'Test', 'last_name' => 'User']);
        $user2 = User::create(['email' => 'test2@test.com', 'first_name' => 'Another', 'last_name' => 'User']);

        $thread = Thread::create(['subject' => 'Test Message'])->amongst([$user1, $user2])->message([
            'body'   => "There was a problem with your order",
            'alias'  => $user1->first_name . ' ' . $user1->last_name,
            'author' => $user1
        ]);

        $thread->reply([
            'body'   => "Yes, I see that there is a mistake. Please cancel my order.",
            'alias'  => $user2->first_name . ' ' . $user2->last_name,
            'author' => $user2
        ]);

        $messages = $thread->messages();

        $this->assertEquals(2, $messages->count());
    }

    public function testMarkThreadReadAndUnread()
    {
        $user1 = User::create(['email' => 'test1@test.com']);
        $user2 = User::create(['email' => 'test2@test.com']);

        $thread = Thread::create(['subject' => 'Test Message']);
        $thread->amongst($user1);
        $thread->addMember($user2);

        $thread->reply([
            'body' => 'This is the first message in this thread',
            'author' => $user1,
            'alias' => $user1->email
        ]);

        $this->assertFalse( $thread->memberHasRead( $user1 ) );

        $thread->markReadForMembers( $user1 );

        $this->assertTrue( $thread->memberHasRead( $user1 ) );

        $thread->markUnreadForMember( $user1 );

        $this->assertFalse( $thread->memberHasRead( $user1 ) );

        $thread->markReadForMembers( $user1 );

        $this->assertTrue( $thread->memberHasRead( $user1 ) );

        $thread->reply([
            'body' => 'This is the second message in the thread.',
            'author' => $user2,
            'alias' => $user2->email
        ]);

        $this->assertFalse( $thread->memberHasRead( $user1 ) );

    }
}