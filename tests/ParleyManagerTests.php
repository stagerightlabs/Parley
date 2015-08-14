<?php

use Carbon\Carbon;
use Illuminate\Database\Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Chekhov\Group;
use Chekhov\User;
use Chekhov\Widget;
use Parley\Facades\Parley;

class ParleyManagerTests extends ParleyTestCase
{
    public function test_parley_discussion()
    {
        $parley = Parley::discuss([
            'subject'  => 'Happy Name Day!',
            'body'   => "Congratulations on your 20th name day!",
            'alias'  => $this->nikolai->alias,
            'author' => $this->nikolai
        ])->withParticipant($this->irina);

        sleep(2);

        $parley->reply([
            'body'   => "I am feeling so very old today.",
            'author' => $this->irina
        ]);

        $members = $parley->members();
        $newestMessage = $parley->newestMessage();
        $originalMessage = $parley->originalMessage();

        $this->assertInstanceOf('Parley\Models\Thread', $parley);
        $this->assertEquals($parley->subject, 'Happy Name Day!');
        $this->assertEquals(2, $parley->messages()->count());

        $this->assertInstanceOf('Illuminate\Support\Collection', $members);
        $this->assertEquals(2, $members->count());

        $this->assertEquals('Congratulations on your 20th name day!', $originalMessage->body);
        $this->assertInstanceOf('Parley\Models\Message', $newestMessage);
        $this->assertEquals('I am feeling so very old today.', $newestMessage->body);
    }

    public function test_parley_discussion_with_reference_object()
    {
        $widgetObject = Widget::create(['name' => 'Gift']);

        $parley = Parley::discuss([
                'subject'  => 'Happy Name Day!',
                'body'   => "Congratulations on your 20th name day!",
                'alias'  => $this->irina->alias,
                'author' => $this->irina
            ], $widgetObject)->withParticipants($this->nikolai);

        $this->assertInstanceOf('Parley\Models\Thread', $parley);
        $this->assertInstanceOf('Chekhov\Widget', $parley->getReferenceObject());
        $this->assertEquals($parley->subject, 'Happy Name Day!');
    }

    public function test_gathering_member_threads()
    {
        // Parley #1
        $parley1 = $this->simulate_a_conversation("Happy Name Day!");
        $parley1->reply([
            'body'   => "Nonsense - you are a beautiful young woman",
            'author' => $this->nikolai
        ]);
        // Parley #2
        $parley2 = $this->simulate_a_conversation("My thoughts on our future society");
        $parley2->markReadForMembers($this->irina);
        // Parley #3
        $parley3 = $this->simulate_a_conversation("Regiment Newsletter");
        $parley3->closedBy($this->irina);
        // Parley #4
        $parley4 = $this->simulate_a_conversation("Pay no attention to Solyony");
        $parley4->delete();
        // Parley #5
        Parley::discuss([
            'subject'  => 'You are Invited',
            'body'   => "Please join us for dinner this evening at our residence.",
            'author' => $this->prozorovGroup
        ])->withParticipants($this->nikolai);

        $irinaThreads             = Parley::gatherFor($this->irina)->get();
        $irinaThreadsCount        = Parley::gatherFor($this->irina)->count();
        $irinaOpenThreads         = Parley::gatherFor($this->irina)->open()->get();
        $irinaClosedThreads       = Parley::gatherFor($this->irina)->closed()->get();
        $irinaThreadsWithTrashed  = Parley::gatherFor($this->irina)->withTrashed()->get();
        $irinaThreadsOnlyTrashed  = Parley::gatherFor($this->irina)->onlyTrashed()->get();
        $irinaUnreadThreadCount   = Parley::gatherFor($this->irina)->unread()->count();
        $irinaReadThreadCount     = Parley::gatherFor($this->irina)->read()->count();
        $nikolaiUnreadThreadCount = Parley::gatherFor($this->nikolai)->unread()->count();
        $nikolaiReadThreadCount   = Parley::gatherFor($this->nikolai)->read()->count();
        $multiGatherThreads       = Parley::gatherFor([$this->irina, $this->prozorovGroup])->get();
        $multiGatherUnread        = Parley::gatherFor([$this->irina, $this->prozorovGroup])->unread()->get();

        //dd(Parley::gatherFor($this->irina)->unread()->get()->toArray());

        $this->assertEquals(3, $irinaThreads->count());
        $this->assertEquals($irinaThreadsCount, $irinaThreads->count());
        $this->assertEquals(2, $irinaOpenThreads->count());
        $this->assertEquals(1, $irinaClosedThreads->count());
        $this->assertEquals(4, $irinaThreadsWithTrashed->count());
        $this->assertEquals(1, $irinaThreadsOnlyTrashed->count());
        $this->assertEquals(1, $irinaUnreadThreadCount);
        $this->assertEquals(2, $irinaReadThreadCount);
        $this->assertEquals(3, $nikolaiUnreadThreadCount);
        $this->assertEquals(1, $nikolaiReadThreadCount);
        $this->assertEquals(4, $multiGatherThreads->count());
        $this->assertEquals(1, $multiGatherUnread->count());
        $this->assertInstanceOf('Illuminate\Support\Collection', $multiGatherThreads);
        $this->assertInstanceOf('Illuminate\Support\Collection', $irinaThreads);
    }

    /**
     * @expectedException \Parley\Exceptions\NonParleyableMemberException
     */
    public function test_gathering_threads_for_invalid_member()
    {
        $this->simulate_a_conversation("Happy Name Day!");

        $group = null;

        $parleys = Parley::gatherFor($group)->get();

        $this->assertInstanceOf('Parley\Support\Collection', $parleys);
        $this->assertEquals(0, $parleys->count());
    }
}
