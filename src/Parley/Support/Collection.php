<?php

namespace Parley\Support;

class Collection extends \Illuminate\Support\Collection
{
    public function unread()
    {
        $count = 0;

        foreach ($this->items as $thread) {
            if ($thread->is_read == 0) {
                $count++;
            }
        }

        return $count;
    }
}
