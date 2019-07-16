<?php

namespace Napp\Salesforce\Tests;

use Carbon\Carbon;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use \Mockery as m;
use Napp\Salesforce\AccessToken;
use Napp\Salesforce\AccessTokenGenerator;
use Napp\Salesforce\Client;
use Napp\Salesforce\ClientConfigInterface;

class ClientTest extends TestCase
{
    protected $requestContainer = [];

    /** @test **/
    public function client_can_be_instantiated()
    {
        $sfClient = new Client($this->getClientConfigMock(), $this->mockGuzzleClient([]), new AccessTokenGenerator);


        $this->assertCount(0, $this->requestContainer);
        $this->assertInstanceOf(Client::class, $sfClient);
    }

    /** @test */
    public function client_will_login()
    {
        $requests = [
            new Response(200, [], '{"id":"https://login.salesforce.com/id/00Dx0000000BV7z/005x00000012Q9P",
"issued_at":"1278448832702","instance_url":"https://yourInstance.salesforce.com/",
"signature":"signature","access_token":
"00Dx0000000BV7z!AR8AQAxo9UfVkh8AlV0Gomt9Czx9LjHnSSpwBMmbRcgKFmxOtvxjTrKW19ye6PE3Ds1eQz3z8jr3W7_VbWmEu4Q8TVGSTHxs","token_type":"Bearer"}')
        ];

        $sfClient = new Client($this->getClientConfigMock(), $this->mockGuzzleClient($requests), new AccessTokenGenerator());
        $sfClient->login('superuser', 'superpasswd');

        $this->assertCount(1, $this->requestContainer);
    }

    /** @test */
    public function client_will_get_record()
    {
        $recordId = 'abc' . rand(1000, 9999999);

        $sfClient = new Client(
            $this->getClientConfigMock(),
            $this->mockGuzzleClient([
                new Response(200, [], json_encode(['foo' => 'bar']))
            ]),
            new AccessTokenGenerator()
        );

        $sfClient->setAccessToken($this->getAccessTokenMock());

        $data = $sfClient->getRecord('Test', $recordId, ['field1', 'field2']);

        $this->assertEquals(['foo' => 'bar'], $data);
    }

    /** @test */
    public function client_can_search()
    {
        $sfClient = new Client(
            $this->getClientConfigMock(),
            $this->mockGuzzleClient([
                new Response(200, [], json_encode(['records' => [], 'done' => true]))
            ]),
            new AccessTokenGenerator()
        );
        $sfClient->setAccessToken($this->getAccessTokenMock());


        $sfClient->search('SELECT Name FROM Lead LIMIT 10');
    }

    /** @test */
    public function client_can_create_record()
    {
        $recordId = 'abc' . rand(1000, 9999999);

        $sfClient = new Client(
            $this->getClientConfigMock(),
            $this->mockGuzzleClient([
                new Response(201, [], json_encode(['id' => $recordId]))
            ]),
            new AccessTokenGenerator()
        );

        $sfClient->setAccessToken($this->getAccessTokenMock());

        $this->assertCount(0, $this->requestContainer);

        $data = $sfClient->createRecord('Test', ['field1', 'field2']);

        $this->assertCount(1, $this->requestContainer);

        $this->assertEquals($recordId, $data);
    }

    /** @test */
    public function client_can_update_record()
    {
        $recordId = 'abc' . rand(1000, 9999999);

        $sfClient = new Client($this->getClientConfigMock(), $this->mockGuzzleClient([
            new Response(204)
        ]), new AccessTokenGenerator());
        $sfClient->setAccessToken($this->getAccessTokenMock());

        $data = $sfClient->updateRecord('Test', $recordId, ['field1' => 'testing', 'field2' => 'testing']);

        $this->assertCount(1, $this->requestContainer);

        $this->assertEquals('api.example.com', $this->requestContainer[0]['request']->getUri()->getHost());
        $this->assertEquals('/services/data/v37.0/sobjects/Test/' . $recordId, $this->requestContainer[0]['request']->getRequestTarget());

        $this->assertEquals('PATCH', $this->requestContainer[0]['request']->getMethod());

        $this->assertEquals([
            'field1' => 'testing',
            'field2' => 'testing',
        ], json_decode($this->requestContainer[0]['request']->getBody()->getContents(), true));

        $this->assertTrue($data);
    }

    /** @test */
    public function client_can_delete_record()
    {
        $recordId = 'abc' . rand(1000, 9999999);

        $sfClient = new Client($this->getClientConfigMock(), $this->mockGuzzleClient([
            new Response(204)
        ]), new AccessTokenGenerator());
        $sfClient->setAccessToken($this->getAccessTokenMock());

        $data = $sfClient->deleteRecord('Test', $recordId);

        $this->assertCount(1, $this->requestContainer);
        $this->assertEquals('/services/data/v37.0/sobjects/Test/' . $recordId, $this->requestContainer[0]['request']->getRequestTarget());
        $this->assertEquals('DELETE', $this->requestContainer[0]['request']->getMethod());
        $this->assertTrue($data);
    }

