<?php namespace SRLabs\Parley;

use ReflectionClass;
use SRLabs\Parley\Exceptions\ParleyRetrievalException;
use SRLabs\Parley\Models\Thread;
use SRLabs\Parley\Support\Collection;
use SRLabs\Parley\Support\Selector;


class ParleyManager {

    /**
     * Create a new message thread, with an optional object reference
     *
     * @param      string $subject
     * @param null $object
     */
    public function discuss($subject, $object = null)
    {
        $thread = Thread::create(['subject' => $subject]);

        if ($object)
        {
            $this->confirmObjectHasId($object);

            $thread->object_id = $object->id;
            $thread->object_type = $this->getObjectClassName($object);
            $thread->save();
        }

        return $thread;
    }

    public function gather($level)
    {
        if ( ! in_array($level, ['all', 'open', 'closed']))
        {
            throw new ParleyRetrievalException("$level is not a valid retrieval option");
        }

        $data['level'] = $level;
        $data['trashed'] = false;

        return new Selector($data);
    }


    protected function getObjectClassName( $object )
    {
        // Reflect on the Object
        $reflector = new ReflectionClass( $object );

        // Return the class name
        return $reflector->getName();
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