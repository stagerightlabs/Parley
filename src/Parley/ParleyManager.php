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

    public function gather($options = null)
    {
        $data['type'] = 'any';

        if (is_array($options))
        {
            foreach ($options as $key => $value)
            {
                $data[$key] = $value;
            }
        }

        return new Selector($data);
    }

    public function gatherOpen()
    {
        return $this->gather(['type' => 'open']);
    }

    public function gatherClosed()
    {
        return $this->gather(['type' => 'closed']);
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