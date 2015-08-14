<?php

namespace Parley;

use Parley\Exceptions\InvalidMessageFormatException;
use Parley\Exceptions\NonParleyableMemberException;
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
     * Create a new messageData thread, with an optional object reference
     *
     * @param array $messageData
     * @param  null $object
     * @return static
     * @throws InvalidMessageFormatException
     * @throws NonParleyableMemberException
     */
    public function discuss(array $messageData, $object = null)
    {
        // Make sure we have a subject parameter
        if (! array_key_exists('subject', $messageData)) {
            throw new InvalidMessageFormatException("Missing subject from message data attributes");
        }

        // Make sure we have an author parameter
        if (! array_key_exists('author', $messageData)) {
            throw new InvalidMessageFormatException('You must specify an author for this Parley Thread');
        }

        // Make sure the author object implements the ParleyableInterface
        $this->confirmObjectIsParleyable($messageData['author']);

        // Create a new Parley Thread with its first Message
        $thread = Thread::create(['subject' => e($messageData['subject'])]);
        $thread->setInitialMessage($messageData);

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
        return Thread::find($id);
    }
}
