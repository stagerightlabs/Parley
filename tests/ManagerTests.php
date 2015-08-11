<?php

use Carbon\Carbon;
use Illuminate\Database\Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Parley\Models\Thread;
use Epiphyte\Group, Epiphyte\User, Epiphyte\Widget;

class ManagerTests extends ParleyTestCase {


    /*
     * ParleyManager Tests
     */
    public function testParleyConversation()
    {
        $user1 = User::create(['email' => 'test1@test.com', 'first_name' => 'Test', 'last_name' => 'User']);
        $user2 = User::create(['email' => 'test2@test.com', 'first_name' => 'Another', 'last_name' => 'User']);

        $thread = \Parley::discuss('This is an important message')->amongst([$user1, $user2])->message([
            'body'   => "There was a problem with your order",
            'alias'  => $user1->first_name . ' ' . $user1->last_name,
            'author' => $user1
        ]);

        sleep(3);

        $thread->reply([
            'body'   => "Yes, I see that there is a mistake. Please cancel my order.",
            'alias'  => $user2->first_name . ' ' . $user2->last_name,
            'author' => $user2
        ]);

        $members = $thread->members();

        $this->assertInstanceOf('Parley\Models\Thread', $thread);
        $this->assertInstanceOf('Illuminate\Support\Collection', $members);
        $this->assertEquals(2, $members->count());
        $this->assertEquals($thread->subject, 'This is an important message');

        $message = $thread->newestMessage();

        $this->assertInstanceOf('Parley\Models\Message', $message);
        $this->assertEquals('Yes, I see that there is a mistake. Please cancel my order.', $message->body);
        $this->assertEquals(2, $thread->messages()->count());

        $message = $thread->originalMessage();

        $this->assertEquals(2, $thread->messages()->count());
    }


    public function testParleyDiscussWithReferenceObject()
    {
        $widget = Widget::create(['name' => 'Widget1']);

        $thread = \Parley::discuss('This is a Parley', $widget);

        $this->assertInstanceOf('Parley\Models\Thread', $thread);
        $this->assertInstanceOf('Epiphyte\Widget', $thread->getReferenceObject());
        $this->assertEquals($thread->subject, 'This is a Parley');
    }

}