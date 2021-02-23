<?php
/**
 *
 * Created By 皮神
 * Date: 2020/12/23
 */

namespace App\Services;

use App\Utils\ResponseCode;
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
     * @param  array  $responseCode
     * @param  string  $message
     * @return void
     *
     * @throws BusinessException
     */
    public function throwBusinessException(
        array $responseCode = ResponseCode::INVALID_PARAM,
        string $message = ''
    ): void {
        throw new BusinessException($responseCode, $message);
    }

    /**
     * 抛出非法参数异常
     *
     * @param  string  $message
     * @return void
     *
     * @throws BusinessException
     */
    public function throwInvalidParamException(string $message = ''): void
    {
        $this->throwBusinessException(ResponseCode::INVALID_PARAM, $message);
    }

    /**
     * 抛出数据更新失败异常
     *
     * @param  string  $message
     * @return void
     *
     * @throws BusinessException
     */
    public function throwUpdateFailedException(string $message = ''): void
    {
        $this->throwBusinessException(ResponseCode::UPDATE_FAILED, $message);
    }
}
