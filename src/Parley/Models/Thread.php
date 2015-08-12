<?php

namespace Parley\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Parley\Traits\ParleyHelpersTrait;
use ReflectionClass;
use Parley\Exceptions\InvalidMessageFormatException;
use Parley\Exceptions\NonParleyableMemberException;
use Parley\Exceptions\NonReferableObjectException;

class Thread extends \Illuminate\Database\Eloquent\Model
{
    use ParleyHelpersTrait;

    /*****************************************************************************
     * Eloquent Configuration
     *****************************************************************************/
    use SoftDeletes;
    protected $table = 'parley_threads';
    protected $dates = ['created_at', 'updated_at', 'closed_at', 'deleted_at'];
    protected $fillable   = [
        'subject', 'object_id', 'object_type', 'resolved_at', 'resolve_by_id', 'resolved_by_type'
    ];

    /**
     * Assign a group of members to this thread
     *
     * @param $members
     * @return $this
     */
    public function withParticipants($members)
    {
        if (! is_array($members)) {
            $members = [$members];
        }

        $members = array_flatten($members);

        foreach ($members as $member) {
            $this->addMember($member);
        }

        $this->notifyMembers('new.thread');

        return $this;
    }

    /**
     * Add a single member this thread
     *
     * @param $member
     * @return bool
     * @throws NonParleyableMemberException
     */
    public function addMember($member)
    {
        // Is this Member parleyable?
        $this->confirmObjectIsParleyable($member);

        // Add the member to the Parley
        return \DB::table('parley_members')->insert(array(
            'parley_thread_id' => $this->id,
            'parleyable_id' => $member->id,
            'parleyable_type' => get_class($member)
        ));
    }

    /**
     * Convenience wrapper for the Add Member method
     *
     * @param $member
     * @return bool
     */
    public function addParticipant($member)
    {
        return $this->addMember($member);
    }

    /**
     * Remove a Member from this Thread
     *
     * @param $member
     *
     * @return mixed
     * @throws NonParleyableMemberException
     */
    public function removeMember($member)
    {
        // Is this Member parleyable?
        $this->confirmObjectIsParleyable($member, true);

        // Remove this member from the Thread
        return \DB::table('parley_members')
            ->where('parley_thread_id', $this->id)
            ->where('parleyable_id', $this->id)
            ->where('parleyable_type', get_class($member))
            ->delete();
    }

    /**
     * Convenience wrapper for the remove member method
     *
     * @param $member
     * @return mixed
     */
    public function removeParticipant($member)
    {
        return $this->removeMember($member);
    }

    /**
     * Determine if an member is a member of this thread
     *
     * @param $member
     *
     * @return bool
     */
    public function isMember($member)
    {
        // Is this Member parleyable?
        $this->confirmObjectIsParleyable($member, true);

        return (count(
            \DB::table('parley_members')
                ->where('parley_thread_id', $this->id)
                ->where('parleyable_id', $member->id)
                ->where('parleyable_type', get_class($member))
                ->get()
        ) > 0);
    }

    /**
     * Convenience wrapper for the isMember method
     *
     * @param $member
     * @return bool
     */
    public function isParticipant($member)
    {
        return $this->isMember($member);
    }

    /**
     * Retrieve all the members associated with this Parley Thread
     *
     * @return Collection
     */
    public function members($options = array())
    {
        $exclusions = array_key_exists('except', $options) ? array_flatten($options['except']) : [];

        $members = \DB::table('parley_members')->where('parley_thread_id', $this->id)->get();
        $filteredMembers = new Collection();

        foreach ($members as $member) {
            $exclude = false;

            foreach ($exclusions as $target) {
                if ($member->parleyable_id == $target->id && $member->parleyable_type == get_class($target)) {
                    $exclude = true;
                }
            }

            if (! $exclude) {
                $object = \App::make($member->parleyable_type)->find($member->parleyable_id);
                $filteredMembers->push($object);
            }
        }

        return $filteredMembers;
    }

    /**
     * Associate the initial Message Object with this thread.
     *
     * @param array $message
     *
     * @return $this
     * @throws InvalidMessageFormatException
     */
    public function initialMessage($message = array())
    {
        $this->createMessage($message);

        // Mark the thread as "unread" for the author
        $this->markReadForMembers($message['author']);
    }

