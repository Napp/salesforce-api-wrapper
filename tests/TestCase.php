<?php

namespace Karronoli\Salesforce\Tests;

use Carbon\Carbon;
use \Mockery as m;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    public function tearDown()
    {
        if ($container = m::getContainer()) {
            $this->addToAssertionCount($container->mockery_getExpectationCount());
        }
        m::close();

        if (Carbon::hasTestNow()) {
            Carbon::setTestNow(null);
        }
    }
}
