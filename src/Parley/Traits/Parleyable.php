<?php namespace SRLabs\Parley\Traits;

use SRLabs\Parley\Models\Thread;

trait Parleyable {

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



}