    /**
     * Add a new Message Object to this thread
     *
     * @param $message
     *
     * @throws InvalidMessageFormatException
     * @throws NonReferableObjectException
     * @return \Parley\Models\Message
     */
    public function reply($message)
    {
        $this->createMessage($message);

        $this->markUnreadForAllMembers();

        // Change the thread's 'updated_at' timestamp
        $this->touch();

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
     * Return the most recent Message associated with this Thread
     *
     * @return mixed
     */
    public function originalMessage()
    {
        return Message::where('parley_thread_id', $this->id)
            ->orderBy('created_at', 'asc')
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
    public function setReferenceObject($object)
    {
        $this->confirmObjectHasId($object);

        // Set the object referece fields
        $this->object_id = $object->id;
        $this->object_type = get_class($object);

        return $this->save();
    }

    /**
     * Return the object this Thread is concerned with, if any
     *
     * @return mixed
     */
    public function getReferenceObject()
    {
        if ($this->object_type == '') {
            return null;
        }

        return \App::make($this->object_type)->find($this->object_id);
    }

    /**
     * Remove the Thread's reference Object
     *
     * @return bool
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
     * @param $member - Thread Member Object
     * @return bool
     */
    public function closedBy($member)
    {
        // Setting a value to the "closed_at" field marks the thread as closed.
        $this->closed_at = new Carbon;

        // Make a note of which member closed the thread
        $this->closed_by_id = $member->id;
        $this->closed_by_type = get_class($member);

        return $this->save();
    }

    /**
     * Has this Thread been closed?
     *
     * @return bool
     */
    public function isClosed()
    {
        return (! is_null($this->closed_at));
    }

    /**
     * Return the member that closed the thread
     *
     * @return null
     */
    public function getCloser()
    {
        // First Make sure this thread has been closed.
        if (! $this->isClosed()) {
            return null;
        }

        // Return the Member who closed the Thread
        return $object = \App::make($this->closed_by_type)->find($this->closed_by_id);
    }


    /**
     * Mark thread as open
     *
     * @return bool
     */
    public function reopen()
    {
        $this->closed_at = null;
        $this->closed_by_id = 0;
        $this->closed_by_type = '';

        return $this->save();
    }

    /**
     * Notify Members that a Thread action has occured.
     *
     * @param $action
     */
    public function notifyMembers($action)
    {
        foreach ($this->members() as $member) {
            $member->notify($action, $this);
        }

        // todo set notification flag on parley_members table
    }

    /**
     * Determine if a thread has been marked read for a given user
     *
     * @param $member
     *
     * @return bool
     */
    public function hasBeenReadByMember($member)
    {
        $status = \DB::table('parley_members')
            ->where('parley_thread_id', $this->id)
            ->where('parleyable_id', $member->id)
            ->where('parleyable_type', get_class($member))
            ->pluck('is_read');

        return (bool) $status;
    }

    /**
     * Mark the Thread as read for a given member.
     *
     * @param array $members
     * @return bool
     *
     */
    public function markReadForMembers($members = array())
    {
        $members = $this->ensureArrayable($members);

        foreach ($members as $member) {
            \DB::table('parley_members')
                ->where('parley_thread_id', $this->id)
                ->where('parleyable_id', $member->id)
                ->where('parleyable_type', get_class($member))
                ->update(['is_read' => 1]);
        }

        return true;
    }

    /**
     * Mark the Thread as Read for all members
     *
     * @return bool
     */
    public function markReadForAllMembers()
    {
        \DB::table('parley_members')
            ->where('parley_thread_id', $this->id)
            ->update(['is_read' => 0]);

        return true;
    }

    /**
     * Mark the Thread as Unread for a given member
     *
     * @param $members
     *
     * @return bool
     */
    public function markUnreadForMembers($members)
    {
        $members = $this->ensureArrayable($members);

        foreach ($members as $member) {
            \DB::table('parley_members')
                ->where('parley_thread_id', $this->id)
                ->where('parleyable_id', $member->id)
                ->where('parleyable_type', get_class($member))
                ->update(['is_read' => 0]);
        }

        return true;
    }

    /**
     * Mark the Thread as Unread for all members
     *
     * @return bool
     */
    public function markUnreadForAllMembers()
    {
        \DB::table('parley_members')
            ->where('parley_thread_id', $this->id)
            ->update(['is_read' => 0]);

        return true;
    }

    /**
     * Determine if a thread member has been notified about this thread
     *
     * @param $member
     *
     * @return bool
     */
    public function memberHasBeenNotified($member)
    {
        $status = \DB::table('parley_members')
            ->where('parley_thread_id', $this->id)
            ->where('parleyable_id', $member->id)
            ->where('parleyable_type', get_class($member))
            ->pluck('notified');

        return (bool) $status;
    }

    /**
     * Set the "notified" flag for a set of members
     *
     * @param $member
     *
     * @return bool
     */
    public function markNotifiedForMembers($members = array())
    {
        $members = $this->ensureArrayable($members);

        foreach ($members as $member) {
            \DB::table('parley_members')
                ->where('parley_thread_id', $this->id)
                ->where('parleyable_id', $member->id)
                ->where('parleyable_type', get_class($member))
                ->update(['notified' => 1]);
        }

        return true;
    }

    /**
     * Remove the notified flag for the given members
     *
     * @param $members
     *
     * @return bool
     */
    public function removeNotifiedFlagForMembers(array $members)
    {
        $members = $this->ensureArrayable($members);

        foreach ($members as $member) {
            \DB::table('parley_members')
                ->where('parley_thread_id', $this->id)
                ->where('parleyable_id', $member->id)
                ->where('parleyable_type', get_class($member))
                ->update(['notified' => 0]);
        }

        return true;
    }

    /**
     * Create a new message object for this thread
     *
     * @return Message
     * @throws InvalidMessageFormatException
     */
    protected function createMessage(array $message)
    {
        // Validate $message structure
        foreach (['title', 'body', 'author'] as $key) {
            if (!in_array($key, $message)) {
                throw new InvalidMessageFormatException("Missing {$key} from message attributes");
            }
        }

        // Specify an author alias if it doesn't already exist
        $messge['alias']  = array_key_exists('alias', $message) ? $message['alias'] : $message['author']->alias;

        // Confirm the Author member contains a valid 'id' field
        $this->confirmObjectHasId($message['author']);

        // Assemble the Message components
        $data['body'] = e($message['body']);
        $data['author_alias'] = e($message['alias']);
        $data['author_id'] = $message['author']->id;
        $data['author_type'] = get_class($message['author']);
        $data['parley_thread_id'] = $this->id;

        // Create the Message Object
        $message = Message::create($data);
        $message->hash = \Hashids::encode($message->id);
        $message->save();

        return $message;
    }
}
