<?php
/**
 *
 * Created By 皮神
 * Date: 2020/12/23
 */

namespace App\Services;

use App\CodeResponse;
use App\Exceptions\BusinessException;

class BaseService
{
    protected static $instance;

    /**
     * 防止类被外部实例化
     */
    private function __construct()
    {
    }

    /**
     * 防止类被外部克隆
     */
    private function __clone()
    {
    }

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (static::$instance instanceof static) {
            return static::$instance;
        }
        static::$instance = new static;

        return static::$instance;
    }

    /**
     * 抛出异常，默认为非法参数异常
     *
     * @param  array  $codeResponse
     * @param  string  $message
     * @return void
     *
     * @throws BusinessException
     */
    public function throwBusinessException(
        array $codeResponse = CodeResponse::INVALID_PARAM,
        string $message = ''
    ): void {
        throw new BusinessException($codeResponse, $message);
    }

    /**
     * 抛出非法参数值异常
     *
     * @param  string  $message
     * @return void
     *
     * @throws BusinessException
     */
    public function throwInvalidParamValueException(string $message = ''): void
    {
        $this->throwBusinessException(CodeResponse::INVALID_PARAM_VALUE, $message);
    }
}
