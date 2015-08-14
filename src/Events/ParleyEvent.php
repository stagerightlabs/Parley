<?php

namespace Parley\Events;

use Parley\Models\Thread;

class ParleyEvent
{
    /**
     * @var Thread
     */
    private $thread;

    /**
     * @var mixed
     */
    private $author;

    public function __construct(Thread $thread, $author)
    {
        $this->thread = $thread;
        $this->author = $author;
    }

    /**
     * @return Thread
     */
    public function getThread()
    {
        return $this->thread;
    }

    /**
     * @return mixed
     */
    public function getAuthor()
    {
        return $this->author;
    }

}