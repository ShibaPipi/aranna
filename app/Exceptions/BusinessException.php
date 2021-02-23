<?php

namespace App\Exceptions;

use Exception;

class BusinessException extends Exception
{
    public function __construct(array $responseCode, string $message = '')
    {
        [$errno, $errmsg] = $responseCode;
        parent::__construct($message ?: $errmsg, $errno);
    }
}
