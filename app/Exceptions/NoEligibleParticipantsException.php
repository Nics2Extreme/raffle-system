<?php

namespace App\Exceptions;

use Exception;

class NoEligibleParticipantsException extends Exception
{
    public function __construct()
    {
        parent::__construct('There are no eligible participants left to draw.');
    }
}
