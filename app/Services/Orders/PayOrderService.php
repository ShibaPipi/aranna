<?php
/**
 * 订单支付服务层
 *
 * Created By 皮神
 * Date: 2021/2/20
 */
declare(strict_types=1);

namespace App\Services\Orders;

use App\Utils\ResponseCode;
use App\Enums\Orders\OrderStatus;
use App\Exceptions\BusinessException;
use App\Models\Orders\Order;
use App\Notifications\NewOrderEmailNotify;
use App\Notifications\NewOrderSmsNotify;
use App\Services\BaseService;
use App\Services\Promotions\GrouponService;
use App\Services\Users\UserService;
use Illuminate\Support\Facades\Notification;
use Throwable;

class PayOrderService extends BaseService
{
    /**
     * 处理支付宝回调
     *
     * @param  array  $data
     * @return Order
     *
     * @throws BusinessException
     * @throws Throwable
     */
    public function alipayNotify(array $data): ?Order
    {
        if (!in_array($data['trade_status'] ?? '', ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
            $this->throwInvalidParamException();
        }

        $orderSn = $data['out_trade_no'] ?? '';
        $payId = $data['trade_no'] ?? '';
        $price = strval($data['total_amount'] ?? 0);

        return $this->notify($orderSn, $payId, $price);
    }

    /**
     * 获取微信支付的订单参数
     *
     * @param  int  $userId
     * @param  int  $orderId
     * @return array
     *
     * @throws BusinessException
     */
    public function getAlipayParams(int $userId, int $orderId): array
    {
        $order = $this->getPayOrderInfo($userId, $orderId);

        return [
            'out_trade_no' => $order->order_sn,
            'subject' => '订单：'.$order->order_sn,
            'total_amount' => $order->actual_price,
        ];
    }

    /**
     * 处理微信回调
     *
     * @param  array  $data
     * @return Order
     *
     * @throws BusinessException
     * @throws Throwable
     */
    public function wechatNotify(array $data): Order
    {
        $orderSn = $data['out_trade_no'] ?? '';
        $payId = $data['transaction_id'] ?? '';
        $price = bcdiv(strval($data['total_fee']), 100, 2);

        return $this->notify($orderSn, $payId, $price);
    }

    /**
     * 支付回调通用逻辑
     *
     * @param $orderSn
     * @param $payId
     * @param $price
     * @return Order
     *
     * @throws BusinessException
     * @throws Throwable
     */
    private function notify(string $orderSn, string $payId, string $price): ?Order
    {
        if (empty($order = OrderService::getInstance()->getOrderBySn($orderSn))) {
            $this->throwInvalidParamException();
        }

        if ($order->hasPaid()) {
            return $order;
        }

        if (0 !== bccomp($order->actual_price, $price, 2)) {
            $this->throwBusinessException(ResponseCode::FAIL,
                "支付回调，订单[{$order->id}]金额不一致,[total_fee={$price}],订单金额[actual_price={$order->actual_price}]");
        }

        return $this->paymentSucceed($order, $payId);
    }

    /**
     * 获取微信支付的订单参数
     *
     * @param  int  $userId
     * @param  int  $orderId
     * @return array
     *
     * @throws BusinessException
     */
    public function getWechatPayParams(int $userId, int $orderId): array
    {
        $order = $this->getPayOrderInfo($userId, $orderId);

        return [
            'out_trade_no' => $order->order_sn,
            'body' => '订单：'.$order->order_sn,
            'total_fee' => bcmul($order->actual_price, '100', 2),
        ];
    }

    /**
     * 获取订单的支付信息
     *
     * @param  int  $userId
     * @param  int  $orderId
     * @return Order
     *
     * @throws BusinessException
     */
    private function getPayOrderInfo(int $userId, int $orderId): ?Order
    {
        if (empty($order = OrderService::getInstance()->getOrderById($userId, $orderId))) {
            $this->throwInvalidParamException();
        }

        if (!$order->handleCanPay()) {
            $this->throwBusinessException(ResponseCode::ORDER_INVALID_OPERATION, '订单不能支付');
        }

        return $order;
    }

    /**
     * 支付成功
     *
     * @param  Order  $order
     * @param  string  $payId
     * @return Order
     *
     * @throws BusinessException
     * @throws Throwable
     */
    public function paymentSucceed(Order $order, string $payId): Order
    {
        if (!$order->handleCanPay()) {
            $this->throwBusinessException(ResponseCode::ORDER_PAY_FAIL, '订单不能支付');
        }

        $order->pay_id = $payId;
        $order->pay_time = now()->toDateTimeString();
        $order->order_status = OrderStatus::PAID;

        if (0 === $order->cas()) {
            $this->throwBusinessException(ResponseCode::UPDATE_FAILED);
        }

        // 更新支付成功的团购信息
        GrouponService::getInstance()->handlePaymentSucceed($order->id);

        // 发送邮件通知
        Notification::route('mail', '906262260@qq.com')->notify(new NewOrderEmailNotify($order->id));

        // 发送短信通知
        $user = UserService::getInstance()->getUserById($order->user_id);
        $user->notify(new NewOrderSmsNotify);

        return $order;
    }
}
