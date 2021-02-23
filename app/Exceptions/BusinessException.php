<?php
/**
 * 业务统一异常类
 *
 * Created By 皮神
 * Date: 2020/12/23
 */
declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class BusinessException extends Exception
{
    /**
     * BusinessException constructor.
     *
     * @param  array  $responseCode
     * @param  string  $message
     */
    public function __construct(array $responseCode, string $message = '')
    {
        [$errno, $errmsg] = $responseCode;

        parent::__construct($message ?: $errmsg, $errno);
    }
}
