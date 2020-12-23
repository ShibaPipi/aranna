<?php
declare(strict_types=1);

namespace App\Http\Controllers\Wechat;

use App\CodeResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BaseController extends Controller
{
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
     * @param  array|null  $data
     * @return JsonResponse
     */
    protected function success(array $data = null, string $info = ''): JsonResponse
    {
        return $this->codeReturn(CodeResponse::SUCCESS, $data, $info);
    }

    /**
     * 失败返回结果
     * @param  array  $codeResponse
     * @param  string  $info
     * @return JsonResponse
     */
    protected function fail(array $codeResponse = CodeResponse::FAIL, string $info = ''): JsonResponse
    {
        return $this->codeReturn($codeResponse, null, $info);
    }
}
