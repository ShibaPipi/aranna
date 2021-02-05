<?php
/**
 *
 * Created By 皮神
 * Date: 2021/1/15
 */
declare(strict_types=1);

namespace App;

use App\Exceptions\BusinessException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

trait VerifyRequestInput
{
    /**
     * 验证是否为 id
     *
     * @param  string  $key
     * @param  null  $default
     * @return int|null
     *
     * @throws BusinessException
     */
    public function verifyId(string $key, $default = null): ?int
    {
        return $this->verifyData($key, $default, 'integer|digits_between:1,20|min:1', 'intval');
    }

    /**
     * 验证是否为整型
     *
     * @param  string  $key
     * @param  null  $default
     * @return int|null
     *
     * @throws BusinessException
     */
    public function verifyInteger(string $key, $default = null): ?int
    {
        return $this->verifyData($key, $default, 'integer', 'intval');
    }

    /**
     * 验证是否为正整型
     *
     * @param  string  $key
     * @param  null  $default
     * @return int|null
     *
     * @throws BusinessException
     */
    public function verifyPositiveInteger(string $key, $default = null): ?int
    {
        return $this->verifyData($key, $default, 'integer|min:1', 'intval');
    }

    /**
     * 验证是否为字符串
     *
     * @param  string  $key
     * @param  null  $default
     * @return string|null
     *
     * @throws BusinessException
     */
    public function verifyString(string $key, $default = null): ?string
    {
        return $this->verifyData($key, $default, 'string');
    }

    /**
     * 验证布尔值
     *
     * @param  string  $key
     * @param  null  $default
     * @return int|null
     *
     * @throws BusinessException
     */
    public function verifyBoolean(string $key, $default = null): ?int
    {
        return $this->verifyData($key, $default, 'boolean', 'intval');
    }

    /**
     * 验证是值否在数组中
     *
     * @param  string  $key
     * @param  null  $default
     * @param  array  $enum
     * @return mixed|null
     *
     * @throws BusinessException
     */
    public function verifyEnum(string $key, $default = null, array $enum = [])
    {
        return $this->verifyData($key, $default, Rule::in($enum));
    }

    /**
     * 验证非空数组
     *
     * @param  string  $key
     * @param  null  $default
     * @return mixed|null
     *
     * @throws BusinessException
     */
    public function verifyNotEmptyArray(string $key, $default = null)
    {
        return $this->verifyData($key, $default, 'array|min:1');
    }

    /**
     * 执行验证，成功返回被验证的值，失败抛出异常
     *
     * @param  string  $key
     * @param  int|string|array|mixed  $default
     * @param  string|array  $rules
     * @param  string|null  $handler
     * @return mixed|null
     *
     * @throws BusinessException
     */
    protected function verifyData(string $key, $default, $rules, ?string $handler = null)
    {
        $value = request()->input($key, $default);

        if (is_null($value) && is_null($default)) {
            return null;
        }

        $validator = Validator::make([$key => $value], [$key => $rules]);

        if ($validator->fails()) {
            throw new BusinessException(CodeResponse::PARAM_VALIDATION_ERROR);
        }

        return $handler ? $handler($value) : $value;
    }
}