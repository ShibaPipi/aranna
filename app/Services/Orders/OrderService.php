<?php
/**
 * 订单服务层
 *
 * Created By 皮神
 * Date: 2020/2/8
 */
declare(strict_types=1);

namespace App\Services\Orders;

use App\CodeResponse;
use App\Enums\Orders\OrderStatus;
use App\Exceptions\BusinessException;
use App\Inputs\Orders\OrderSubmitInput;
use App\Models\Orders\Cart;
use App\Models\Orders\Order;
use App\Models\Orders\OrderGoods;
use App\Services\BaseService;
use App\Services\Promotions\CouponService;
use App\Services\Promotions\GrouponService;
use App\Services\SystemService;
use App\Services\Users\AddressService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderService extends BaseService
{
    public function submit(int $userId, OrderSubmitInput $input): ?Order
    {
        if (!empty($input->grouponRuleId)) {
            GrouponService::getInstance()->checkValidToOpenOrJoin($userId, $input->grouponRuleId);
        }

        if (empty($address = AddressService::getInstance()->getInfoById($userId, $input->addressId))) {
            $this->throwInvalidParamValueException();
        }

        // 获取待下单的商品列表
        $checkedGoodsList = CartService::getInstance()->getCheckoutGoodsList($userId, $input->cartId);

        // 获取订单总价
        $grouponPrice = 0;
        $goodsTotalPrice = CartService::getInstance()->getCheckoutCartPriceSubGroupon($checkedGoodsList,
            $input->grouponRuleId, $grouponPrice);

        // 获取优惠券优惠金额
        $couponPrice = 0;
        if ($input->couponId > 0) {
            $coupon = CouponService::getInstance()->getInfoById($input->couponId);
            $couponUser = CouponService::getInstance()->getCouponUserById($input->couponUserId);
            if (CouponService::getInstance()->checkUsableWithPrice($coupon, $couponUser, $goodsTotalPrice)) {
                $couponPrice = $coupon->discount;
            }
        }

        // 获取运费信息
        $freightPrice = $this->getFreight($goodsTotalPrice);

        // 获取订单总金额：商品总金额 + 运费 - 优惠券金额
        $orderTotalPrice = bcsub(bcadd($goodsTotalPrice, $freightPrice, 2), $couponPrice, 2);
        // 订单金额最小为 0
        $orderTotalPrice = max('0', $orderTotalPrice);
        $actualPrice = $orderTotalPrice;

        // 保存订单记录
        $order = Order::new();
        $order->order_sn = $this->generateSn();
        $order->order_status = OrderStatus::CREATED;
        $order->consignee = $address->name;
        $order->mobile = $address->tel;
        $order->address = $address->province.$address->city.$address->county.' '.$address->address_detail;
        $order->message = $input->message;
        $order->goods_price = $goodsTotalPrice;
        $order->freight_price = $freightPrice;
        $order->coupon_price = $couponPrice;
        $order->order_price = $orderTotalPrice;
        $order->actual_price = $actualPrice;
        $order->groupon_price = $grouponPrice;
        $order->save();

        // 保存订单商品记录
        $this->saveGoods($checkedGoodsList, $order->id);

        // 删除购物车商品记录
        CartService::getInstance()->clearCartGoods($userId, $input->cartId);

        // 减库存
        $this->reduceProductStock($checkedGoodsList);

        // 添加团购记录
        GrouponService::getInstance()->openOrJoinGroupon($userId, $order->id, $input->grouponRuleId,
            $input->grouponLinkId);

        // 设置超时任务
        // TODO

        return null;
    }

    public function reduceProductStock(Collection $checkedGoodsList)
    {
        // TODO
    }

    /**
     * 生成订单编号
     *
     * @return string
     * @throws BusinessException
     */
    public function generateSn(): string
    {
        return retry(6, function () {
            $orderSn = date('YmdHis').Str::random(6);

            if (!$this->snAvailable($orderSn)) {
                Log::warning('订单号获取失败，orderSn：'.$orderSn);

                $this->throwBusinessException(CodeResponse::FAIL, '订单编号获取失败');
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
     * @param  Cart[]|Collection  $checkedGoodsList
     * @param  int  $orderId
     */
    private function saveGoods(Collection $checkedGoodsList, int $orderId)
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
