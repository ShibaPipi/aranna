<?php

namespace App\Http\Controllers\Wechat;

use App\Exceptions\BusinessException;
use App\Inputs\Orders\OrderSubmitInput;
use App\Services\Orders\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrderController extends BaseController
{
    /**
     * 提交订单
     *
     * @return JsonResponse
     *
     * @throws BusinessException
     * @throws Throwable
     */
    public function submit(): JsonResponse
    {
        $input = OrderSubmitInput::new();

        $order = DB::transaction(function () use ($input) {
            return OrderService::getInstance()->submit($this->userId(), $input);
        });

        return $this->success([
            'orderId' => $order->id,
            'grouponLinkId' => $input->grouponLinkId ?? 0,
        ]);
    }
}
