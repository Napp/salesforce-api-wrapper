<?php

namespace Karronoli\Salesforce;

use GuzzleHttp\Exception\GuzzleException;
use Karronoli\Salesforce\Exceptions\AuthenticationException;
use Karronoli\Salesforce\Exceptions\RequestException;

/**
 * Class Client
 * @package Karronoli\Salesforce
 */
class Client
{
    /**
     * @var ClientConfigInterface
     */
    private $clientConfig;

    /**
     * @var AccessToken|null
     */
    private $accessToken;

    /**
     * @var string|null
     */
    private $baseUrl;

    /**
     * @var \GuzzleHttp\Client
     */
    private $guzzleClient;

    /**
     * @var \Karronoli\Salesforce\AccessTokenGenerator
     */
    private $tokenGenerator;


    /**
     * Create a sf client using a client config object or an array of params
     *
     * @param ClientConfigInterface $clientConfig
     * @param \GuzzleHttp\ClientInterface $guzzleClient
     * @param \Karronoli\Salesforce\AccessTokenGeneratorInterface $accessTokenGenerator
     */
    public function __construct(
        ClientConfigInterface $clientConfig,
        \GuzzleHttp\ClientInterface $guzzleClient,
        AccessTokenGeneratorInterface $accessTokenGenerator
    ) {
        $this->clientConfig = $clientConfig;
        $this->guzzleClient = $guzzleClient;
        $this->tokenGenerator = $accessTokenGenerator;
    }

    /**
     * Create an instance of the salesforce client using the passed in config data
     *
     * @param string $salesforceLoginUrl
     * @param string $clientId
     * @param string $clientSecret
     * @param string $version
     *
     * @return Client
     */
    public static function create(
        string $salesforceLoginUrl,
        string $clientId,
        string $clientSecret,
        string $version = 'v37'
    ): Client {
        return new self(
            new ClientConfig($salesforceLoginUrl, $clientId, $clientSecret, $version),
            new \GuzzleHttp\Client,
            new AccessTokenGenerator
        );
    }

    /**
     * Log the user using the credential if known in advance
     *
     * Only use when not needing the OAuth usual flow.
     *
     * @param string $user
     * @param string $password
     *
     * @return AccessToken
     * @throws \Karronoli\Salesforce\Exceptions\AuthenticationException
     * @throws \Karronoli\Salesforce\Exceptions\RequestException
     */
    public function login(string $user, string $password): AccessToken
    {
        $response = $this->makeRequest('post', rtrim($this->clientConfig->getLoginUrl(), '/') . '/services/oauth2/token', [
            'headers'     => ['Accept' => 'application/json'],
            'form_params' => [
                'client_id'     => $this->clientConfig->getClientId(),
                'client_secret' => $this->clientConfig->getClientSecret(),
                'grant_type'    => 'password',
                'username'      => $user,
                'password'      => $password,
            ],
        ]);

        $this->setAccessToken(
            $accessToken = $this->tokenGenerator->createFromSalesforceResponse($response)
        );

        return $accessToken;
    }


    /**
     * Fetch a specific object
     *
     * @param string $objectType
     * @param string $id
     * @param array $fields
     *
     * @return array
     * @throws \Karronoli\Salesforce\Exceptions\AuthenticationException
     * @throws \Karronoli\Salesforce\Exceptions\RequestException
     */
    public function getRecord(string $objectType, string $id, array $fields = []): array
    {
        $fieldsQuery = '';

        if ([] !== $fields) {
            $fieldsQuery = '?fields=' . implode(',', $fields);
        }

        return $this->makeRequest(
            'get',
            $this->generateUrl('sobjects/' . $objectType . '/' . $id . $fieldsQuery),
            [
                'headers' => [
                    'Authorization' => $this->getAuthHeader()
                ]
            ]
        );
    }

    /**
     * Execute an SOQL query and return the result set
     * This will loop through large result sets collecting all the data so the query should be limited
     *
     * @param string|null $query
     * @param string|null $nextUrl
     * @return array
     * @throws \Exception
     */
    public function search($query = null, $nextUrl = null): array
    {
        if (null !== $nextUrl) {
            $url = $this->baseUrl . '/' . $nextUrl;
        } else {
            $url = $this->generateUrl('query/?q=' . urlencode($query));
        }

        $data = $this->makeRequest('get', $url, ['headers' => ['Authorization' => $this->getAuthHeader()]]);

        $results = $data['records'] ?? [];
        $done = $data['done'] ?? null;

        $nextRecordsUrl = $data['nextRecordsUrl'] ?? '';

        if (!$done) {
            $moreResults = $this->search(null, substr($nextRecordsUrl, 1));

            if ([] !== $moreResults) {
                $results = array_merge($results, $moreResults);
            }
        }

        return $results;
    }


