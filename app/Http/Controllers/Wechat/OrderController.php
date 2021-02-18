<?php
declare(strict_types=1);

namespace App\Http\Controllers\Wechat;

use App\CodeResponse;
use App\Exceptions\BusinessException;
use App\Inputs\Orders\OrderSubmitInput;
use App\Services\Orders\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrderController extends BaseController
{
    /**
     * 用户主动取消订单
     *
     * @return JsonResponse
     *
     * @throws BusinessException
     * @throws Throwable
     */
    public function cancel(): JsonResponse
    {
        $orderId = $this->verifyId('orderId');
        OrderService::getInstance()->userCancel($this->userId(), $orderId);

        return $this->success();
    }

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

        $lockKey = sprintf('order_submit_%s_%s'.$this->userId(), md5(serialize($input)));
        $lock = Cache::lock($lockKey, 5);
        if (!$lock->get()) {
            return $this->fail(CodeResponse::FAIL, '请勿重复下单');
        }

        $order = DB::transaction(function () use ($input) {
            return OrderService::getInstance()->submit($this->userId(), $input);
        });

        return $this->success([
            'orderId' => $order->id,
            'grouponLinkId' => $input->grouponLinkId ?? 0,
        ]);
    }
}
