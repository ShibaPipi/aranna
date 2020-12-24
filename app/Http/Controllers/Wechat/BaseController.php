<?php
declare(strict_types=1);

namespace App\Http\Controllers\Wechat;

use App\CodeResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

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
            $ret += compact('data');
        }
        return response()->json($ret);
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
}
