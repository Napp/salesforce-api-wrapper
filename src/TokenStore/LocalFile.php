<?php

namespace Karronoli\Salesforce\TokenStore;

use Karronoli\Salesforce\AccessToken;
use Karronoli\Salesforce\AccessTokenGenerator;

class LocalFile implements StoreInterface
{

    /**
     * @var AccessTokenGenerator
     */
    private $accessTokenGenerator;

    /**
     * @var string
     */
    private $filePath;

    /**
     * @var string
     */
    private $fileName = 'sf-key';

    /**
     * @var LocalFileConfigInterface
     */
    private $config;


    /**
     * @param AccessTokenGenerator     $accessTokenGenerator
     * @param LocalFileConfigInterface $config
     */
    public function __construct(AccessTokenGenerator $accessTokenGenerator, LocalFileConfigInterface $config)
    {
        $this->accessTokenGenerator = $accessTokenGenerator;
        $this->filePath             = $config->getFilePath();
        $this->config               = $config;
    }

    /**
     * @return AccessToken
     * @throws \Exception
     */
    public function fetchAccessToken()
    {
        try {
            $accessTokenJson = file_get_contents($this->filePath . '/' . $this->fileName);
        } catch (\ErrorException $e) {
            throw new \Exception('Salesforce access token not found');
        }

        return $this->accessTokenGenerator->createFromJson($accessTokenJson);
    }

    /**
     * @param AccessToken $accessToken
     */
    public function saveAccessToken(AccessToken $accessToken)
    {
        file_put_contents($this->filePath . '/' . $this->fileName, $accessToken->toJson());
    }
}
