<?php

use Carbon\Carbon;
use Illuminate\Database\Eloquent;
use Parley\Models\Thread;
use Parley\Support\Collection;
use Epiphyte\User, Epiphyte\Group;

class SupportTests extends ParleyTestCase
{

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
        $this->assertInstanceOf('Parley\Support\Collection', $multiGatherThreads);
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

        $this->assertInstanceOf('Parley\Support\Collection', $threads);
        $this->assertEquals(0, $threads->count());
    }

}