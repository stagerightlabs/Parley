<?php namespace SRLabs\Parley\Traits;

use ReflectionClass;
use SRLabs\Parley\Models\Thread;

trait Parleyable {

    public function notify($action, $thread)
    {
        $class = $this->getObjectClassName($this);

        $event = 'parley.' . $action . '.for.' . str_replace('\\', '.', $class);

        // Fire a notification event.
        \Event::fire($event, [
            'action' => $action,
            'thread' => $thread,
            'member' => $this
        ]);
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



}


