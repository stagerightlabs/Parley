<?php 

namespace Parley\Exceptions;

class InvalidMessageFormatException extends \Exception
{
    /**
     * This Exception is thrown when a message is submitted with out all the necessary data
     *
     * @param string $message
     */
    public function __construct($message)
    {
        $this->message = $message;
    }
}
