<?php

namespace Karronoli\Salesforce\Exceptions;

class AuthenticationException extends \Exception {

    /**
     * @var string
     */
    private $errorCode;

    /**
     * @param string $errorCode
     * @param string $message
     * @param \Throwable|null $previous
     */
    public function __construct($errorCode, $message, \Throwable $previous = null)
    {
        $this->errorCode = $errorCode;

        parent::__construct($message, 0, $previous);
    }

    /**
     * @return string
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }
}