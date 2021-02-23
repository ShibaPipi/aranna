<?php
/**
 *
 * Created By 皮神
 * Date: 2021/1/15
 */
declare(strict_types=1);

namespace App\Inputs;

use App\Exceptions\BusinessException;
use App\Utils\ResponseCode;
use App\Utils\VerifyRequestInput;
use Illuminate\Support\Facades\Validator;

abstract class Input
{
    use VerifyRequestInput;

    /**
     * @param  array|null  $data
     * @return $this
     *
     * @throws BusinessException
     */
    public function fill(array $data = null): Input
    {
        if (is_null($data)) {
            $data = request()->input();
        }

        $validator = Validator::make($data, $this->rules(), $this->messages());
        if ($validator->fails()) {
            throw new BusinessException(ResponseCode::PARAM_VALIDATION_ERROR);
        }

        $vars = array_keys(get_object_vars($this));
        collect($data)->each(function ($item, $key) use ($vars) {
            if (in_array($key, $vars)) {
                $this->$key = $item;
            }
        });

        return $this;
    }

    /**
     * 验证规则
     *
     * @return array
     */
    abstract public function rules(): array;

    /**
     * 验证失败后的错误信息
     *
     * @return array
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * @param  array|null  $data
     * @return Input|static
     *
     * @throws BusinessException
     */
    public static function new(array $data = null): Input
    {
        return (new static)->fill($data);
    }
}
