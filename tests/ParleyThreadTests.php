<?php

use Illuminate\Database\Eloquent;
use Illuminate\Support\Collection;
use Parley\Models\Thread;
use Parley\Exceptions\NonParleyableMemberException;
use Chekhov\User;
use Chekhov\Widget;

class ParleyThreadTests extends ParleyTestCase
{



    public function testAddMember()
    {
        $user1 = User::create(['email' => 'test1@test.com']);
        $user2 = User::create(['email' => 'test2@test.com']);

        $thread = Thread::create(['subject' => 'Test Message']);
        $thread->withParticipants($user1);
        $thread->addMember($user2);

        $members = $thread->members();

        $this->assertInstanceOf('Parley\Models\Thread', $thread);
        $this->assertEquals($thread->subject, 'Test Message');
        $this->assertEquals($members->count(), 2);
    }

    public function testAmongstMembers()
    {
        $user1 = User::create(['email' => 'test1@test.com']);
        $user2 = User::create(['email' => 'test2@test.com']);

        $thread = Thread::create(['subject' => 'Test Message']);
        $thread->withParticipants([$user1, $user2]);

        $members = $thread->members();

        $this->assertEquals($members->count(), 2);
    }

    /**
     * @expectedException Parley\Exceptions\NonParleyableMemberException
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
        $thread->withParticipants([$user1, $user2]);

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
        $thread->withParticipants([$user1, $user2]);

        $thread->setReferenceObject($widget);

        $this->assertInstanceOf('Checkhov\Widget', $thread->getReferenceObject());

        $thread->clearReferenceObject();

        $this->assertNull($thread->getReferenceObject());
    }

    /**
     * @expectedException Parley\Exceptions\NonReferableObjectException
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
        $thread->withParticipants([$user1, $user2]);

        $thread->close($user1);

        $this->assertEquals($thread->isClosed(), true);
        $this->assertInstanceOf('Chekhov\User', $thread->getCloser());
    }

    public function testOpenAClosedTest()
    {
        $user1 = User::create(['email' => 'test1@test.com']);
        $user2 = User::create(['email' => 'test2@test.com']);

        $thread = Thread::create(['subject' => 'Test Message']);
        $thread->withParticipants([$user1, $user2]);

        $thread->close($user1);

        $thread->open();

        $this->assertEquals($thread->isClosed(), false);
        $this->assertNull($thread->getCloser());
    }

    public function testRetrieveNewestMessage()
    {
        $user1 = User::create(['email' => 'test1@test.com', 'first_name' => 'Test', 'last_name' => 'User']);
        $user2 = User::create(['email' => 'test2@test.com', 'first_name' => 'Another', 'last_name' => 'User']);

        $thread = Thread::create(['subject' => 'Test Message'])->withParticipants([$user1, $user2])->message([
            'body'   => "There was a problem with your order",
            'alias'  => $user1->first_name . ' ' . $user1->last_name,
            'author' => $user1
        ]);

        sleep(3);

        // Simulate a reply message
        $thread->reply([
            'body' => "Yes, I see that there is a mistake. Please cancel my order.",
            'alias' => $user2->first_name . ' ' . $user2->last_name,
            'author' => $user2
        ]);

        // This is the code we are testing
        $message = $thread->newestMessage();

        $this->assertInstanceOf('Parley\Models\Message', $message);
        $this->assertEquals("Yes, I see that there is a mistake. Please cancel my order.", $message->body);
    }

    public function testRetrieveOriginalMessage()
    {
        $user1 = User::create(['email' => 'test1@test.com', 'first_name' => 'Test', 'last_name' => 'User']);
        $user2 = User::create(['email' => 'test2@test.com', 'first_name' => 'Another', 'last_name' => 'User']);

        $thread = Thread::create(['subject' => 'Test Message'])->withParticipants([$user1, $user2])->message([
            'body'   => "There was a problem with your order",
            'alias'  => $user1->first_name . ' ' . $user1->last_name,
            'author' => $user1
        ]);

        sleep(3);

        // Simulate a reply message
        $thread->reply([
            'body' => "Oh dear - what happened?",
            'alias' => $user2->first_name . ' ' . $user2->last_name,
            'author' => $user2
        ]);

        // This si the code we are testing.
        $message = $thread->originalMessage();

        $this->assertInstanceOf('Parley\Models\Message', $message);
        $this->assertEquals('There was a problem with your order', $message->body);
    }

    public function testRetrieveAllMessages()
    {
        $user1 = User::create(['email' => 'test1@test.com', 'first_name' => 'Test', 'last_name' => 'User']);
        $user2 = User::create(['email' => 'test2@test.com', 'first_name' => 'Another', 'last_name' => 'User']);

        $thread = Thread::create(['subject' => 'Test Message'])->withParticipants([$user1, $user2])->message([
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
        $thread->withParticipants($user1);
        $thread->addMember($user2);

        $thread->reply([
            'body' => 'This is the first message in this thread',
            'author' => $user1,
            'alias' => $user1->email
        ]);

        $this->assertFalse($thread->memberHasRead($user1));

        $thread->markReadForMembers($user1);

        $this->assertTrue($thread->memberHasRead($user1));

        $thread->markUnreadForMember($user1);

        $this->assertFalse($thread->memberHasRead($user1));

        $thread->markReadForMembers($user1);

        $this->assertTrue($thread->memberHasRead($user1));

        $thread->reply([
            'body' => 'This is the second message in the thread.',
            'author' => $user2,
            'alias' => $user2->email
        ]);

        $this->assertFalse($thread->memberHasRead($user1));
    }

    // todo add test for $thread->members and $thread->members($except)
}
