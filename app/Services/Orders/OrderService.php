<?php
/**
 * 订单服务层
 *
 * Created By 皮神
 * Date: 2020/2/8
 */
declare(strict_types=1);

namespace App\Services\Orders;

use App\Utils\ResponseCode;
use App\Enums\Orders\OrderStatus;
use App\Exceptions\BusinessException;
use App\Inputs\Orders\OrderSubmitInput;
use App\Jobs\OrderUnpaidTimeoutJob;
use App\Models\Orders\Cart;
use App\Models\Orders\Order;
use App\Models\Orders\OrderGoods;
use App\Services\BaseService;
use App\Services\Goods\GoodsService;
use App\Services\Promotions\CouponService;
use App\Services\Promotions\GrouponService;
use App\Services\SystemService;
use App\Services\Users\AddressService;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class OrderService extends BaseService
{
    /**
     * 根据订单编号获取订单
     *
     * @param  string  $orderSn
     * @return Order|null
     */
    public function getOrderBySn(string $orderSn): ?Order
    {
        return Order::query()->whereOrderSn($orderSn)->first();
    }

    /**
     * 订单详情
     *
     * @param  int  $userId
     * @param  int  $orderId
     * @return array
     *
     * @throws BusinessException
     */
    public function detail(int $userId, int $orderId): array
    {
        if (empty($order = $this->getOrderById($userId, $orderId))) {
            $this->throwInvalidParamValueException();
        }

        $orderInfo = Arr::only($order->toArray(), [
            'id',
            'orderSn',
            'message',
            'addTime',
            'consignee',
            'mobile',
            'address',
            'goodsPrice',
            'couponPrice',
            'freightPrice',
            'actualPrice',
            'afterSaleStatus',
        ]);

        $orderInfo['orderStatusText'] = OrderStatus::STATUS_TEXT_MAP[$order->order_status] ?? '';
        $orderInfo['handleOption'] = $order->getAvailableHandleOptions();

        $expressInfo = [];

        if ($order->isShippingStatus()) {
            $orderInfo['expCode'] = $order->ship_channel;
            $orderInfo['expNo'] = $order->ship_sn;
            $orderInfo['expName'] = ExpressService::getInstance()->getExpressName($order->ship_channel);
            $expressInfo = ExpressService::getInstance()->getOrderTracesByJson($order->ship_channel, $order->ship_sn);
        }

        $orderGoods = $this->getOrderGoodsByOrderId($orderId);

        return compact('orderInfo', 'orderGoods', 'expressInfo');
    }

    /**
     * 自动确认收货
     *
     * @return void
     *
     * @throws Throwable
     */
    public function autoConfirm(): void
    {
        Log::info('Auto Confirm Start.');

        $this->getConfirmTimeoutOrders()->each(function (Order $order) {
            try {
                $this->confirm($order, true);
            } catch (BusinessException $e) {
            } catch (Throwable $e) {
                Log::error('Auto Confirm Error: '.$e->getMessage());
            }
        });
    }

    /**
     * 获取 7-10 天内已发货但未确认收货的订单
     *
     * @return Order[]|Collection
     */
    public function getConfirmTimeoutOrders(): Collection
    {
        $days = SystemService::getInstance()->getOrderUnconfirmed();

        return Order::query()
            ->whereOrderStatus(OrderStatus::SHIPPING)
            ->where('ship_time', '<=', now()->subDays($days))
            ->where('ship_time', '>=', now()->subDays($days + 10))
            ->get();
    }

    /**
     * 删除订单
     *
     * @param  int  $userId
     * @param  int  $orderId
     * @return void
     *
     * @throws BusinessException
     * @throws Exception
     */
    public function delete(int $userId, int $orderId): void
    {
        if (empty($order = $this->getOrderById($userId, $orderId))) {
            $this->throwInvalidParamValueException();
        }

        if (!$order->handleCanDelete()) {
            $this->throwBusinessException(ResponseCode::ORDER_INVALID_OPERATION, '订单不能删除');
        }

        $order->delete();

        // TODO: 删除相应的售后单，售后状态机不够完善，没有退货退款、卖家寄回、平台确认收货等状态
    }

    /**
     * 确认收货
     *
     * @param  Order  $order
     * @param  bool  $auto
     * @return Order|null
     *
     * @throws BusinessException
     * @throws Throwable
     */
    public function confirm(Order $order, bool $auto = false): ?Order
    {
        if (empty($order)) {
            $this->throwInvalidParamValueException();
        }

        if (!$order->handleCanConfirm()) {
            $this->throwBusinessException(ResponseCode::ORDER_INVALID_OPERATION, '订单不能确认收货');
        }

        $order->comments = $this->countOrderGoods($order->id);
        $order->order_status = $auto ? OrderStatus::AUTO_CONFIRMED : OrderStatus::CONFIRMED;
        $order->confirm_time = now()->toDateTimeString();

        if (0 === $order->cas()) {
            $this->throwUpdateFailedException();
        }

        return $order;
    }

    /**
     * 计算待评价的订单商品数量
     *
     * @param  int  $orderId
     * @return int
     */
    public function countOrderGoods(int $orderId): int
    {
        return OrderGoods::query()->whereOrderId($orderId)->count('id');
    }

    /**
     * 执行退款
     *
     * @param  Order  $order
     * @param $refundType
     * @param $refundContent
     * @return Order
     *
     * @throws BusinessException
     * @throws Throwable
     */
    public function executeRefund(Order $order, $refundType, $refundContent): Order
    {
        if (!$order->handleCanExecuteRefund()) {
            $this->throwBusinessException(ResponseCode::ORDER_INVALID_OPERATION, '订单不能执行退款');
        }

        $now = now()->toDateTimeString();

        $order->order_status = OrderStatus::REFUND_CONFIRMED;
        $order->end_time = $now;
        $order->refund_amount = $order->actual_price;
        $order->refund_type = $refundType;
        $order->refund_content = $refundContent;
        $order->refund_time = $now;

        if (0 === $order->cas()) {
            $this->throwUpdateFailedException();
        }

        // 回滚库存
        $this->rollbackStock($order->id);

        return $order;
    }

    /**
     * 申请退款
     *
     * @param  int  $userId
     * @param  int  $orderId
     * @return Order|null
     *
     * @throws BusinessException
     * @throws Throwable
     */
    public function applyRefund(int $userId, int $orderId): ?Order
    {
        $order = $this->getOrderById($userId, $orderId);

        if (empty($order)) {
            $this->throwInvalidParamValueException();
        }

        if (!$order->handleCanRefund()) {
            $this->throwBusinessException(ResponseCode::ORDER_INVALID_OPERATION, '订单不能申请退款');
        }

        $order->order_status = OrderStatus::REFUNDING;

        if (0 === $order->cas()) {
            $this->throwUpdateFailedException();
        }

        // TODO: 通知用户已经申请退款

        return $order;
    }

    /**
     * 订单发货
     *
     * @param  int  $userId
     * @param  int  $orderId
     * @param  string  $shipSn
     * @param  string  $shipChannel
     * @return Order|null
     *
     * @throws BusinessException
     * @throws Throwable
     */
    public function ship(int $userId, int $orderId, string $shipSn, string $shipChannel): ?Order
    {
        $order = $this->getOrderById($userId, $orderId);

        if (empty($order)) {
            $this->throwInvalidParamValueException();
        }

        if (!$order->handleCanShip()) {
            $this->throwBusinessException(ResponseCode::ORDER_INVALID_OPERATION, '订单不能发货');
        }

        $order->order_status = OrderStatus::SHIPPING;
        $order->ship_sn = $shipSn;
        $order->ship_channel = $shipChannel;
        $order->ship_time = now()->toDateTimeString();

        if (0 === $order->cas()) {
            $this->throwUpdateFailedException();
        }

        // TODO: 通知用户已经发货

        return $order;
    }

    /**
     * 用户取消订单
     *
     * @param  int  $userId
     * @param  int  $orderId
     *
     * @throws Throwable
     */
    public function userCancel(int $userId, int $orderId)
    {
        DB::transaction(function () use ($orderId, $userId) {
            $this->cancel($userId, $orderId);
        });
    }

    /**
     * 管理员取消订单
     *
     * @param  int  $userId
     * @param  int  $orderId
     *
     * @throws Throwable
     */
    public function adminCancel(int $userId, int $orderId)
    {
        DB::transaction(function () use ($orderId, $userId) {
            $this->cancel($userId, $orderId, 'admin');
        });
    }

    /**
     * 系统自动取消订单
     *
     * @param  int  $userId
     * @param  int  $orderId
     *
     * @throws Throwable
     */
    public function systemCancel(int $userId, int $orderId)
    {
        DB::transaction(function () use ($orderId, $userId) {
            $this->cancel($userId, $orderId, 'system');
        });
    }

    /**
     * 取消订单，回滚库存
     *
     * @param  int  $userId
     * @param  int  $orderId
     * @param  string  $role  user|admin|system
     * @return bool
     *
     * @throws BusinessException
     * @throws Throwable
     */
    private function cancel(int $userId, int $orderId, string $role = 'user'): bool
    {
        $order = $this->getOrderById($userId, $orderId);

        if (is_null($order)) {
            $this->throwInvalidParamValueException();
        }

        if (!$order->handleCanCancel()) {
            $this->throwBusinessException(ResponseCode::ORDER_INVALID_OPERATION, '订单不能取消');
        }

        switch ($role) {
            case 'system':
                $order->order_status = OrderStatus::AUTO_CANCELED;
                break;
            case 'admin':
                $order->order_status = OrderStatus::ADMIN_CANCELED;
                break;
            default:
                $order->order_status = OrderStatus::CANCELED;
                break;
        }

        $order->end_time = now()->toDateTimeString();

        if (0 === $order->cas()) {
            $this->throwBusinessException(ResponseCode::UPDATE_FAILED);
        }

        $this->rollbackStock($orderId);

        return true;
    }

    /**
     * 回滚订单商品库存
     *
     * @param  int  $orderId
     * @return void
     *
     * @throws BusinessException
     */
    private function rollbackStock(int $orderId): void
    {
        $this->getOrderGoodsByOrderId($orderId)->each(function (OrderGoods $goods) {
            if (0 === GoodsService::getInstance()->addStock($goods->product_id, $goods->number)) {
                $this->throwUpdateFailedException();
            }
        });
    }

    /**
     * 根据订单 id 获取订单商品
     *
     * @param  int  $orderId
     * @return OrderGoods[]|Collection
     */
    public function getOrderGoodsByOrderId(int $orderId): Collection
    {
        return OrderGoods::query()->whereOrderId($orderId)->get();
    }

    /**
     * 根据订单 id 和用户 id 获取订单信息
     *
     * @param  int  $userId
     * @param  int  $orderId
     * @return Order|null
     */
    public function getOrderById(int $userId, int $orderId): ?Order
    {
        return Order::query()->whereUserId($userId)->find($orderId);
    }

    /**
     * 提交订单
     *
     * @param  int  $userId
     * @param  OrderSubmitInput  $input
     * @return Order|null
     *
     * @throws BusinessException
     * @throws Exception
     */
    public function submit(int $userId, OrderSubmitInput $input): ?Order
    {
        if (!empty($input->grouponRuleId)) {
            GrouponService::getInstance()->checkValidToOpenOrJoin($userId, $input->grouponRuleId);
        }

        if (empty($address = AddressService::getInstance()->getAddressById($userId, $input->addressId))) {
            $this->throwInvalidParamValueException();
        }

        // 获取待下单的商品列表
        $checkedGoodsList = CartService::getInstance()->getCheckoutGoodsList($userId, $input->cartId);

        // 获取订单总价
        $grouponPrice = 0;
        $goodsTotalPrice = CartService::getInstance()->getCheckoutCartPriceSubGroupon($checkedGoodsList,
            $input->grouponRuleId, $grouponPrice);

        // 获取优惠券优惠金额
        $couponPrice = '0';
        if ($input->couponId > 0) {
            $coupon = CouponService::getInstance()->getInfoById($input->couponId);
            $couponUser = CouponService::getInstance()->getCouponUserById($input->couponUserId);
            if (CouponService::getInstance()->checkUsableWithPrice($coupon, $couponUser, $goodsTotalPrice)) {
                $couponPrice = $coupon->discount;
            }
        }

        // 获取运费信息
        $freightPrice = $this->getFreight($goodsTotalPrice);

        // 积分减免金额
        $integralPrice = '0';

        // 获取订单总金额：商品总金额 + 运费 - 优惠券金额 - 积分减免金额
        $orderTotalPrice = bcadd($goodsTotalPrice, $freightPrice, 2);
        $orderTotalPrice = bcsub($orderTotalPrice, $couponPrice, 2);
        $orderTotalPrice = bcsub($orderTotalPrice, $integralPrice, 2);
        // 订单金额最小为 0
        $orderTotalPrice = max('0', $orderTotalPrice);
        $actualPrice = $orderTotalPrice;

        // 保存订单记录
        $order = Order::new();
        $order->user_id = $userId;
        $order->order_sn = $this->generateSn();
        $order->order_status = OrderStatus::CREATED;
        $order->consignee = $address->name;
        $order->mobile = $address->tel;
        $order->address = $address->province.$address->city.$address->county.' '.$address->address_detail;
        $order->message = $input->message ?? '';
        $order->goods_price = $goodsTotalPrice;
        $order->freight_price = $freightPrice;
        $order->coupon_price = $couponPrice;
        $order->integral_price = $integralPrice;
        $order->order_price = $orderTotalPrice;
        $order->actual_price = $actualPrice;
        $order->groupon_price = $grouponPrice;
        $order->save();

        // 保存订单商品记录
        $this->saveOrderGoods($checkedGoodsList, $order->id);

        // 删除购物车商品记录
        CartService::getInstance()->clearCartGoods($userId, $input->cartId);

        // 减库存
        $this->reduceProductsStock($checkedGoodsList);

        // 添加团购记录
        GrouponService::getInstance()->openOrJoinGroupon($userId, $order->id, $input->grouponRuleId,
            $input->grouponLinkId);

        // 设置超时任务
        dispatch(new OrderUnpaidTimeoutJob($userId, $order->id));

        return $order;
    }

    /**
     * 减库存
     *
     * @param  Cart[]|Collection  $checkedGoodsList
     * @return void
     *
     * @throws BusinessException
     */
    public function reduceProductsStock(Collection $checkedGoodsList): void
    {
        $productIds = $checkedGoodsList->pluck('product_id')->toArray();
        $products = GoodsService::getInstance()->getGoodsProductsByProductIds($productIds)->keyBy('id');

        foreach ($checkedGoodsList as $cart) {
            if (empty($product = $products->get($cart->product_id))) {
                $this->throwInvalidParamValueException();
            }

            if ($product->number < $cart->number) {
                $this->throwBusinessException(ResponseCode::GOODS_NO_STOCK);
            }

            if (0 === GoodsService::getInstance()->reduceStock($product->id, $cart->number)) {
                $this->throwBusinessException(ResponseCode::GOODS_NO_STOCK);
            }
        }
    }

    /**
     * 生成订单编号
     *
     * @return string
     *
     * @throws BusinessException
     * @throws Exception
     */
    public function generateSn(): string
    {
        return retry(6, function () {
            $orderSn = date('YmdHis').strtoupper(Str::random(6));

            if ($this->snAvailable($orderSn)) {
                Log::warning('订单号获取失败，orderSn：'.$orderSn);

                $this->throwBusinessException(ResponseCode::FAIL, '订单编号获取失败');
            }

            return $orderSn;
        });
    }

    /**
     * 判断订单号是否已经存在
     *
     * @param  string  $orderSn
     * @return bool
     */
    public function snAvailable(string $orderSn): bool
    {
        return Order::query()->whereOrderSn($orderSn)->exists();
    }

    /**
     * 获取运费金额
     *
     * @param  string  $goodsTotalPrice
     * @return string
     */
    public function getFreight(string $goodsTotalPrice): string
    {
        $freightPrice = '0';

        $freightMin = SystemService::getInstance()->getExpressFreightMin();
        if (1 == bccomp($freightMin, $goodsTotalPrice)) {
            $freightPrice = SystemService::getInstance()->getExpressFreightValue();
        }

        return $freightPrice;
    }

    /**
     * 保存订单商品信息
     *
     * @param  Cart[]|Collection  $checkedGoodsList
     * @param  int  $orderId
     */
    private function saveOrderGoods(Collection $checkedGoodsList, int $orderId)
    {
        foreach ($checkedGoodsList as $cart) {
            $orderGoods = OrderGoods::new();
            $orderGoods->order_id = $orderId;
            $orderGoods->goods_id = $cart->goods_id;
            $orderGoods->goods_sn = $cart->goods_sn;
            $orderGoods->product_id = $cart->product_id;
            $orderGoods->goods_name = $cart->goods_name;
            $orderGoods->pic_url = $cart->pic_url;
            $orderGoods->price = $cart->price;
            $orderGoods->number = $cart->number;
            $orderGoods->specifications = $cart->specifications;

            $orderGoods->save();
        }
    }
}
