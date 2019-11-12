<?php

namespace Karronoli\Salesforce;

class ClientConfig implements ClientConfigInterface
{
    /**
     * @var string
     */
    private $loginUrl;

    /**
     * @var string
     */
    private $clientId;
    /**
     * @var string
     */
    private $clientSecret;

    /**
     * @var string
     */
    private $version;

    /**
     * ClientConfig constructor.
     * @param string $loginUrl
     * @param string $clientId
     * @param string $clientSecret
     * @param string $version
     */
    public function __construct(
        string $loginUrl,
        string $clientId,
        string $clientSecret,
        string $version = 'v37.0'
    ) {
        $this->loginUrl     = $loginUrl;
        $this->clientId     = $clientId;
        $this->clientSecret = $clientSecret;
        $this->version      = $version;
    }

    /**
     * @return string
     */
    public function getLoginUrl(): string
    {
        return $this->loginUrl;
    }

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * @return string
     */
    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    /**
     * Getter for version
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @return bool
     */
    public function isFullyConfigured(): bool
    {
        return count(array_filter([
            $this->version,
            $this->clientId,
            $this->clientSecret,
            $this->loginUrl
        ], function ($value) {
            return empty($value);
        })) === 0;
    }
}
