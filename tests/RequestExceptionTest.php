<?php

namespace Napp\Salesforce\Tests;

class RequestExceptionTest extends TestCase {

    /** @test */
    public function without_response_error()
    {
        $message = 'abc123';
        $code = 1;
        $previous = new \Exception('hello');
        $e = \Napp\Salesforce\Exceptions\RequestException::withoutResponseError(
            $message,
            $previous,
            $code
        );

        $this->assertInstanceOf(\Napp\Salesforce\Exceptions\RequestException::class, $e);
        $this->assertSame($message, $e->getMessage());
        $this->assertSame($code, $e->getCode());
        $this->assertSame($previous, $e->getPrevious());
    }
}
