<?php namespace SRLabs\Parley;

use SRLabs\Parley\Models\Thread;

trait ParleyTrait {

    public function parley( $creator ) {
        $thread = new Thread();
        $thread->addMember($this);
        $thread->addMember($creator);
        return $thread;
    }

    public function getParleys() {
        return Thread::join('parley_members', 'parley_threads.id', '=', 'parley_members.parley_thread_id')
                ->where('parley_members.parleyable_id', $this->id)
                ->where('parley_members.parleyable_type', $this->getModel())
                ->get();
    }

    public function retrieve() {

    }


    public function retrieveForWithTrashed() {

    }

    public function remove() {

    }

}


