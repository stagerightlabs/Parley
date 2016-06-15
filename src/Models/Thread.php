<?php

namespace Parley\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Parley\Events\ParleyMessageAdded;
use Parley\Events\ParleyThreadCreated;
use Parley\Exceptions\NonMemberObjectException;
use Parley\Traits\ParleyHelpersTrait;
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
        'subject', 'object_id', 'object_type', 'resolved_at', 'resolved_by_id', 'resolved_by_type'
    ];

    /**
     * Assign a group of members to this thread
     *
     * @return $this
     */
    public function withParticipants()
    {
        $members = func_get_args();

        foreach ($members as $member) {
            $this->addMember($member);
        }

        // Send an alert to any application listeners that might be interested
        \Event::fire(new ParleyThreadCreated($this, $this->getThreadAuthor()));

        return $this;
    }

    /**
     * Convenience wrapper for withParticipants()
     *
     * @return $this
     */
    public function withParticipant()
    {
        $this->withParticipants(func_get_args());

        return $this;
    }

    /**
     * Convenience wrapper for the Add Member method
     *
     * @return $this
     */
    public function addParticipant($member)
    {
        $this->addMember($member);

        return $this;
    }

    /**
     * Add a single member this thread
     *
     * @param $member
     * @return void
     * @throws NonParleyableMemberException
     */
    protected function addMember($member)
    {
        // If we have been passed an Eloquent collection, add each member recursively
        if ($member instanceof Collection) {
            foreach ($member->all() as $m) {
                $this->addMember($m);
            }

            return;
        }

        // Or perhaps we have been given an array...
        if (is_array($member)) {
            foreach ($member as $m) {
                $this->addMember($m);
            }

            return;
        }

        // Is this Member parleyable?
        $this->confirmObjectIsParleyable($member);

        // Add the member to the Parley
        \DB::table('parley_members')->insert(array(
            'parley_thread_id' => $this->id,
            'parleyable_id' => $member->getParleyIdAttribute(),
            'parleyable_type' => get_class($member)
        ));

        return;
    }

    /**
     * Remove a Member from this Thread
     *
     * @param $member
     *
     * @return void
     * @throws NonParleyableMemberException
     */
    protected function removeMember($member)
    {
        // Is this Member parleyable?
        $this->confirmObjectIsParleyable($member, true);

        // Remove this member from the Thread
        \DB::table('parley_members')
            ->where('parley_thread_id', $this->id)
            ->where('parleyable_id', $member->getParleyIdAttribute())
            ->where('parleyable_type', get_class($member))
            ->delete();

        return;
    }

    /**
     * Convenience wrapper for the remove member method
     *
     * @param $member
     * @return $this
     */
    public function removeParticipant($member)
    {
        $this->removeMember($member);

        return $this;
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
                ->where('parleyable_id', $member->getParleyIdAttribute())
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
     * @param array $options
     * @return Collection
     */
    public function getMembers(array $options = [])
    {
        $exclusions = array_key_exists('except', $options) ? $this->ensureArrayable($options['except']) : [];

        $members = \DB::table('parley_members')->where('parley_thread_id', $this->id)->get();
        $filteredMembers = new Collection();

        foreach ($members as $member) {
            $exclude = false;

            foreach ($exclusions as $target) {
                if ($member->parleyable_id == $target->getParleyIdAttribute() && $member->parleyable_type == get_class($target)) {
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
     * @param array $messageData
     *
     * @return $this
     * @throws InvalidMessageFormatException
     */
    public function setInitialMessage($messageData = array())
    {
        // Create the first Message and add it to this thread
        $this->createMessage($messageData);

        // Add the author as a member of this Thread
        $this->addMember($messageData['author']);

        // We can assume that the author has read their own message.
        $this->markReadForMembers($messageData['author']);
    }

    /**
     * Add a new Message Object to this thread
     *
     * @param $messageData
     * @return Message
     * @throws InvalidMessageFormatException
     * @throws NonMemberObjectException
     */
    public function reply($messageData)
    {
        // Make sure the author is in fact a member of this thread
        if (!array_key_exists('author', $messageData) || !$this->isMember($messageData['author'])) {
            throw new NonMemberObjectException;
        }

        // Add this messageData to the thread
        $this->createMessage($messageData);

        // A new messageData implies that this thread is now unread for all members
        $this->markUnreadForAllMembers();

        // Except the author of the reply, of course.
        $this->markReadForMembers($messageData['author']);

        // Change the thread's 'updated_at' timestamp to be in sync with the new messageData timestamp
        $this->touch();

        // Send an alert to any application listeners that might be interested
        \Event::fire(new ParleyMessageAdded($this, $this->getThreadAuthor()));

        return $this;
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
        return $this->hasMany('Parley\Models\Message', 'parley_thread_id')->orderBy('created_at', 'desc');
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
        // Ensure that this object has a valid primary key
        $this->confirmObjectIsReferable($object);

        // Set the object reference fields
        $this->object_id = $object->getKey();
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
     * Get the authoring object for the first messageData in the thread
     *
     * @return mixed
     */
    public function getThreadAuthor()
    {
        return $this->originalMessage()->getAuthor();
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
        $this->closed_by_id = $member->getParleyIdAttribute();
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
        return (bool)$this->closed_at;
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
            ->where('parleyable_id', $member->getParleyIdAttribute())
            ->where('parleyable_type', get_class($member))
            ->value('is_read');

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
                ->where('parleyable_id', $member->getParleyIdAttribute())
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
                ->where('parleyable_id', $member->getParleyIdAttribute())
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
            ->where('parleyable_id', $member->getParleyIdAttribute())
            ->where('parleyable_type', get_class($member))
            ->value('notified');

        return (bool) $status;
    }

    /**
     * Set the "notified" flag for a set of members
     *
     * @param mixed $members
     *
     * @return bool
     */
    public function markNotifiedForMembers($members = [])
    {
        $members = $this->ensureArrayable($members);

        foreach ($members as $member) {
            \DB::table('parley_members')
                ->where('parley_thread_id', $this->id)
                ->where('parleyable_id', $member->getParleyIdAttribute())
                ->where('parleyable_type', get_class($member))
                ->update(['notified' => 1]);
        }

        return true;
    }

    /**
     * Remove the notified flag for the given members
     *
     * @param mixed $members
     *
     * @return bool
     */
    public function removeNotifiedFlagForMembers($members = [])
    {
        $members = $this->ensureArrayable($members);

        foreach ($members as $member) {
            \DB::table('parley_members')
                ->where('parley_thread_id', $this->id)
                ->where('parleyable_id', $member->getParleyIdAttribute())
                ->where('parleyable_type', get_class($member))
                ->update(['notified' => 0]);
        }

        return true;
    }

    /**
     * Create a new messageData object for this thread
     *
     * @param array $messageData
     * @return Message
     * @throws InvalidMessageFormatException
     */
    protected function createMessage(array $messageData)
    {
        // We can't proceed if there is no messageData body.
        if (! array_key_exists('body', $messageData)) {
            throw new InvalidMessageFormatException("Missing body from message data attributes");
        }

        // Assemble the Message components and create the messageData
        $message = Message::create([
            'body' => e($messageData['body']),
            'parley_thread_id' => $this->id
        ]);

        // Set the message author and author_alias
        $alias  = array_key_exists('alias', $messageData) ? $messageData['alias'] : $messageData['author']->parley_alias;
        $message->setAuthor($messageData['author'], $alias);

        // Create the Message Object
        return $message;
    }
}
