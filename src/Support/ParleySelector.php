<?php

namespace Parley\Support;

use Illuminate\Support\Collection;
use Parley\Models\Thread;
use Parley\Traits\ParleyHelpersTrait;
use ReflectionClass;

class ParleySelector
{
    use ParleyHelpersTrait;

    protected $type;
    protected $status;
    protected $trashed;
    protected $members;

    public function __construct($members)
    {
        $this->members = $members;
    }

    /**
     * Include "deleted" threads in the selection
     *
     * @return $this
     */
    public function withTrashed()
    {
        $this->trashed = 'yes';

        return $this;
    }

    /**
     * Select only threads that have been "deleted" (aka soft-deleted)
     *
     * @return $this
     */
    public function onlyTrashed()
    {
        $this->trashed = 'only';

        return $this;
    }

    /**
     * Select only threads that have been read
     *
     * @return $this
     */
    public function read()
    {
        $this->status = 'read';

        return $this;
    }

    /**
     * Select only unread threads
     *
     * @return $this
     */
    public function unread()
    {
        $this->status = 'unread';

        return $this;
    }

    /**
     * Select only threads that are "open"
     *
     * @return $this
     */
    public function open()
    {
        $this->type = 'open';

        return $this;
    }

    public function closed()
    {
        $this->type = 'closed';

        return $this;
    }

    /**
     * Return a count of the threads that have been selected
     *
     * @return int
     */
    public function count()
    {
        $count = 0;

        foreach ($this->members as $member) {
            $count += $this->getThreadsForMember($member)->count();
        }

        return $count;
    }

    /**
     * Return a collection of the threads that have been selected
     *
     * @return Collection
     */
    public function get()
    {
        $results = new Collection();

        foreach ($this->members as $member) {
            $results = $results->merge($this->getThreadsForMember($member));
        }

        return $results;
    }

    /**
     * Get Threads belonging to a specific Member object
     *
     * @param $member
     *
     * @return mixed
     */
    public function getThreadsForMember($member)
    {
        // Confirm the specified member is a valid Parleyable Object
        if (! $this->confirmObjectIsParleyable($member)) {
            return new Collection();
        }

        $query = Thread::join('parley_members', 'parley_threads.id', '=', 'parley_members.parley_thread_id')
            ->where('parley_members.parleyable_id', $member->id)
            ->where('parley_members.parleyable_type', get_class($member))
            ->select('parley_threads.*',
                'parley_members.is_read as is_read',
                'parley_members.parleyable_id as member_id',
                'parley_members.parleyable_type as member_type');

        switch ($this->trashed) {
            case 'yes':
                $query = $query->withTrashed();
                break;

            case 'only':
                $query = $query->onlyTrashed();
                break;

            default:
                break;
        }

        if ($this->type == 'open') {
            $query = $query->whereNull('parley_threads.closed_at');
        }

        if ($this->type == 'closed') {
            $query = $query->whereNotNull('parley_threads.closed_at');
        }

        if ($this->status == 'read') {
            $query = $query->where('parley_members.is_read', 1);
        }

        if ($this->status == 'unread') {
            $query = $query->where('parley_members.is_read', 0);
        }

        return $query->orderBy('updated_at', 'desc')->get();
    }
}
