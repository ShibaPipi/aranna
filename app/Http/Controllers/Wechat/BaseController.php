<?php
declare(strict_types=1);

namespace App\Http\Controllers\Wechat;

use App\Http\Controllers\Controller;
use App\Models\Users\User;
use App\Utils\ResponseCode;
use App\Utils\VerifyRequestInput;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class BaseController extends Controller
{
    use VerifyRequestInput;

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
     * 获取当前正在登录的用户
     *
     * @return Authenticatable|User|null
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
     * @param  null  $list
     * @return array
     */
    public function paginate($data, $list = null): array
    {
        if ($data instanceof LengthAwarePaginator) {
            $data = $data->toArray();

            return [
                'total' => $data['total'],
                'page' => 0 === $data['total'] ? 0 : $data['current_page'],
                'limit' => $data['per_page'],
                'pages' => 0 === $data['total'] ? 0 : $data['last_page'],
                'list' => $list ?? $data['data']
            ];
        }

        if ($data instanceof Collection) {
            $data = $data->toArray();
        }

        if (is_array($data)) {
            $total = count($data);

            return [
                'total' => $total,
                'page' => $total ? 1 : 0,
                'limit' => $total,
                'pages' => $total ? 1 : 0,
                'list' => $data
            ];
        }

        return $data;
    }

    /**
     * @param  array  $responseCode
     * @param  array|null  $data
     * @param  string  $info
     * @return JsonResponse
     */
    protected function codeReturn(array $responseCode, array $data = null, $info = ''): JsonResponse
    {
        [$errno, $errmsg] = $responseCode;
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
     * @param  string  $info
     * @return JsonResponse
     */
    protected function success(array $data = null, string $info = ''): JsonResponse
    {
        return $this->codeReturn(ResponseCode::SUCCESS, $data, $info);
    }

    /**
     * 失败返回结果
     *
     * @param  array  $responseCode
     * @param  string  $info
     * @return JsonResponse
     */
    protected function fail(array $responseCode = ResponseCode::FAIL, string $info = ''): JsonResponse
    {
        return $this->codeReturn($responseCode, null, $info);
    }

    /**
     * 表单验证错误返回结果，400
     *
     * @param  array  $responseCode
     * @param  string  $info
     * @return JsonResponse
     */
    protected function validationFailed(
        array $responseCode = ResponseCode::PARAM_VALIDATION_ERROR,
        string $info = ''
    ): JsonResponse {
        return $this->fail($responseCode, $info);
    }

    /**
     * 参数非法错误返回结果，401
     *
     * @param  array  $responseCode
     * @param  string  $info
     * @return JsonResponse
     */
    protected function invalidParam(
        array $responseCode = ResponseCode::INVALID_PARAM,
        string $info = ''
    ): JsonResponse {
        return $this->fail($responseCode, $info);
    }

    /**
     * 参数值非法错误返回结果，402
     *
     * @param  array  $responseCode
     * @param  string  $info
     * @return JsonResponse
     */
    protected function invalidParamValue(
        array $responseCode = ResponseCode::INVALID_PARAM_VALUE,
        string $info = ''
    ): JsonResponse {
        return $this->fail($responseCode, $info);
    }

    /**
     * 根据传入的 bool，判断应该返回成功或者失败
     *
     * @param  bool  $success
     * @param  array  $responseCode
     * @param  array|null  $data
     * @param  string  $info
     * @return JsonResponse
     */
    protected function judge(
        bool $success,
        array $responseCode = ResponseCode::FAIL,
        array $data = null,
        string $info = ''
    ): JsonResponse {
        return $success ? $this->success($data, $info) : $this->fail($responseCode, $info);
    }
}
