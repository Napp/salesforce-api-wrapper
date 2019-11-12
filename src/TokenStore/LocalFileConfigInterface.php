<?php

namespace Karronoli\Salesforce\TokenStore;

interface LocalFileConfigInterface
{

    /**
     * The path where the file will be stored, no trailing slash, must be writable
     *
     * @return string
     */
    public function getFilePath();
}