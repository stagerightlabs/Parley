<?php

use Carbon\Carbon;
use Illuminate\Database\Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Epiphyte\Group;
use Epiphyte\User;
use Epiphyte\Widget;
use Parley\Facades\Parley;

class ParleyManagerTests extends ParleyTestCase
{
    private $nikolai;
    private $irina;
    private $prozorovGroup;

    public function setUp()
    {
        parent::setUp();

        // Establish the players in our dialogue
        $this->irina = User::create(['email' => 'irina@prozorov.net', 'first_name' => 'Irina', 'last_name' => 'Prozorovna']);
        $this->nikolai = User::create(['email' => 'nikolai@tuzenbach.com', 'first_name' => 'Nikolai', 'last_name' => 'Tuzenbach']);
        $this->prozorovGroup = Group::create(['name' => 'Prozorovs']);
    }

    public function test_parley_discussion()
    {
        $parley = Parley::discuss([
            'title'  => 'Happy Name Day!',
            'body'   => "Congratulations on your 20th name day!",
            'alias'  => $this->irina->alias,
            'author' => $this->irina
        ])->with($this->nikolai);

        sleep(2);

        $parley->reply([
            'body'   => "Yes, I see that there is a mistake. Please cancel my order.",
            'author' => $this->nikolai
        ]);

        $members = $parley->members();
        $newestMessage = $parley->newestMessage();
        $originalMessage = $parley->originalMessage();

        $this->assertInstanceOf('Parley\Models\Thread', $parley);
        $this->assertEquals($parley->subject, 'Happy Name Day!');
        $this->assertEquals(2, $parley->messages()->count());

        $this->assertInstanceOf('Illuminate\Support\Collection', $members);
        $this->assertEquals(2, $members->count());

        $this->assertEquals('There was a problem with your order', $originalMessage->body);
        $this->assertInstanceOf('Parley\Models\Message', $newestMessage);
        $this->assertEquals('Congratulations on your 20th name day!', $newestMessage->body);
    }

    public function test_parley_discussion_with_reference_object()
    {
        $widgetObject = Widget::create(['name' => 'Gift']);

        $parley = Parley::discuss([
                'title'  => 'Happy Name Day!',
                'body'   => "Congratulations on your 20th name day!",
                'alias'  => $this->irina->alias,
                'author' => $this->irina
            ], $widgetObject)->with($this->nikolai);

        $this->assertInstanceOf('Parley\Models\Thread', $parley);
        $this->assertInstanceOf('Epiphyte\Widget', $parley->getReferenceObject());
        $this->assertEquals($parley->subject, 'Happy Name Day!');
    }

    public function test_retrieving_member_threads()
    {
        $this->simulate_a_conversation("Happy Name Day!");
        $parley2 = $this->simulate_a_conversation("Regiment Newsletter");
        $parley2->closedByMember($this->irina);
        $parley3 = $this->simulate_a_conversation("Request from Natasha");
        $parley3->delete();
        $parley4 = Parley::discuss([
            'title'  => 'RSVP',
            'body'   => "Thank you for the invitation - I will be there.",
            'author' => $this->nikolai
        ])->with($this->prozorovGroup);
        $parley4->markReadForMembers($this->nikolai);

        $irinaThreads             = Parley::gatherFor($this->irina)->get();
        $irinaOpenThreads         = Parley::gatherFor($this->irina)->open()->get();
        $irinaClosedThreads       = Parley::gatherFor($this->irina)->closed()->get();
        $irinaThreadsWithTrashed  = Parley::gatherFor($this->irina)->withTrashed()->get();
        $irinaThreadsOnlyTrashed  = Parley::gatherFor($this->irina)->onlyTrashed()->get();
        $irinaUnreadThreadCount   = Parley::gatherFor($this->irina)->unread()->count();
        $irinaReadThreadCount     = Parley::gatherFor($this->irina)->read()->count();
        $nikolaiUnreadThreadCount = Parley::gatherFor($this->nikolai)->unread()->count();
        $nikolaiReadThreadCount   = Parley::gatherFor($this->nikolai)->read()->count();
        $multiGatherThreads       = Parley::gatherFor([$this->irina, $group])->get();

        $this->assertEquals(2, $irinaThreads->count());
        $this->assertEquals(1, $irinaOpenThreads->count());
        $this->assertEquals(1, $irinaClosedThreads->count());
        $this->assertEquals(3, $irinaThreadsWithTrashed->count());
        $this->assertEquals(1, $irinaThreadsOnlyTrashed->count());
        $this->assertEquals(2, $irinaAll);
        $this->assertEquals(1, $irinaUnread);
        $this->assertEquals(1, $irinaRead);
        $this->assertEquals(2, $nikolaiUnread);
        $this->assertEquals(0, $nikolaiRead);
        $this->assertEquals(3, $multiGatherThreads->count());
        $this->assertInstanceOf('Parley\Support\Collection', $multiGatherThreads);
        $this->assertEquals(3, $multiGatherThreads->unread());
    }

    public function test_gathering_threads_for_invalid_member()
    {
        $group = null;

        $parleys = Parley::gatherFor($group)->get();

        $this->assertInstanceOf('Parley\Support\Collection', $parleys);
        $this->assertEquals(0, $parleys->count());
    }

    private function simulate_a_conversation($title)
    {
        $parley = Parley::discuss([
            'title'  => $title,
            'body'   => "Congratulations on your 20th name day!",
            'alias'  => $this->irina->alias,
            'author' => $this->irina
        ])->with($this->nikolai);

        sleep(2);

        $parley->reply([
            'body'   => "Yes, I see that there is a mistake. Please cancel my order.",
            'alias'  => $this->nikolai->first_name . ' ' . $this->nikolai->last_name,
            'author' => $this->nikolai
        ]);

        return $parley;
    }
}
