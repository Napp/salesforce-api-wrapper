<?php

namespace Karronoli\Salesforce;

use Carbon\Carbon;

class AccessTokenGenerator implements AccessTokenGeneratorInterface
{

    /**
     * Create an access token from stored json data
     *
     * @param $text
     * @return AccessToken
     */
    public function createFromJson(string $text): AccessToken
    {
        $savedToken = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid json encountered');
        }

        return AccessToken::fromArray($savedToken);
    }

    /**
     * Create an access token object from the salesforce response data
     *
     * @param array $response
     * @return AccessToken
     */
    public function createFromSalesforceResponse(array $response): AccessToken
    {
        if (is_int($response['issued_at'])) {
            $dateIssued = Carbon::createFromTimestamp($response['issued_at']);
        } else {
            $dateIssued = new Carbon('now');
        }

        $dateExpires = $dateIssued->copy()->addHour()->subMinutes(5);

        $tokenId = $this->getKeyIfSet($response, 'id');

        $scope = explode(' ', $this->getKeyIfSet($response, 'scope'));

        $refreshToken = $this->getKeyIfSet($response, 'refresh_token');

        $signature = $this->getKeyIfSet($response, 'signature');

        $accessToken = $this->getKeyIfSet($response, 'access_token');

        $apiUrl = $this->getKeyIfSet($response, 'instance_url');

        return new AccessToken(
            $tokenId,
            $dateIssued,
            $dateExpires,
            $scope,
            $refreshToken,
            $signature,
            $accessToken,
            $apiUrl
        );
    }

    /**
     * @param array $array
     * @param mixed $key
     * @return null
     */
    private function getKeyIfSet($array, $key)
    {
        return $array[$key] ?? '';
    }
}
