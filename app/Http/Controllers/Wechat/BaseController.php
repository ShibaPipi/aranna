<?php
declare(strict_types=1);

namespace App\Http\Controllers\Wechat;

use App\CodeResponse;
use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Models\User\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BaseController extends Controller
{
    /**
     * @var array 中间件白名单
     */
    protected $middlewareOnly;

    /**
     * @var array 中间件黑名单
     */
    protected $middlewareExcept;

    public function __construct()
    {
        $option = [];
        if (!is_null($this->middlewareOnly)) {
            $option['only'] = $this->middlewareOnly;
        }
        if (!is_null($this->middlewareExcept)) {
            $option['except'] = $this->middlewareExcept;
        }
        $this->middleware('auth:wechat', $option);
    }

    /**
     * 获取正在当前的用户
     *
     * @return User|null
     */
    public function user()
    {
        return auth('wechat')->user();
    }

    /**
     * 判断用户是否登录
     *
     * @return bool
     */
    public function isLogin(): bool
    {
        return !is_null($this->user());
    }

    /**
     * 获取登录用户 id
     *
     * @return mixed
     */
    public function userId()
    {
        return $this->user()->getAuthIdentifier();
    }

    /**
     * @param  array|Collection|LengthAwarePaginator  $data
     * @return array
     */
    public function paginate($data): array
    {
        if ($data instanceof LengthAwarePaginator) {
            $data = $data->toArray();

            return [
                'total' => $data['total'],
                'page' => $data['current_page'],
                'limit' => $data['per_page'],
                'pages' => $data['last_page'],
                'list' => $data['data']
            ];
        }
        if ($data instanceof Collection) {
            $data = $data->toArray();
        }
        if (is_array($data)) {
            $total = count($data);

            return [
                'total' => $total,
                'page' => 1,
                'limit' => $total,
                'pages' => 1,
                'list' => $data
            ];
        }

        return $data;
    }

    /**
     * @param  array  $codeResponse
     * @param  array|null  $data
     * @param  string  $info
     * @return JsonResponse
     */
    protected function codeReturn(array $codeResponse, array $data = null, $info = ''): JsonResponse
    {
        [$errno, $errmsg] = $codeResponse;
        $errmsg = $info ?: $errmsg;
        $ret = compact('errno', 'errmsg');
        if ($data) {
            if (is_array($data)) {
                $data = array_filter($data, function ($item) {
                    return null !== $item;
                });
            }
            $ret += compact('data');
        }
        return response()->json($ret);
    }

    /**
     * @param  array|LengthAwarePaginator  $data
     * @return JsonResponse
     */
    public function successPaginate($data): JsonResponse
    {
        return $this->success($this->paginate($data));
    }

    /**
     * 成功返回结果
     *
     * @param  array|null  $data
     * @return JsonResponse
     */
    protected function success(array $data = null, string $info = ''): JsonResponse
    {
        return $this->codeReturn(CodeResponse::SUCCESS, $data, $info);
    }

    /**
     * 失败返回结果
     *
     * @param  array  $codeResponse
     * @param  string  $info
     * @return JsonResponse
     */
    protected function fail(array $codeResponse = CodeResponse::FAIL, string $info = ''): JsonResponse
    {
        return $this->codeReturn($codeResponse, null, $info);
    }

    /**
     * 根据传入的 bool，判断应该返回成功或者失败
     *
     * @param  bool  $success
     * @param  array  $codeResponse
     * @param  array|null  $data
     * @param  string  $info
     * @return JsonResponse
     */
    protected function judge(
        bool $success,
        array $codeResponse = CodeResponse::FAIL,
        array $data = null,
        string $info = ''
    ): JsonResponse {
        return $success ? $this->success($data, $info) : $this->fail($codeResponse, $info);
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
        return $this->verifyData($key, $default, 'integer|digits_between:1,20');
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
        return $this->verifyData($key, $default, 'integer');
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
        return $this->verifyData($key, $default, 'boolean');
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
     * @return mixed|null
     *
     * @throws BusinessException
     */
    public function verifyData(string $key, $default, $rules)
    {
        $value = request()->input($key, $default);

        if (is_null($value) && is_null($default)) {
            return null;
        }

        $validator = Validator::make([$key => $value], [$key => $rules]);

        if ($validator->fails()) {
            throw new BusinessException(CodeResponse::PARAM_VALIDATION_ERROR);
        }

        return $value;
    }
}
