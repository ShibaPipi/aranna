<?php

namespace App\Http\Controllers\Wechat;

use App\Exceptions\BusinessException;
use App\Services\Users\AddressService;
use Illuminate\Http\JsonResponse;

class AddressController extends BaseController
{
    /**
     * 获取用户地址列表
     *
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        return $this->successPaginate(
            AddressService::getInstance()->getListByUserId($this->user()->id)
        );
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
     * @return JsonResponse
     *
     * @throws BusinessException
     */
    public function delete(): JsonResponse
    {
        AddressService::getInstance()->delete(
            $this->user()->id,
            $this->verifyId('id', 0)
        );

        return $this->success();
    }
}
