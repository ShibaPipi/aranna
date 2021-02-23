<?php
/**
 * 辅助函数
 *
 * Created By 皮神
 * Date: 2021/2/23
 */

use App\Exceptions\BusinessException;

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
