<?php

namespace Karronoli\Salesforce\Tests;

use \Mockery as m;

class LocalFileStoreTest extends TestCase
{
    /** @test */
    public function file_store_can_be_instantiated()
    {
        $tokenGenerator = m::mock('Karronoli\Salesforce\AccessTokenGenerator');
        $config = m::mock('Karronoli\Salesforce\TokenStore\LocalFileConfigInterface');
        $config->shouldReceive('getFilePath')->once()->andReturn('/foo');
        $fileStore = new \Karronoli\Salesforce\TokenStore\LocalFile($tokenGenerator, $config);

        $this->assertInstanceOf(\Karronoli\Salesforce\TokenStore\LocalFile::class, $fileStore);
    }
}
