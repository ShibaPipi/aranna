<?php
/**
 * 辅助函数
 *
 * Created By 皮神
 * Date: 2021/2/23
 */

use App\Exceptions\BusinessException;
use App\Utils\ResponseCode;

if (!function_exists('throwBusinessException_if')) {
    /**
     * Throw business exception if the given condition is true.
     *
     * @param $condition
     * @param  mixed  ...$parameters
     *
     * @throws Throwable
     */
    function throwBusinessException_if($condition, ...$parameters)
    {
        throw_if($condition, BusinessException::class, ...$parameters);
    }
}

if (!function_exists('throwParamValidationException_if')) {
    /**
     * 抛出表单验证错误异常
     *
     * @param $condition
     * @param  mixed  ...$parameters
     *
     * @throws Throwable
     */
    function throwParamValidationException_if($condition, ...$parameters)
    {
        throwBusinessException_if($condition, ResponseCode::PARAM_VALIDATION_ERROR, ...$parameters);
    }
}

if (!function_exists('throwInvalidParamException_if')) {
    /**
     * 抛出非法参数异常
     *
     * @param $condition
     * @param  mixed  ...$parameters
     *
     * @throws Throwable
     */
    function throwInvalidParamException_if($condition, ...$parameters)
    {
        throwBusinessException_if($condition, ResponseCode::INVALID_PARAM, ...$parameters);
    }
}
