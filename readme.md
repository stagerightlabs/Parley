## Parley: Polymorphic Messaging for Laravel Applications

This package facilitates inter-application notifications, allowing messages to be sent between different object types.  Each notification is represented as a Thread (Parley\Models\Thread), and each Thread can have multiple Messages (Parley\Models\Message), allowing for back-and-forth communication within a given thread (this is optional.)

Any model that uses the ```ParleyableTrait``` can be used to send or receive Parley messages.

Imagine you have a Laravel application that manages softball teams. You want to be able to send notifications to team members whenever a new game has been scheduled.  In this scenario, we have three model types: ```Epiphyte\User```, ```Epiphyte\Team``` and ```Epiphyte\Game```.

To send the notification, you would do this when the new game has been created:

```php
$admin = Epiphyte\User::where('email', 'admin@admin.com')->first();
$game = Epiphyte\Game::create();
$teamA = Epiphyte\Team::find(1);
$teamB = EpiphyteTeam::find(2);

Parley::discuss("A new game with {$teamB->name} has been added", $game)
    ->amongst([$admin, $teamA])
    ->message([
        'body'   => "A new match has been added to the season!",
        'alias'  => $admin->name,
        'author' => $admin
    ]);
```

When a team member from team A logs in, you can retrieve their messages like so:

```php
$user = Auth::user();
$messages = Parley::gather()->belongingTo([$user, $user->team])->get();

```

The ```$messages``` collection contains all of the threads (Parley\Thread) associated wither with the user or with the user's team.

More usage examples can be found here.


### Installation

This package can be installed using composer:

```shell
$ composer require srlabs/parley
```

Make sure you use the version most appropriate for the type of Laravel application you are running:

| Laravel Version  | Parley Version  | Packagist Branch |
|---|---|---|
| 4.2.* | 1.* | ```"srlabs/parley": "~1"``` |
| 5.* | 2.* | ```"srlabs/parley": "~2"``` |

The rest of these instructions are for Parley 1.0 / Laravel 4.2:

Add the Service Provider to your ```app/config/app.php``` file:

```php
'providers' => array(
    ...
    'Parley\ParleyServiceProvider',
    ...
)
```

The service provider will automatically register the ```Parley``` and ```Hashids``` facade aliases, if they have not been registered already.

Next, run the Migrations:

```shell
php artisan migrate --package=srlabs/parley
```

Add the Parleyable trait to any models for which you want to enable messaging:

```php
use Parley\Traits\ParleyableTrait;

class User extends \Illuminate\Database\Eloquent\Model {
    // ..

    use ParleyableTrait;

    // ..
}
```

You are now ready to go!

## Events

Whenever a new thread is created, or a new "reply" is added to a thread, an event is fired.  The event names are dynamically generated, as such:

'parley.' . ACTION . '.for.' . MODEL_NAMESPACE . MODEL_NAME;

So when the first Parley message from the example above is created, two events are fired:

* 'parley.new.thread.for.Epiphyte.Team'
* 'parley.new.thread.for.Epiphyte.User'

When a reply is posted to that thread, two new events will be fired:

* 'parley.new.reply.for.Epiphyte.Team'
* 'parley.new.reply.for.Epiphyte.User'

Each event has three objects that are broadcast with it:

* $action (string): the type of action that occured, i.e 'new.thread'
* $thread (Parley\Models\Thread): The related thread object
* $member (Eloquent\Model): The member object being notified about the thread, either ```Epiphyte\Team``` or ```Epiphyte\User``` in the examples above.


