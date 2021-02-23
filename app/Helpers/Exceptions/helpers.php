<?php
/**
 * 辅助函数
 *
 * Created By 皮神
 * Date: 2021/2/23
 */
declare(strict_types=1);

use App\Exceptions\BusinessException;
use App\Utils\ResponseCode;

if (!function_exists('throw_business_if')) {
    /**
     * Throw business exception if the given condition is true.
     *
     * @param  bool  $condition
     * @param  array  $responseCode
     * @param  string  $message
     * @return void
     *
     * @throws Throwable
     */
    function throw_business_if(bool $condition, array $responseCode, string $message = ''): void
    {
        throw_if($condition, BusinessException::class, ...[$responseCode, $message]);
    }
}

if (!function_exists('throw_param_validation_if')) {
    /**
     * Throw param validation exception if the given condition is true.
     *
     * @param  bool  $condition
     * @param  mixed  ...$parameters
     * @return void
     *
     * @throws Throwable
     */
    function throw_param_validation_if(bool $condition, ...$parameters): void
    {
        throw_business_if($condition, ResponseCode::PARAM_VALIDATION_ERROR, ...$parameters);
    }
}

if (!function_exists('throw_invalid_param_if')) {
    /**
     * Throw invalid param exception if the given condition is true.
     *
     * @param  bool  $condition
     * @param  mixed  ...$parameters
     * @return void
     *
     * @throws Throwable
     */
    function throw_invalid_param_if(bool $condition, ...$parameters): void
    {
        throw_business_if($condition, ResponseCode::INVALID_PARAM, ...$parameters);
    }
}
