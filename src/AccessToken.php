<?php

namespace Napp\Salesforce;

use Carbon\Carbon;

class AccessToken
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var \Carbon\Carbon
     */
    private $dateIssued;

    /**
     * @var \Carbon\Carbon
     */
    private $dateExpires;

    /**
     * @var array
     */
    private $scope;

    /**
     * @var string
     */
    private $refreshToken;

    /**
     * @var string
     */
    private $signature;

    /**
     * @var string
     */
    private $accessToken;

    /**
     * @var string
     */
    private $apiUrl;

    /**
     * @param string $tokenId
     * @param \Carbon\Carbon $dateIssued
     * @param \Carbon\Carbon $dateExpires
     * @param array $scope
     * @param string $refreshToken
     * @param string $signature
     * @param string $accessToken
     * @param string $apiUrl
     */
    public function __construct(
        string $tokenId,
        Carbon $dateIssued,
        Carbon $dateExpires,
        ?array $scope,
        ?string $refreshToken,
        string $signature,
        string $accessToken,
        string $apiUrl
    ) {
        $this->id = $tokenId;
        $this->dateIssued = $dateIssued;
        $this->dateExpires = $dateExpires;
        $this->scope = $scope;
        $this->refreshToken = $refreshToken;
        $this->signature = $signature;
        $this->accessToken = $accessToken;
        $this->apiUrl = $apiUrl;
    }


    /**
     * @param array $response
     * @return \Napp\Salesforce\AccessToken
     */
    public function refresh(array $response): self
    {
        $this->dateIssued = Carbon::createFromTimestampMs($response['issued_at']);

        $this->dateExpires = $this->dateIssued->copy()->addHour()->subMinutes(5);

        $this->signature = $response['signature'] ?? '';

        $this->accessToken = $response['access_token'] ?? '';

        return $this;
    }

    /**
     * @return bool
     */
    public function needsRefresh(): bool
    {
        return $this->dateExpires->lt(Carbon::now());
    }


    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'dateIssued' => $this->dateIssued->format('Y-m-d H:i:s'),
            'dateExpires' => $this->dateExpires->format('Y-m-d H:i:s'),
            'scope' => $this->scope,
            'refreshToken' => $this->refreshToken,
            'signature' => $this->signature,
            'accessToken' => $this->accessToken,
            'apiUrl' => $this->apiUrl,
        ];
    }

    /**
     * @param int $options
     * @return string
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * @param array $array
     * @return \Napp\Salesforce\AccessToken
     */
    public static function fromArray(array $array): self
    {
        return new self(
            $array['id'] ?? '',
            Carbon::parse($array['dateIssued']),
            Carbon::parse($array['dateExpires']),
            $array['scope'] ?? [],
            $array['refreshToken'] ?? '',
            $array['signature'] ?? '',
            $array['accessToken'] ?? '',
            $array['apiUrl'] ?? ''
        );
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * @return Carbon
     */
    public function dateExpires(): Carbon
    {
        return $this->dateExpires;
    }

    /**
     * @return Carbon
     */
    public function dateIssued(): Carbon
    {
        return $this->dateIssued;
    }

    /**
     * @return null|string
     */
    public function refreshToken(): string
    {
        return $this->refreshToken;
    }

    /**
     * @return string
     */
    public function accessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * @return array
     */
    public function scopes(): array
    {
        return $this->scope;
    }

    /**
     * @return string
     */
    public function apiUrl(): string
    {
        return $this->apiUrl;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function signature(): string
    {
        return $this->signature;
    }
}
