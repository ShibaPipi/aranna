<?php
/**
 * 订单状态特性
 *
 * Created By 皮神
 * Date: 2021/2/18
 */
declare(strict_types=1);

namespace App\Models\Orders;

use App\Enums\Orders\OrderStatus;
use Exception;
use Illuminate\Support\Str;
use ReflectionClass;
use Throwable;

/**
 * Trait OrderStatusTrait
 *
 * @package App\Models\Orders
 *
 * @method bool handleCanAfterSale() 判断订单是否可以申请售后
 * @method bool handleCanReBuy() 判断订单是否可以再次购买
 * @method bool handleCanComment() 判断订单是否可以评价
 * @method bool handleCanCancel() 判断订单是否可以取消
 * @method bool handleCanPay() 判断订单是否可以支付
 * @method bool handleCanShip() 判断订单是否可以发货
 * @method bool handleCanRefund() 判断订单是否可以申请退款
 * @method bool handleCanExecuteRefund() 判断订单是否可以执行退款
 * @method bool handleCanConfirm() 判断订单是否可以确认收货
 * @method bool handleCanDelete() 判断订单是否可以删除
 *
 * @method bool isCreatedStatus() 判断订单是否为未付款
 * @method bool isPaidStatus() 判断订单是否为已付款
 * @method bool isShippingStatus() 判断订单是否为已发货
 * @method bool isConfirmedStatus() 判断订单是否为已收货
 * @method bool isCanceledStatus() 判断订单是否为已取消
 * @method bool isAutoCanceledStatus() 判断订单是否为系统取消
 * @method bool isAdminCanceledStatus() 判断订单是否为管理员取消
 * @method bool isRefundingStatus() 判断订单是否为退款中
 * @method bool isRefundConfirmedStatus() 判断订单是否为已退款
 * @method bool isGrouponTimeoutStatus() 判断订单是否为已超时团购
 * @method bool isAutoConfirmedStatus() 判断订单是否为系统已确认收货
 */
trait OrderStatusTrait
{
    private $canHandleMap = [
        // 取消操作
        'cancel' => [
            OrderStatus::CREATED,
        ],
        // 删除操作
        'delete' => [
            OrderStatus::CANCELED,
            OrderStatus::AUTO_CANCELED,
            OrderStatus::ADMIN_CANCELED,
            OrderStatus::REFUND_CONFIRMED,
            OrderStatus::CONFIRMED,
            OrderStatus::AUTO_CONFIRMED,
        ],
        // 支付操作
        'pay' => [
            OrderStatus::CREATED,
        ],
        // 发货
        'ship' => [
            OrderStatus::PAID,
        ],
        // 评论操作
        'comment' => [
            OrderStatus::CONFIRMED,
            OrderStatus::AUTO_CONFIRMED,
        ],
        // 确认收货操作
        'confirm' => [
            OrderStatus::SHIPPING,
        ],
        // 取消订单并退款操作
        'refund' => [
            OrderStatus::PAID,
        ],
        // 再次购买
        're_buy' => [
            OrderStatus::CONFIRMED,
            OrderStatus::AUTO_CONFIRMED,
        ],
        // 售后操作
        'after_sale' => [
            OrderStatus::CONFIRMED,
            OrderStatus::AUTO_CONFIRMED,
        ],
        // 同意退款
        'execute_refund' => [
            OrderStatus::REFUNDING,
        ],
    ];

    /**
     * 判断订单是否已经支付
     *
     * @return bool
     */
    public function hasPaid(): bool
    {
        return !in_array($this->order_status, [
            OrderStatus::CREATED,
            OrderStatus::ADMIN_CANCELED,
            OrderStatus::AUTO_CANCELED,
            OrderStatus::CANCELED,
        ]);
    }

    /**
     * 获取订单现有的可操作的状态
     *
     * @return array
     */
    public function getAvailableHandleOptions(): array
    {
        return [
            'cancel' => $this->handleCanCancel(),
            'delete' => $this->handleCanDelete(),
            'pay' => $this->handleCanPay(),
            'comment' => $this->handleCanComment(),
            'confirm' => $this->handleCanConfirm(),
            'refund' => $this->handleCanRefund(),
            're_buy' => $this->handleCanReBuy(),
            'after_sale' => $this->handleCanAfterSale(),
        ];
    }

    /**
     * 构造检测订单状态的逻辑
     *
     * @param  string  $name
     * @return bool
     *
     * @throws Throwable
     */
    protected function checkStatus(string $name): bool
    {
        throw_if(is_null($this->order_status), Exception::class, "order status is null when call method [$name]!");

        $key = Str::of($name)
            ->replaceFirst('is', '')
            ->replaceLast('Status', '')
            ->snake()
            ->upper();

        $status = (new ReflectionClass(OrderStatus::class))->getConstant((string) $key);

        return $this->order_status === $status;
    }

    /**
     * 构造检测订单是否可以进行某些操作行为的逻辑
     *
     * @param  string  $name
     * @return bool
     *
     * @throws Throwable
     */
    protected function checkHandler(string $name): bool
    {
        throw_if(is_null($this->order_status), Exception::class, "order status is null when call method [$name]!");

        $key = Str::of($name)
            ->replaceFirst('handleCan', '')
            ->snake();

        return in_array($this->order_status, $this->canHandleMap[(string) $key]);
    }

    /**
     * @param $name
     * @param $arguments
     * @return bool|mixed
     *
     * @throws Throwable
     */
    public function __call($name, $arguments)
    {
        if (Str::is('handleCan*', $name)) {
            return $this->checkHandler($name);
        } elseif (Str::is('is*Status', $name)) {
            return $this->checkStatus($name);
        }

        return parent::__call($name, $arguments);
    }
}
