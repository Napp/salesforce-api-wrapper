<?php

namespace Napp\Salesforce\Exceptions;

use Throwable;

class RequestException extends \Exception
{

    /**
     * @var string
     */
    protected $errorCode;

    /**
     * @var string
     */
    private $requestBody;


    public static function withResponseError($data, Throwable $previous, int $code = 0): self
    {
        $self = new self('', $code, $previous);

        $self->requestBody = $data;

        //Errors generated during the auth stage are different to those generated during normal requests
        if (isset($data['error'], $data['error_description'])) {
            $self->errorCode = $data['error'];
            $self->message = $data['error_description'];
        } elseif (isset($data[0]['message'], $data[0]['errorCode'])) {
            $self->errorCode = $data[0]['errorCode'];
            $self->message = $data[0]['message'];
        } else {
            $self->errorCode = $data['errorCode'] ?? 0;
            $self->message = $data['message'] ?? 'Unknown error';
        }

        return $self;
    }

    public static function withoutResponseError($message, Throwable $previous, int $code = 0): self
    {
        $self = new self($message, $code, $previous);
        $self->errorCode = 500;

        return $self;
    }

    /**
     * @return mixed
     */
    public function getRequestBody()
    {
        return $this->requestBody;
    }

    /**
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}