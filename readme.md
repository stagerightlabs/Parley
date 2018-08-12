## Parley: Polymorphic Messaging for Laravel Applications

[![Build Status](https://travis-ci.org/SRLabs/Parley.svg?branch=master)](https://travis-ci.org/SRLabs/Parley)

With Parley you can easily send messages between different object types within a Laravel application.   These "conversations" can be bi-directional, allowing for easy communication with your users about topics relevant to your application.

* Associate threads with reference objects, such as orders or any other eloquent model instance
* Keep track of which members have or haven't "read" the messages
* Optionally mark threads as "open" or "closed"

Here is an example:

```php
Parley::discuss([
    'subject' => "A New Game has been added",
    'body'   => "A new game with {$teamB->name} has been added to the schedule.",
    'alias'  => "The Commissioner",
    'author' => $admin,
    'regarding' => $game
])->withParticipant($teamA);
```

When a player logs in, their unread Parley messages can be retrieved like so:

```php
$messages = Parley::gatherFor([$user, $user->team])->unread()->get();
```

If this user wants to reply to a message, it can be done like this:

```php
$thread = Parley::find(1);
$thread->reply([
    'body' => 'Thanks for the heads up! We will bring snacks.',
    'author' => $user
]);
```

Additional usage examples and the API can be found [here](http://stagerightlabs.com/projects/parley).


### Installation

This package can be installed using composer:

```shell
$ composer require srlabs/parley
```

Make sure you use the version most appropriate for your Laravel Installation:

| Laravel Version  | Parley Version  | Packagist Branch |
|---|---|---|
| 4.2.* | 1.* | ```"srlabs/parley": "~1"``` |
| 5.1.* | 2.0.* | ```"srlabs/parley": "~2.0"``` |
| 5.2.* | 2.1.* | ```"srlabs/parley": "~2.1"``` |
| 5.6.* | 2.2.* | ```"srlabs/parley": "~2.2"``` |


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
    'Parley'    => Parley\Facades\Parley::class,
    // ...
],
```

Next, publish and run the migrations

```shell
php artisan vendor:publish --provider="Parley\ParleyServiceProvider" --tag="migrations"
php artisan migrate
```

Any Eloquent Model that implements the ```Parley\Contracts\ParleyableInterface``` can be used to send or receive Parley messages.  To fulfill that contract, you need to have ```getParleyAliasAttribute``` and ```getParleyIdAttribute``` methods available on that model:

* ```getParleyAliasAttribute()``` - Specify the "display name" for the model participating in a Parley Conversation.  For users this could be their username, or their first and last names combined.
* ```getParleyIdAttribute()``` - Specify the integer id you want to have represent this model in the Parley database tables.  It is most likely that you will want to use the model's ```id``` attribute here, but that is not always the case.

NB: While you are required to provide an alias for each Parleyable Model, You are not required to use that alias when creating threads - you can optionally specify a different "alias" attribute when creating messages.

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