    /**
     * Make an update request
     *
     * @param string $type The object type to update
     * @param string $objectId The ID of the record to update
     * @param array $data The data to put into the record
     * @return boolean
     *
     * @throws \Karronoli\Salesforce\Exceptions\AuthenticationException
     * @throws \Karronoli\Salesforce\Exceptions\RequestException
     */
    public function updateRecord(string $type, string $objectId, array $data): bool
    {
        $url = $this->generateUrl('sobjects/' . $type . '/' . $objectId);

        $this->makeRequest('patch', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => $this->getAuthHeader()
            ],
            'body'    => json_encode($data)
        ]);

        return true;
    }

    /**
     * Create a new object in salesforce
     *
     * @param string $type
     * @param array|object $data
     * @return string The id of the newly created record
     * @throws \Karronoli\Salesforce\Exceptions\AuthenticationException
     * @throws \Karronoli\Salesforce\Exceptions\RequestException
     */
    public function createRecord(string $type, array $data): string
    {
        $url = $this->generateUrl('sobjects/' . $type);

        $response = $this->makeRequest(
            'post',
            $url,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => $this->getAuthHeader()
                ],
                'body'    => json_encode($data)
            ]
        );

        return $response['id'];
    }

    /**
     * Delete an object with th specified id
     *
     * @param string $type
     * @param string $objectId
     * @return bool
     * @throws \Karronoli\Salesforce\Exceptions\AuthenticationException
     * @throws \Karronoli\Salesforce\Exceptions\RequestException
     */
    public function deleteRecord(string $type, string $objectId): bool
    {
        $url = $this->generateUrl('sobjects/' . $type . '/' . $objectId);

        $this->makeRequest('delete', $url, ['headers' => ['Authorization' => $this->getAuthHeader()]]);

        return true;
    }

    /**
     * Complete the oauth process by confirming the code and returning an access token
     *
     * @param string $code
     * @param string $redirectUrl
     * @return AccessToken
     * @throws \Karronoli\Salesforce\Exceptions\AuthenticationException
     * @throws \Karronoli\Salesforce\Exceptions\RequestException
     */
    public function authorizeConfirm(string $code, string $redirectUrl): AccessToken
    {
        $url = rtrim($this->clientConfig->getLoginUrl(), '/') . '/services/oauth2/token';

        $data = [
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->clientConfig->getClientId(),
            'client_secret' => $this->clientConfig->getClientSecret(),
            'code'          => $code,
            'redirect_uri'  => $redirectUrl
        ];

        $response = $this->makeRequest('post', $url, ['form_params' => $data]);

        $accessToken = $this->tokenGenerator->createFromSalesforceResponse($response);

        $this->setAccessToken(
            $accessToken
        );

        return $accessToken;
    }

    /**
     * @return AccessToken|null
     */
    public function getAccessToken(): ?AccessToken
    {
        return $this->accessToken;
    }

    /**
     * Get the url to redirect users to when setting up a salesforce access token
     *
     * @param string $redirectUrl
     * @param string|null $state
     * @param bool $reauthorize
     * @return string
     */
    public function getLoginUrl(string $redirectUrl, ?string $state = null, bool $reauthorize = false): string
    {
        $params = [
            'client_id'     => $this->clientConfig->getClientId(),
            'redirect_uri'  => $redirectUrl,
            'response_type' => 'code',
            'grant_type'    => 'authorization_code'
        ];

        if (null !== $state || '' !== $state) {
            $params['state'] = $state;
        }

        if (true === $reauthorize) {
            $params['prompt'] = 'login consent';
        }

        return rtrim($this->clientConfig->getLoginUrl(), '/') . '/services/oauth2/authorize?' .
            http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Refresh an existing access token
     *
     * @return AccessToken
     * @throws \Exception
     */
    public function refreshToken(): AccessToken
    {
        $url = rtrim($this->clientConfig->getLoginUrl(), '/') . '/services/oauth2/token';

        $data = [
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->clientConfig->getClientId(),
            'client_secret' => $this->clientConfig->getClientSecret(),
            'refresh_token' => $this->accessToken->refreshToken()
        ];

        $response = $this->makeRequest(
            'post',
            $url,
            ['form_params' => $data]
        );

        $accessToken = $this->accessToken->refresh($response);
        $this->setAccessToken($accessToken);

        return $accessToken;
    }

    /**
     * @param AccessToken $accessToken
     * @return self
     */
    public function setAccessToken(AccessToken $accessToken): self
    {
        $this->accessToken = $accessToken;
        $this->baseUrl     = $accessToken->apiUrl();

        return $this;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array  $data
     * @return mixed
     * @throws AuthenticationException
     * @throws RequestException
     */
    private function makeRequest($method, $url, $data)
    {
        try {
            $response = $this->guzzleClient->request($method, $url, $data);

            if (204 === $response->getStatusCode()) {
                return [];
            }

            return \GuzzleHttp\json_decode($response->getBody(), true);
        } catch (GuzzleException $e) {
            if (false === $e->hasResponse()) {
                throw RequestException::withoutResponseError($e->getMessage(), $e, $e->getCode());
            }

            $responseError = json_decode($e->getResponse()->getBody(), true);

            if (401 === $e->getResponse()->getStatusCode()) {
                throw new AuthenticationException(
                    $responseError[0]['errorCode'] ?? null,
                    $responseError[0]['message'] ?? 'Unauthorized',
                    $e->getPrevious()
                );
            }

            throw RequestException::withResponseError($responseError, $e);
        }
    }

    /**
     * @return string
     * @throws AuthenticationException
     */
    private function getAuthHeader(): string
    {
        if ($this->accessToken === null) {
            throw new AuthenticationException(0, 'Access token not set');
        }

        return 'Bearer ' . $this->accessToken->accessToken();
    }

    /**
     * Generate the call URL
     * @param string $append
     *
     * @return string
     */
    private function generateUrl(string $append): string
    {
        return $this->baseUrl . '/services/data/' . $this->clientConfig->getVersion() . '/' . $append;
    }

    /**
     * @return bool
     */
    public function isConfigured(): bool
    {
        return $this->clientConfig->isFullyConfigured();
    }
}
