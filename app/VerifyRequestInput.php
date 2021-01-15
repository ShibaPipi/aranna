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
     * @param  string  $key
     * @return mixed
     *
     * @throws BusinessException
     */
    public function verifyRequiredId(string $key)
    {
        $this->verifyRequired($key);

        return $this->verifyId($key);
    }

    /**
     * @param  string  $key
     * @return mixed|null
     *
     * @throws BusinessException
     */
    public function verifyRequired(string $key)
    {
        return $this->verifyData($key, '', 'required');
    }

    /**
     * @param  string  $key
     * @param  null  $default
     * @return mixed
     *
     * @throws BusinessException
     */
    public function verifyId(string $key, $default = null)
    {
        return $this->verifyData($key, $default, 'integer|digits_between:1,20|min:1', 'intval');
    }

    /**
     * @param  string  $key
     * @param  null  $default
     * @return mixed|null
     *
     * @throws BusinessException
     */
    public function verifyInteger(string $key, $default = null)
    {
        return $this->verifyData($key, $default, 'integer', 'intval');
    }

    /**
     * @param  string  $key
     * @param  null  $default
     * @return mixed|null
     *
     * @throws BusinessException
     */
    public function verifyString(string $key, $default = null)
    {
        return $this->verifyData($key, $default, 'string');
    }

    /**
     * @param  string  $key
     * @param  null  $default
     * @return mixed|null
     *
     * @throws BusinessException
     */
    public function verifyBoolean(string $key, $default = null)
    {
        return $this->verifyData($key, $default, 'boolean', 'intval');
    }

    /**
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
     * @param  string  $key
     * @param $default
     * @param  string|array  $rules
     * @param  string|null  $handler
     * @return mixed|null
     *
     * @throws BusinessException
     */
    public function verifyData(string $key, $default, $rules, ?string $handler = null)
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