<?php
/**
 * 订单状态特性
 *
 * Created By 皮神
 * Date: 2021/2/18
 */

namespace App\Models\Orders;

use App\Enums\Orders\OrderStatus;

trait OrderStatusTrait
{
    /**
     * 判断订单是否可以取消
     *
     * @return bool
     */
    public function handleCanCancel(): bool
    {
        return $this->order_status == OrderStatus::CREATED;
    }

    /**
     * 判断订单是否可以支付
     *
     * @return bool
     */
    public function handleCanPay(): bool
    {
        return $this->order_status == OrderStatus::CREATED;
    }

    /**
     * 判断订单是否可以发货
     *
     * @return bool
     */
    public function handleCanShip(): bool
    {
        return $this->order_status == OrderStatus::PAID;
    }

    /**
     * 判断订单是否可以申请退款
     *
     * @return bool
     */
    public function handleCanRefund(): bool
    {
        return $this->order_status == OrderStatus::PAID;
    }

    /**
     * 判断订单是否可以执行退款
     *
     * @return bool
     */
    public function handleCanExecuteRefund(): bool
    {
        return $this->order_status == OrderStatus::PAID;
    }

    /**
     * 判断订单是否可以确认收货
     *
     * @return bool
     */
    public function handleCanConfirm(): bool
    {
        return $this->order_status == OrderStatus::SHIPPING;
    }

    /**
     * 判断订单是否可以确认收货
     *
     * @return bool
     */
    public function handleCanDelete(): bool
    {
        return in_array($this->order_status, [
            OrderStatus::CANCELED,
            OrderStatus::ADMIN_CANCELED,
            OrderStatus::AUTO_CANCELED,
            OrderStatus::REFUND_CONFIRMED,
            OrderStatus::CONFIRMED,
            OrderStatus::AUTO_CONFIRMED,
        ]);
    }

}
