<?php
/**
 *
 * Created By 皮神
 * Date: 2020/12/23
 */

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Utils\ResponseCode;
use Throwable;

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
     * 抛出异常
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
    public function throwIf(
        bool $condition,
        array $responseCode = ResponseCode::FAIL,
        string $message = ''
    ): void {
        throw_business_if($condition, $responseCode, $message);
    }

    /**
     * Throw invalid param exception if the given condition is true.
     *
     * @param  bool  $condition
     * @param  string  $message
     * @return void
     *
     * @throws Throwable
     */
    public function throwInvalidParamIf(bool $condition, string $message = ''): void
    {
        $this->throwIf($condition, ResponseCode::INVALID_PARAM, $message);
    }

    /**
     * Throw updated failed exception if the given condition is true.
     *
     * @param  bool  $condition
     * @param  string  $message
     * @return void
     *
     * @throws Throwable
     */
    public function throwUpdateFailedIf(bool $condition, string $message = ''): void
    {
        $this->throwIf($condition, ResponseCode::UPDATE_FAILED, $message);
    }
}