    /** @test */
    public function client_can_complete_auth_process()
    {
        $sfClient = new Client($this->getClientConfigMock(), $this->mockGuzzleClient([
            new Response(200, [], json_encode([
                'issued_at' => (string)time() . '000',
                'id' => 'some-fake-id',
                'access_token' => 'some-fake-token',
                'instance_url' => 'some-instance',
                'signature' => 'string'
            ]))
        ]), new AccessTokenGenerator());

        $response = $sfClient->authorizeConfirm('authCode', 'redirect');
        $this->assertCount(1, $this->requestContainer);

        $this->assertEquals('/services/oauth2/token', $this->requestContainer[0]['request']->getRequestTarget());
        $this->assertEquals('POST', $this->requestContainer[0]['request']->getMethod());
        $this->assertEquals('login.example.com', $this->requestContainer[0]['request']->getUri()->getHost());

        $this->assertEquals(
            'grant_type=authorization_code&client_id=client_id&client_secret=client_secret&code=authCode&redirect_uri=redirect',
            $this->requestContainer[0]['request']->getBody()->getContents()
        );

        $this->assertNotNull($sfClient->getAccessToken());

        $this->assertInstanceOf(AccessToken::class, $response);
    }

    /** @test */
    public function client_can_complete_token_refresh_process()
    {
        $sfClient = new Client($this->getClientConfigMock(), $this->mockGuzzleClient([
            new Response(200, [], '{
  "id": "https:\/\/login.salesforce.com\/id\/00Dx0000000BV7z\/005x00000012Q9P",
  "issued_at": "1278448384422",
  "instance_url": "https:\/\/yourInstance.salesforce.com\/",
  "signature": "signature",
  "access_token": "00Dx0000000BV7z!AR8AQP0jITN80ESEsj5EbaZTFG0RNBaT1cyWk7TrqoDjoNIWQ2ME_sTZzBjfmOE6zMHq6y8PIW4eWze9JksNEkWUl.Cju7m4",
  "token_type": "Bearer",
  "scope": "id api refresh_token"
}')
        ]), new AccessTokenGenerator());

        $this->assertCount(0, $this->requestContainer);

        $accessToken = AccessToken::fromArray([
            'id' => 'https:\/\/login.salesforce.com\/id\/00Dx0000000BV7z\/005x00000012Q9P',
            'dateIssued' => $now = Carbon::now(),
            'dateExpires' => $now->copy()->addHour(1)->subMinutes(5),
            'scope' => ['id', 'api', 'refresh_token'],
            'refreshToken' => 'refresh_token',
            'signature' => 'signature',
            'accessToken' => 'access_token',
            'apiUrl' => 'api.salesforce.com'
        ]);

        $sfClient->setAccessToken($accessToken);

        $oldAccessToken = $sfClient->getAccessToken()->accessToken();
        $response = $sfClient->refreshToken();

        $this->assertNotSame($oldAccessToken, $sfClient->getAccessToken()->accessToken());

        $this->assertCount(1, $this->requestContainer);
        $this->assertEquals('POST', $this->requestContainer[0]['request']->getMethod());
        $this->assertEquals(
            'grant_type=refresh_token&client_id=client_id&client_secret=client_secret&refresh_token=refresh_token',
            $this->requestContainer[0]['request']->getBody()->getContents()
        );

        $this->assertEquals('login.example.com', $this->requestContainer[0]['request']->getUri()->getHost());

        $this->assertInstanceOf(AccessToken::class, $response);
    }

    /** @test */
    public function client_can_get_login_url()
    {
        $sfClient = new Client($this->getClientConfigMock(), $this->mockGuzzleClient([]), new AccessTokenGenerator());
        $sfClient->setAccessToken($this->getAccessTokenMock());

        $url = $sfClient->getLoginUrl('https://example.dk/redirect');

        $this->assertCount(0, $this->requestContainer);

        $this->assertEquals('http://login.example.com/services/oauth2/authorize?client_id=client_id&redirect_uri=https%3A%2F%2Fexample.dk%2Fredirect&response_type=code&grant_type=authorization_code', $url);
    }

    /**
     * @test
     * @expectedException        Napp\Salesforce\Exceptions\RequestException
     * @expectedExceptionMessage expired authorization code
     */
    public function client_can_parse_auth_flow_error()
    {
        $sfClient = new Client($this->getClientConfigMock(), $this->mockGuzzleClient([
            new Response(400, [], '{"error_description":"expired authorization code","error":"invalid_grant"}')
        ]), new AccessTokenGenerator);

        //Try the auth flow - this should generate an exception
        $sfClient->authorizeConfirm('authCode', 'redirect error');
    }

    public function mockGuzzleClient(array $requests = [])
    {
        $mock = new MockHandler($requests);
        $stack = HandlerStack::create($mock);
        $history = Middleware::history($this->requestContainer);
        $stack->push($history);

        return new \GuzzleHttp\Client(['handler' => $stack ]);
    }


    /**
     * Mock the client config interface
     * @return m\MockInterface
     */
    private function getClientConfigMock()
    {
        $config = m::mock(ClientConfigInterface::class);
        $config->shouldReceive('getLoginUrl')->andReturn('http://login.example.com');
        $config->shouldReceive('getClientId')->andReturn('client_id');
        $config->shouldReceive('getClientSecret')->andReturn('client_secret');
        $config->shouldReceive('getVersion')->andReturn('v37.0');
        return $config;
    }

    private function getAccessTokenMock()
    {
        $accessToken = m::mock(AccessToken::class);
        $accessToken->shouldReceive('apiUrl')->andReturn('http://api.example.com');
        $accessToken->shouldReceive('accessToken')->andReturn('123456789abcdefghijk');
        $accessToken->shouldReceive('refreshToken')->andReturn('refresh123456789abcdefghijk');
        return $accessToken;
    }
}
