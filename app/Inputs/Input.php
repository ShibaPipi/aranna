<?php
/**
 *
 * Created By 皮神
 * Date: 2021/1/15
 */

namespace App\Inputs;

use App\Utils\CodeResponse;
use App\Exceptions\BusinessException;
use App\VerifyRequestInput;
use Illuminate\Support\Facades\Validator;

class Input
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

        $validator = Validator::make($data, $this->rules());
        if ($validator->fails()) {
            throw new BusinessException(CodeResponse::PARAM_VALIDATION_ERROR);
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
     * @return array
     */
    public function rules(): array
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
