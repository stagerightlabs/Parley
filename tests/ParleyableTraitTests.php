<?php

use Illuminate\Database\Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Parley\Models\Thread;
use Epiphyte\User, Epiphyte\Group;

class ParleyableTraitTests extends ParleyTestCase
{

    public function testNotify()
    {
        \Event::shouldReceive('fire')->twice()
            ->with('parley.new.thread.for.Epiphyte.User',
                \Mockery::any()
            );

        \Event::shouldReceive('fire')->once()
            ->with('parley.new.thread.for.Epiphyte.Group',
                \Mockery::any()
            );

        $user1 = User::create(['email' => 'test1@test.com', 'first_name' => 'Test', 'last_name' => 'User']);
        $user2 = User::create(['email' => 'test2@test.com', 'first_name' => 'Another', 'last_name' => 'User']);
        $group = Group::create(['name' => 'admin']);

        $thread = \Parley::discuss('This is an important message')->amongst([$user1, $user2, $group])->message([
            'body'   => "There was a problem with your order",
            'alias'  => $user1->first_name . ' ' . $user1->last_name,
            'author' => $user1
        ]);

        sleep(2);

        $thread->reply([
            'body'   => "Yes, I see that there is a mistake. Please cancel my order.",
            'alias'  => $user2->first_name . ' ' . $user2->last_name,
            'author' => $user2
        ]);

    }
}