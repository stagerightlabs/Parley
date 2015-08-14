<?php

use Epiphyte\Group;
use Epiphyte\User;

class ParleyTestCase extends \Orchestra\Testbench\TestCase
{
    // Entity "Mocks"
    protected $nikolai;
    protected $irina;
    protected $prozorovGroup;

    /**
     * Setup the test environment, per the Orchestra\Testbench\TestCase documentation
     */
    public function setUp()
    {
        parent::setUp();

        // Prepare the sqlite database
        // http://www.chrisduell.com/blog/development/speeding-up-unit-tests-in-php/
        exec('cp ' . __DIR__ . '/_data/db/staging.sqlite ' . __DIR__ . '/_data/db/database.sqlite');

        // Establish the players in our dialogue
        // https://en.wikipedia.org/wiki/Three_Sisters_(play)
        $this->irina = User::create(['email' => 'irina@prozorov.net', 'first_name' => 'Irina', 'last_name' => 'Prozorovna']);
        $this->nikolai = User::create(['email' => 'nikolai@tuzenbach.com', 'first_name' => 'Nikolai', 'last_name' => 'Tuzenbach']);
        $this->prozorovGroup = Group::create(['name' => 'The Prozorovs']);
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
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => __DIR__ . '/_data/db/database.sqlite',
            'prefix'   => '',
        ]);
    }

    /**
     * Get package providers.  At a minimum this is the package being tested, but also
     * would include packages upon which our package depends, e.g. Cartalyst/Sentry
     * In a normal app environment these would be added to the 'providers' array in
     * the config/app.php file.
     *
     * @return array
     */
    protected function getPackageProviders($app)
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
    protected function getPackageAliases($app)
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

    /**
     * A helper method for quickly stubbing out parley conversations
     *
     * @param $subject
     * @return mixed
     */
    protected function simulate_a_conversation($subject = 'Happy Name Day!')
    {
        $parley = Parley::discuss([
            'subject'  => $subject,
            'body'   => 'Congratulations on your 20th name day!',
            'alias'  => $this->nikolai->alias,
            'author' => $this->nikolai
        ])->withParticipants($this->irina);

        sleep(1);

        $parley->reply([
            'body'   => 'I am feeling so very old today.',
            'author' => $this->irina
        ]);

        return $parley;
    }
}
