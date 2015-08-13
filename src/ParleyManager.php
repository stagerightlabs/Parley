<?php

namespace Parley;

use Parley\Exceptions\InvalidMessageFormatException;
use Parley\Exceptions\NonReferableObjectException;
use Parley\Support\ParleySelector;
use Parley\Traits\ParleyHelpersTrait;
use ReflectionClass;
use Parley\Models\Thread;
use Parley\Support\Selector;

class ParleyManager
{
    use ParleyHelpersTrait;

    /**
     * Create a new message thread, with an optional object reference
     *
     * @param array $message
     * @param  null $object
     * @return static
     * @throws InvalidMessageFormatException
     * @throws NonReferableObjectException
     */
    public function discuss(array $message, $object = null)
    {
        // Create a new Parley Thread
        $thread = Thread::create(['subject' => e($subject)]);
        $thread->hash = \Hashids::encode($thread->id);
        $thread->initialMessage($message);
        $thread->save();

        // Set the reference object, if one has been assigned
        if ($object) {
            $thread->setReferenceObject($object);
        }

        return $thread;
    }

    /**
     * Gather Threads for a group of objects
     *
     * @param mixed $members
     *
     * @return ParleySelector
     */
    public function gatherFor($members)
    {
        $members = $this->ensureArrayable($members);

        return new ParleySelector($members);
    }

    /**
     * Get a thread by its hash value
     * @param string|int $id
     *
     * @return mixed
     */
    public function getThread($id)
    {
        if (is_string($id)) {
            $id = \Hashids::decode($id)[0];
        }

        return Thread::find($id);
    }
}
