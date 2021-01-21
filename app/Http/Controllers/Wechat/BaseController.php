<?php
declare(strict_types=1);

namespace App\Http\Controllers\Wechat;

use App\CodeResponse;
use App\Http\Controllers\Controller;
use App\Models\User\User;
use App\VerifyRequestInput;
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
}
