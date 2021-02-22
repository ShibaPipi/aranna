<?php
/**
 * 订单控制器
 *
 * Created By 皮神
 * Date: 2020/2/8
 */
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
     * 订单详情
     *
     * @return JsonResponse
     *
     * @throws BusinessException|Throwable
     */
    public function detail(): JsonResponse
    {
        $orderId = $this->verifyId('orderId');
        $detail = OrderService::getInstance()->detail($this->userId(), $orderId);

        return $this->success($detail);
    }

    /**
     * 确认收货
     *
     * @return JsonResponse
     *
     * @throws BusinessException
     * @throws Throwable
     */
    public function confirm(): JsonResponse
    {
        $orderId = $this->verifyId('orderId');
        $order = OrderService::getInstance()->getOrderById($this->userId(), $orderId);
        OrderService::getInstance()->confirm($order);

        return $this->success();
    }

    /**
     * 申请退款
     *
     * @return JsonResponse
     *
     * @throws BusinessException
     * @throws Throwable
     */
    public function applyRefund(): JsonResponse
    {
        $orderId = $this->verifyId('orderId');
        OrderService::getInstance()->applyRefund($this->userId(), $orderId);

        return $this->success();
    }

    /**
     * 用户主动取消订单
     *
     * @return JsonResponse
     *
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
