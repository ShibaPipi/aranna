<?php

namespace App\Exceptions;

use Exception;

class BusinessException extends Exception
{
    public function __construct(array $codeResponse, string $message = '')
    {
        [$errno, $errmsg] = $codeResponse;
        parent::__construct($message ?: $errmsg, $errno);
    }
}
