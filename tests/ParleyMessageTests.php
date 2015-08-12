<?php

use Illuminate\Database\Eloquent;
use Illuminate\Support\Collection;
use Parley\Models\Thread;
use Parley\Models\Message;
use Epiphyte\User;

class ParleyMessageTests extends ParleyTestCase
{

    public function testCreateMessage()
    {
        $user1 = User::create(['email' => 'test1@test.com']);
        $user2 = User::create(['email' => 'test2@test.com']);

        $thread = Thread::create(['subject' => 'Test Message']);
        $thread->amongst($user1);
        $thread->addMember($user2);

        $members = $thread->members();

        $this->assertInstanceOf('Parley\Models\Thread', $thread);
        $this->assertEquals($thread->subject, 'Test Message');
        $this->assertEquals($members->count(), 2);
    }

    public function testGetAndSetAuthor()
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

        sleep(2);

        $thread->reply([
            'body' => 'This is the second message in this thread',
            'author' => $user2,
            'alias' => $user2->email
        ]);

        $message = $thread->newestMessage();

        $author = $message->getAuthor();

        $this->assertInstanceOf('Epiphyte\User', $author);
        $this->assertEquals('test2@test.com', $author->email);

        $message->setAuthor('User 1 Alias', $user1);

        $author = $message->getAuthor();

        $this->assertEquals('test1@test.com', $author->email);
        $this->assertEquals('User 1 Alias', $message->author_alias);
    }

    public function testGetThreadFromMessage()
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

        $message = $thread->newestMessage();

        $thread = $message->thread;

        $this->assertInstanceOf('Parley\Models\Thread', $thread);
        $this->assertEquals('Test Message', $thread->subject);
    }
}
