## Parley: Polymorphic Messaging for Laravel Applications

This package enables messages to be sent between different object types within a Laravel application.  Any model that implements the ```Parley\Contracts\ParleyableInterface``` can be used to send or receive Parley messages.  Each message is represented as a Thread (```Parley\Models\Thread```), and each Thread can have multiple Messages (```Parley\Models\Message```), allowing for back-and-forth communication within a given thread (if you want.)

Imagine you have a Laravel application that manages softball teams. You want to be able to send notifications to team members whenever a new game has been scheduled.  In this scenario, we could have three model types: ```Epiphyte\User```: $admin, $user, ```Epiphyte\Team```: $teamA, $teamB and ```Epiphyte\Game```: $game.

To send the notification you would do this when the new game has been created:

```php
Parley::discuss([
    'subject' => "A New Game has been added",
    'body'   => "A new game with {$teamB->name} has been added to the schedule.",
    'alias'  => "The Commissioner",
    'author' => $admin,
    'regarding' => $game
])->withParticipant($teamA);
```

or, if you want to send a notification to both teams:

```php
Parley::discuss([
    'subject' => "Expect Rain Delays on Saturday",
    'body'   => "The forecast for saturday is not looking good - be prepared for delays",
    'author' => $admin,
    'regarding' => $game
])->withParticipants([$teamA, $teamB]);
```

or, you can even send messages to each individual user on both teams: 

```php
Parley::discuss([
    'subject' => "Updated Parking Regulations",
    'body'   => "Given the incident on Saturday, we have decided to update league parking rules.",
    'author' => $admin,
    'regarding' => $game
])->withParticipants([$teamA->players, $teamB->players]);
```

When a player from team A logs in, their unread messages can be retrieved like so:

```php
$messages = Parley::gatherFor([$user, $user->team])->unread()->get();
```

In this example, the ```$messages``` collection contains all of the thread associated wither with the user or with the user's team.  

If this user wants to reply to a message, it can be done like this:

```php
$thread = Parley::find(1);
$thread->reply([
    'body' => 'I think it is worth noting that the cause of the problem was mostly due to players from Team B.',
    'author' => $user
]);
```

Check out the [API Documentation](https://github.com/SRLabs/Parley/wiki/API-2.0) for more usage details.


### Installation

This package can be installed using composer:

```shell
$ composer require srlabs/parley
```

Make sure you use the version most appropriate for your Laravel Installation:

| Laravel Version  | Parley Version  | Packagist Branch |
|---|---|---|
| 4.2.* | 1.* | ```"srlabs/parley": "~1"``` |
| 5.* | 2.* | ```"srlabs/parley": "~2"``` |

The rest of these instructions are for Parley 2.0 / Laravel 5.*:

Add the Service Provider and Alias to your ```config/app.php``` file:

```php
'providers' => array(
    // ...
    Parley\ParleyServiceProvider::class,
    // ...
)
```

```php
'aliases' => [
    // ...
    'Parley'       => Parley\Facades\Parley::class,
    // ...
],
```

Next, publish and run the migrations

```shell
php artisan vendor:publish --provider="Parley\ParleyServiceProvider" --tag="migrations"
php artisan migrate
```

Add the ```Parley\Contracts\ParleyableInterface``` interface to each of the models you want to send or receive messages, and implement the 

```php
use Parley\Contracts\ParleyableInteraface;

class User extends \Illuminate\Database\Eloquent\Model implements ParleyableInterface {
    
    // ..

    /**
      * Each Parleyable object must implement an 'alias' accessor which is used 
     * as a display name associated with messages sent by this model.
     *
     * @return mixed
     */
    public function getParleyAliasAttribute() {
        return "{$this->attributes['first_name']} {$this->attributes['last_name']}"; 
    }

    /**
     * Each Parleyable object must provide an integer id value.  Usually this is can be
     * as simple as "return $this->attributes['id'];".
     *
     * @return int
     */
    public function getParleyIdAttribute(){
        return $this->attributes['id'];
    }
}
```

NB: You can manually specify an "alias" attribute for each thread and message if you don't want to use the alias 

You are now ready to go!

### Events

Whenever a new thread is created, or a new reply message is added, an event is fired.  You can set up your listeners in your EventServiceProvider like so: 

```php
protected $listen = [
    'Parley\Events\ParleyThreadCreated' => [
        'App\Listeners\FirstEventListener',
        'App\Listeners\SecondEventListener'
    ],
    'Parley\Events\ParleyMessageAdded' => [
        'App\Listeners\ThirdEventListener',
        'App\Listeners\FourthEventListener'
    ],
]
```

Each event is passed the Thread object and the author of the current message.  You can retrieve these objects using the ```getThread()``` and ```getAuthor``` methods: 

```php
class AppEventListener 
{
    
    /**
     * Handle the event.
     *
     * @param  SiteEvent  $event
     * @return void
     */
    public function handle(ParleyEvent $event)
    {
        // Fetch the thread
        $thread = $event->getThread();

        // Fetch the author
        $author = $event->getAuthor(); 

        // ...
    }
}
```

