<?php namespace SRLabs\Parley\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletingTrait;
use Illuminate\Support\Collection;
use ReflectionClass;
use SRLabs\Parley\Exceptions\InvalidMessageFormatException;
use SRLabs\Parley\Exceptions\NonParleyableMemberException;
use SRLabs\Parley\Exceptions\NonReferableObjectException;
use SRLabs\Parley\Models\Message;

class Thread extends \Eloquent {

    /**
     * Establish the DB table associated with the Thread Model
     *
     * @var string
     */
    protected $table = 'parley_threads';


    /**
     * Declare model fields available for Mass Assignment
     *
     * @var array
     */
    protected $fillable   = ['subject', 'object_id', 'object_type', 'resolved_at', 'resolve_by_id', 'resolved_by_type' ];

    /**
     * Establish the date fields to be turned into Carbon objects
     *
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'closed_at', 'deleted_at'];
    }

    /**
     * Allow for Soft-Deleting of Threads
     */
    use SoftDeletingTrait;

    /**
     * Assign a group of members to this thread
     *
     * @param $members
     */
    public function amongst($members)
    {
        if ( ! is_array($members))
        {
            $members = [$members];
        }

        foreach ($members as $member)
        {
            $this->addMember($member);
        }

        return $this;
    }

    /**
     * Add a member to a Parley Thread
     *
     * @param $member
     */
    public function addMember($member)
    {
        // Is this Member parleyable?
        $this->confirmObjectIsParleyable( $member );

        // Add the member to the Parley
        \DB::table('parley_members')->insert(array(
            'parley_thread_id' => $this->id,
            'parleyable_id' => $member->id,
            'parleyable_type' => $this->getObjectClassName($member)
        ));

        // Success!
        return true;
    }

    /**
     * Remove a Thread Member from the Thread
     *
     * @param $member
     *
     * @return mixed
     * @throws NonParleyableMemberException
     */
    public function removeMember( $member )
    {
        // Is this Member parleyable?
        $this->confirmObjectIsParleyable( $member );

        // Remove this member from the Thread
        return \DB::table('parley_members')
            ->where('parley_thread_id', $this->id)
            ->where('parleyable_id', $this->id)
            ->where('parleyable_type', $this->getObjectClassName($member))
            ->delete();
    }

    /**
     * Determine if an object is a member of a Parley Thread
     *
     * @param $object
     *
     * @return bool
     */
    public function isMember($object)
    {
        return (count(
                \DB::table('parley_members')
                    ->where('parley_thread_id', $this->id)
                    ->where('parleyable_id', $object->id)
                    ->where('parleyable_type', $this->getObjectClassName($object))
                    ->get()
            ) > 0);
    }

    /*
     * Retrieve all the members associated with this Parley Thread
     *
     * @return Illuminate\Support\Collection
     */
    public function members()
    {
        $members = new Collection();

        $results = \DB::table('parley_members')
            ->where('parley_thread_id', $this->id)
            ->get();

        foreach ($results as $member)
        {
            $object = \App::make($member->parleyable_type)->find($member->parleyable_id);
            $members->push($object);
        }

        return $members;
    }

    /**
     * Associate the initial Message Object with this thread.
     *
     * @param array $message
     *
     * @throws InvalidMessageFormatException
     */
    public function message($message = array())
    {
        $message = $this->reply($message);

        $this->notifyMembers('new.thread');

        if ($message)
        {
            return $this;
        }
        else
        {
            throw new InvalidMessageFormatException('There was a problem creating the first message for this thread');
        }
    }

    /**
     * Add a new Message Object to this thread
     *
     * @param $message
     *
     * @throws InvalidMessageFormatException
     * @throws NonReferableObjectException
     * @return \SRLabs\Parley\Models\Message
     */
    public function reply( $message )
    {
        // Make sure the message array contains all the necessary details.
        if (! array_key_exists('body', $message))
        {
            throw new InvalidMessageFormatException('Message must have a body string');
        }

        if (! array_key_exists('alias', $message))
        {
            throw new InvalidMessageFormatException('Message must have an author alias');
        }

        if (! array_key_exists('author', $message))
        {
            throw new InvalidMessageFormatException('Message must be provided with Authoring Object');
        }

        // Confirm the Author object contains a valid 'id' field
        $this->confirmObjectHasId($message['author']);

        // Assemble the Message components
        $data['body'] = $message['body'];
        $data['author_alias'] = $message['alias'];
        $data['author_id'] = $message['author']->id;
        $data['author_type'] = $this->getObjectClassName($message['author']);
        $data['parley_thread_id'] = $this->id;

        // Create the Message Object
        $message = Message::create( $data );

        // Mark the thread as unread for all members.
        $this->markUnreadForAllMembers();

        return $message;
    }

