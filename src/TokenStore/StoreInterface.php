<?php

namespace Napp\Salesforce\TokenStore;

use Napp\Salesforce\AccessToken;

interface StoreInterface
{

    /**
     * Fetch the access token from the chosen storage medium and return
     *
     * @return AccessToken
     */
    public function fetchAccessToken();

    /**
     * Save an access token in the chosen store
     *
     * @param AccessToken $accessToken
     */
    public function saveAccessToken(AccessToken $accessToken);
}