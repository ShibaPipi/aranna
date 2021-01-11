<?php

namespace App\Http\Controllers\Wechat;

use App\CodeResponse;
use App\Services\User\AddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends BaseController
{
    /**
     * 获取用户地址列表
     *
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        $list = AddressService::getInstance()->getListByUserId($this->user()->id);

        return $this->success([
            'total' => $list->count(),
            'page' => 1,
            'list' => $list,
            'pages' => 1,
            'limit' => $list->count()
        ]);
    }

    public function detail()
    {

    }

    public function save()
    {

    }

    /**
     * 删除地址
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function delete(Request $request): JsonResponse
    {
        $id = $request->input('id');
        if (empty($id) || !is_numeric($id)) {
            $this->fail(CodeResponse::INVALID_PARAM);
        }
        AddressService::getInstance()->delete($this->user()->id, $id);

        return $this->success();
    }
}
