<?php

namespace App\Exceptions;

use RuntimeException;

class PrizeUnavailableException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The selected prize is no longer available.');
    }
}
