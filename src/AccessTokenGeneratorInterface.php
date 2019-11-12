<?php

namespace Karronoli\Salesforce;

interface AccessTokenGeneratorInterface
{

    /**
     * Create an access token from stored json data
     *
     * @param $text
     * @return AccessToken
     */
    public function createFromJson(string $text): AccessToken;

    /**
     * Create an access token object from the salesforce response data
     *
     * @param array $response
     * @return AccessToken
     */
    public function createFromSalesforceResponse(array $response): AccessToken;
}
