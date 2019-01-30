<?php

namespace Napp\Salesforce\Tests;

use \Mockery as m;

class LocalFileStoreTest extends TestCase
{
    /** @test */
    public function file_store_can_be_instantiated()
    {
        $tokenGenerator = m::mock('Napp\Salesforce\AccessTokenGenerator');
        $config = m::mock('Napp\Salesforce\TokenStore\LocalFileConfigInterface');
        $config->shouldReceive('getFilePath')->once()->andReturn('/foo');
        $fileStore = new \Napp\Salesforce\TokenStore\LocalFile($tokenGenerator, $config);

        $this->assertInstanceOf(\Napp\Salesforce\TokenStore\LocalFile::class, $fileStore);
    }
}
