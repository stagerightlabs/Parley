<?php  namespace SRLabs\Parley\Exceptions;

/**
 * This Exception is thrown when a message is submitted with out all the necessary data
 */
class InvalidMessageFormatException extends \Exception {

    /**
     * @param string $message
     * @param int    $errors
     */
    public function __construct($message)
    {
        $this->message = $message;
    }
} 