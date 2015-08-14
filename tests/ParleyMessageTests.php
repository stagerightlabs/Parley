<?php

use Illuminate\Database\Eloquent;
use Illuminate\Support\Collection;
use Parley\Models\Thread;
use Parley\Models\Message;

class ParleyMessageTests extends ParleyTestCase
{
    public function test_creating_messages_with_explicit_alias()
    {
        $parley = Parley::discuss([
            'subject' => 'Happy Name Day!',
            'body'    => 'Congratulations on your 20th name day!',
            'alias'   => 'Baron Nikolaj Lvovich Tuzenbach',
            'author' => $this->nikolai
        ])->withParticipants($this->irina);

        sleep(1);

        $parley->reply([
            'body'   => 'I am feeling so very old today.',
            'alias'  => 'Irina Sergeyevna Prozorova',
            'author' => $this->irina
        ]);

        $initialMessage = $parley->originalMessage();
        $replyMessage   = $parley->newestMessage();

        $this->assertInstanceOf('Parley\Models\Message', $initialMessage);
        $this->assertEquals($initialMessage->body, 'Congratulations on your 20th name day!');
        $this->assertEquals($initialMessage->author_alias, 'Baron Nikolaj Lvovich Tuzenbach');
        $this->assertNotEquals($initialMessage->author_alias, $this->nikolai->alias);

        $this->assertInstanceOf('Parley\Models\Message', $replyMessage);
        $this->assertEquals($replyMessage->body, 'I am feeling so very old today.');
        $this->assertEquals($replyMessage->author_alias, 'Irina Sergeyevna Prozorova');
        $this->assertNotEquals($replyMessage->author_alias, $this->irina->alias);

    }

    public function test_creating_messages_without_explicit_alias()
    {
        $parley = Parley::discuss([
            'subject' => 'Happy Name Day!',
            'body'    => 'Congratulations on your 20th name day!',
            'author' => $this->nikolai
        ])->withParticipants($this->irina);

        sleep(1);

        $parley->reply([
            'body'   => 'I am feeling so very old today.',
            'author' => $this->irina
        ]);

        $initialMessage = $parley->originalMessage();
        $replyMessage   = $parley->newestMessage();

        $this->assertInstanceOf('Parley\Models\Message', $initialMessage);
        $this->assertEquals($initialMessage->author_alias, $this->nikolai->alias);

        $this->assertInstanceOf('Parley\Models\Message', $replyMessage);
        $this->assertEquals($replyMessage->author_alias, $this->irina->alias);

    }

    public function test_setting_and_retrieving_message_author()
    {
        $parley = $this->simulate_a_conversation();
        $message = $parley->newestMessage();

        $originalAuthor = $message->getAuthor();

        $message->setAuthor($this->prozorovGroup);

        $updatedAuthor = $message->getAuthor();

        $this->assertInstanceOf('Chekhov\User', $originalAuthor);
        $this->assertEquals('Irina Prozorovna', $originalAuthor->alias);
        $this->assertInstanceOf('Chekhov\Group', $updatedAuthor);
        $this->assertEquals('The Prozorovs', $updatedAuthor->alias);
    }

    public function test_retrieve_thread_from_message()
    {
        $parley = $this->simulate_a_conversation();
        $message = $parley->newestMessage();
        $thread = $message->thread;

        $this->assertInstanceOf('Parley\Models\Thread', $thread);
        $this->assertEquals('Happy Name Day!', $thread->subject);
    }
}
