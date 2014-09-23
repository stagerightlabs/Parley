<?php namespace SRLabs\Parley\Support;

use SRLabs\Parley\Support\Collection;
use ReflectionClass;
use SRLabs\Parley\Models\Thread;

class Selector {

    protected $type;

    protected $status;

    protected $trashed;

    protected $members;

    public function __construct($options = null)
    {
        if ($options && is_array($options)) {
            $this->type   = ( array_key_exists('type', $options) ? $options['type'] : 'any' );
            $this->trashed = ( array_key_exists('trashed', $options) ? $options['trashed'] : 'no' );
            $this->status = ( array_key_exists('status', $options) ? $options['status'] : 'all' );
        }
    }

    /**
     *
     * @return $this
     */
    public function withTrashed()
    {
        $this->trashed = 'yes';

        return $this;
    }

    public function onlyTrashed()
    {
        $this->trashed = 'only';

        return $this;
    }

    public function read()
    {
        $this->status = 'read';

        return $this;
    }

    public function unread()
    {
        $this->status = 'unread';

        return $this;
    }

    /**
     * @param $members
     *
     * @return $this
     */
    public function belongingTo($members)
    {
        if ( ! is_array($members) )
        {
            $members = [$members];
        }

        $this->members = $members;

        return $this;
    }

    /**
     *
     * @return Collection
     */
    public function get()
    {
        $results = new Collection();

        foreach ($this->members as $member)
        {
            $results = $results->merge($this->getThreads($member));
        }

        return $results;
    }

    public function count()
    {
        $count = 0;

        foreach ($this->members as $member)
        {
            $count += $this->getThreads($member)->count();
        }

        return $count;
    }

    /**
     * Get Threads belonging to a specific Member object
     *
     * @param $member
     *
     * @return mixed
     */
    public function getThreads( $member )
    {
        // Confirm this is a Parleyable object
        if (! $this->confirmObjectIsParleyable( $member ) )
        {
            return new Collection();
        }

        $query = Thread::join('parley_members', 'parley_threads.id', '=', 'parley_members.parley_thread_id')
            ->where('parley_members.parleyable_id', $member->id)
            ->where('parley_members.parleyable_type', $this->getObjectClassName($member))
            ->select('parley_threads.*', 'parley_members.is_read as is_read');

        switch ($this->trashed)
        {
            case 'yes':
                $query = $query->withTrashed();
                break;

            case 'only':
                $query = $query->onlyTrashed();
                break;

            default:
                break;
        }

        if ($this->type == 'open')
        {
            $query = $query->whereNull('parley_threads.closed_at');
        }

        if ($this->type == 'closed')
        {
            $query = $query->whereNotNull('parley_threads.closed_at');
        }

        if ($this->status == 'read')
        {
            $query = $query->where('parley_members.is_read', 1);
        }

        if ($this->status == 'unread')
        {
            $query = $query->where('parley_members.is_read', 0);
        }

        return $query->orderBy('updated_at', 'desc')->get();
    }

    /**
     * @param $object
     *
     * @return mixed
     */
    protected function getObjectClassName( $object )
    {
        // Reflect on the Object
        $reflector = new ReflectionClass( $object );

        // Return the class name
        return $reflector->getName();
    }

    protected function confirmObjectIsParleyable( $object )
    {
        if ( is_object( $object ) )
        {
            // Reflect on the Object
            $reflector = new ReflectionClass( $object );

            // Is this object parleyable?
            return ( in_array('SRLabs\Parley\Traits\Parleyable', $reflector->getTraitNames() ) );
        }

        return false;
    }

} 