    /**
     * Return the most recent Message associated with this Thread
     *
     * @return mixed
     */
    public function newestMessage()
    {
        return Message::where('parley_thread_id', $this->id)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Return the Collection of Messages associated with this Thread
     *
     * @return \Illuminate\Support\Collection
     */
    public function messages()
    {
        return Message::where('parley_thread_id', $this->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }


    /**
     * Set the Object that this Thread is concerned with, if needed.
     *
     * @param $object
     *
     * @return mixed
     * @throws NonReferableObjectException
     */
    public function setReferenceObject( $object )
    {
        // Confirm that this object can be referred to by Id
        $this->confirmObjectHasId( $object);

        $this->object_id = $object->id;
        $this->object_type = $this->getObjectClassName($object);
        return $this->save();
    }


    /**
     * Return the Object this Thread is concerned with, if any
     *
     * @return mixed
     */
    public function getReferenceObject()
    {
        if ($this->object_type == '')
        {
            return null;
        }

        return \App::make($this->object_type)->find($this->object_id);
    }

    /**
     * Remove the Thread's reference Object
     *
     * @return mixed
     */
    public function clearReferenceObject()
    {
        $this->object_id = null;
        $this->object_type = '';
        return $this->save();
    }

    /**
     * Mark this Thread as Closed
     *
     * @param $closer - Thread Member Object
     */
    public function close( $closer )
    {
        // Mark the Thread as Closed
        $this->closed_at = new Carbon;

        // Record
        $this->closed_by_id = $closer->id;
        $this->closed_by_type = $this->getObjectClassName($closer);
        $this->save();
    }

    /**
     * Has this Thread been closed?
     *
     * @return bool
     */
    public function isClosed() {
        return (! is_null($this->closed_at) );
    }

    /**
     * Return the object that closed the thread
     *
     * @return null
     */
    public function getCloser() {

        // First Make sure this thread has been closed.
        if ( ! $this->isClosed() )
        {
            return null;
        }

        // Return the Member who closed the Thread
        return $object = \App::make($this->closed_by_type)->find($this->closed_by_id);
    }

    /**
     * Mark a thread as open
     */
    public function open() {
        $this->closed_at = NULL;
        $this->closed_by_id = 0;
        $this->closed_by_type = '';
        $this->save();
    }

    /**
     * Notify Members that a Thread action has occured.
     *
     * @param $action
     */
    public function notifyMembers( $action )
    {
        foreach ($this->members() as $member)
        {
            $member->notify( $action, $this );
        }
    }

    /**
     * Determine if a thread has been marked read for a given user
     *
     * @param $member
     *
     * @return bool
     */
    public function memberHasRead( $member ) {

        $status = \DB::table('parley_members')
            ->where('parley_thread_id', $this->id)
            ->where('parleyable_id', $member->id)
            ->where('parleyable_type', $this->getObjectClassName($member))
            ->pluck('is_read');

        return (bool) $status;
    }


    /**
     * Mark the Thread as read for a given member.
     *
     * @param $member
     *
     * @return bool
     */
    public function markReadForMember( $member )
    {
        \DB::table('parley_members')
            ->where('parley_thread_id', $this->id)
            ->where('parleyable_id', $member->id)
            ->where('parleyable_type', $this->getObjectClassName($member))
            ->update(['is_read' => 1]);

        return true;
    }

    /**
     * Mark the Thread as Read for a given member
     *
     * @param $member
     *
     * @return bool
     */
    public function markReadForAllMembers( )
    {
        \DB::table('parley_members')
            ->where('parley_thread_id', $this->id)
            ->update(['is_read' => 0]);

        return true;
    }

    /**
     * Mark the Thread as Unread for a given member
     *
     * @param $member
     *
     * @return bool
     */
    public function markUnreadForMember( $member )
    {
        \DB::table('parley_members')
            ->where('parley_thread_id', $this->id)
            ->where('parleyable_id', $member->id)
            ->where('parleyable_type', $this->getObjectClassName($member))
            ->update(['is_read' => 0]);

        return true;
    }

    /**
     * Mark the Thread as Unread for all members
     *
     * @return bool
     */
    public function markUnreadForAllMembers( )
    {
        \DB::table('parley_members')
            ->where('parley_thread_id', $this->id)
            ->update(['is_read' => 0]);

        return true;
    }

    /**
     * Helper Function: Return an Object's class name
     *
     * @param $object
     *
     * @return string
     */
    protected function getObjectClassName( $object )
    {
        // Reflect on the Object
        $reflector = new ReflectionClass( $object );

        // Return the class name
        return $reflector->getName();
    }


    /**
     * Helper Function: Determine if an object has the 'SRLabs\Parley\Traits\Parleyable' trait
     *
     * @param $object
     *
     * @return bool
     * @throws NonParleyableMemberException
     */
    protected function confirmObjectIsParleyable( $object )
    {
        // Reflect on the Object
        $reflector = new ReflectionClass( $object );

        // Does the object have the Parleyable trait? If not, thrown an exception.
        if ( ! in_array('SRLabs\Parley\Traits\Parleyable', $reflector->getTraitNames() ) )
        {
            throw new NonParleyableMemberException;
        }

        return true;
    }

    protected function confirmObjectHasId( $object )
    {
        if ( is_null($object->id) )
        {
            throw new NonReferableObjectException;
        }

        return true;
    }
}
