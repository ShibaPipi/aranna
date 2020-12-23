<?php

namespace App\Exceptions;

use Exception;

class BusinessException extends Exception
{
    public function __construct(array $codeResponse, string $info = '')
    {
        [$errno, $errmsg] = $codeResponse;
        parent::__construct($info ?: $errmsg, $errno);
    }
}
