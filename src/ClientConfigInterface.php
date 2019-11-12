<?php

namespace Karronoli\Salesforce;

/**
 * Interface ClientConfigInterface
 * @package Karronoli\Salesforce
 */
interface ClientConfigInterface
{
    /**
     * @return string
     */
    public function getLoginUrl(): string;

    /**
     * @return string
     */
    public function getClientId(): string;

    /**
     * @return string
     */
    public function getClientSecret(): string;

    /**
     * Version of the API
     * @return string
     */
    public function getVersion(): string;

    /**
     * @return bool
     */
    public function isFullyConfigured(): bool;
}
