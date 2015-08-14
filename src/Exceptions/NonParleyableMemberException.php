<?php

namespace Parley\Exceptions;

class NonParleyableMemberException extends \Exception
{
    /**
     * An effort was made to associate an object with a parley thread, but that object is not a valid member.
     *
     * @param string $message
     */
    public function __construct($message)
    {
        $this->message = $message;
    }
}

