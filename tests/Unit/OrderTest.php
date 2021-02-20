<?php

namespace Tests\Unit;

use App\Enums\Orders\OrderStatus;
use App\Exceptions\BusinessException;
use App\Inputs\Orders\OrderSubmitInput;
use App\Jobs\OrderUnpaidTimeoutJob;
use App\Models\Goods\GoodsProduct;
use App\Models\Orders\Order;
use App\Models\Orders\OrderGoods;
use App\Models\Promotions\GrouponRule;
use App\Models\Users\User;
use App\Services\Goods\GoodsService;
use App\Services\Orders\CartService;
use App\Services\Orders\ExpressService;
use App\Services\Orders\OrderService;
use App\Services\Users\AddressService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Throwable;

class OrderTest extends TestCase
{
    use DatabaseTransactions;

    public function testOrderStatusTrait()
    {
        $order = $this->getOrder();
        self::assertEquals(true, $order->isCreatedStatus());
        self::assertEquals(false, $order->isPaidStatus());
        self::assertEquals(true, $order->handleCanCancel());
        self::assertEquals(true, $order->handleCanPay());
        self::assertEquals(false, $order->handleCanConfirm());
    }

    public function testExpress()
    {
        $res = ExpressService::getInstance()->getOrderTracesByJson('YTO', '12345678');
        dd($res);
    }

    public function testRefundProcess()
    {
        $order = $this->getOrder()->refresh();
        OrderService::getInstance()->paymentSucceed($order, 'pay_id');
        self::assertEquals(OrderStatus::PAID, $order->refresh()->order_status);
        self::assertEquals('pay_id', $order->pay_id);

        OrderService::getInstance()->applyRefund($this->user->id, $order->id);
        self::assertEquals(OrderStatus::REFUNDING, $order->refresh()->order_status);

        $refundType = '微信退款接口';
        $refundContent = '123456';
        OrderService::getInstance()->executeRefund($order->refresh(), $refundType, $refundContent);
        self::assertEquals(OrderStatus::REFUND_CONFIRMED, $order->refresh()->order_status);
        self::assertEquals($refundType, $order->refresh()->refund_type);
        self::assertEquals($refundContent, $order->refresh()->refund_content);

        OrderService::getInstance()->delete($this->user->id, $order->id);
        self::assertNull(OrderService::getInstance()->getOrderById($this->user->id, $order->id));
    }

    public function testBaseProcess()
    {
        $order = $this->getOrder()->refresh();
        OrderService::getInstance()->paymentSucceed($order, 'pay_id');
        self::assertEquals(OrderStatus::PAID, $order->refresh()->order_status);
        self::assertEquals('pay_id', $order->pay_id);

        $shipSn = 'SF019011235';
        $shipChannel = 'SF';
        OrderService::getInstance()->ship($this->user->id, $order->id, $shipSn, $shipChannel);
        self::assertEquals(OrderStatus::SHIPPING, $order->refresh()->order_status);
        self::assertEquals($shipSn, $order->ship_sn);
        self::assertEquals($shipChannel, $order->ship_channel);

        OrderService::getInstance()->confirm($order);
        self::assertEquals(OrderStatus::CONFIRMED, $order->refresh()->order_status);
        self::assertEquals(2, $order->comments);

        OrderService::getInstance()->delete($this->user->id, $order->id);
        self::assertNull(OrderService::getInstance()->getOrderById($this->user->id, $order->id));
    }

    public function testPaymentSucceed()
    {
        $order = $this->getOrder()->refresh();
        OrderService::getInstance()->paymentSucceed($order, 'pay_id');

        self::assertEquals(OrderStatus::PAID, $order->refresh()->order_status);
    }

    /**
     * 测试 cas 乐观锁
     *
     * @throws Throwable
     */
    public function testCas()
    {
        $user = $this->user->refresh();
        $user->nickname = 'test1';
        $user->mobile = '15000000000';
        $ret = $user->cas();
        self::assertEquals(1, $ret);
        self::assertEquals('test1', User::find($this->user->id)->nickname);

        User::query()->where('id', $this->user->id)->update(['nickname' => 'test2']);
        $ret = $user->cas();
        self::assertEquals(0, $ret);
        self::assertEquals('test2', User::find($this->user->id)->nickname);
    }

    /**
     * 测试取消订单
     *
     * @throws BusinessException
     * @throws Throwable
     */
    public function testCancel()
    {
        $order = $this->getOrder();

        OrderService::getInstance()->userCancel($this->user->id, $order->id);
        self::assertEquals(OrderStatus::CANCELED, $order->refresh()->order_status);

        $goodsList = OrderService::getInstance()->getOrderGoodsByOrderId($order->id);
        $productIds = $goodsList->pluck('product_id')->toArray();
        $products = GoodsService::getInstance()->getGoodsProductsByProductIds($productIds);
        self::assertEquals([10, 10], $products->pluck('number')->toArray());
    }

    public function testJob()
    {
        dispatch(new OrderUnpaidTimeoutJob(1, 2));
    }

    /**
     * @throws BusinessException
     */
    public function testReduceStock()
    {
        /** @var GoodsProduct $product1 */
        $product1 = factory(GoodsProduct::class)->create(['number' => 10, 'price' => 11.3]);
        /** @var GoodsProduct $product2 */
        $product2 = factory(GoodsProduct::class)->state('groupon')->create(['number' => 10, 'price' => 20.56]);
        /** @var GoodsProduct $product3 */
        $product3 = factory(GoodsProduct::class)->create(['number' => 10, 'price' => 10.6]);

        CartService::getInstance()->add($this->user->id, $product1->goods_id, $product1->id, 1);
        CartService::getInstance()->add($this->user->id, $product2->goods_id, $product2->id, 5);
        CartService::getInstance()->add($this->user->id, $product3->goods_id, $product3->id, 3);

        CartService::getInstance()->updateChecked($this->user->id, [$product1->id], false);

        $checkedGoodsList = CartService::getInstance()->getCheckedCartList($this->user->id);

        OrderService::getInstance()->reduceProductsStock($checkedGoodsList);

        self::assertEquals($product2->number - 5, $product2->refresh()->number);
        self::assertEquals($product3->number - 3, $product3->refresh()->number);
    }

    /**
     * 提交订单，不包含减库存和超时任务
     *
     * @throws BusinessException
     */
    public function testSubmit()
    {
        $this->user = factory(User::class)->state('default_address')->create();
        $address = AddressService::getInstance()->getDefaultAddress($this->user->id);

        /** @var GoodsProduct $product1 */
        $product1 = factory(GoodsProduct::class)->create(['number' => 10, 'price' => 11.3]);
        /** @var GoodsProduct $product2 */
        $product2 = factory(GoodsProduct::class)->state('groupon')->create(['number' => 10, 'price' => 20.56]);
        /** @var GoodsProduct $product3 */
        $product3 = factory(GoodsProduct::class)->create(['number' => 10, 'price' => 10.6]);

        CartService::getInstance()->add($this->user->id, $product1->goods_id, $product1->id, 1);
        CartService::getInstance()->add($this->user->id, $product2->goods_id, $product2->id, 5);
        CartService::getInstance()->add($this->user->id, $product3->goods_id, $product3->id, 3);

        CartService::getInstance()->updateChecked($this->user->id, [$product1->id], false);

        $checkedGoodsList = CartService::getInstance()->getCheckedCartList($this->user->id);
        $grouponPrice = 0;
        $grouponRuleId = GrouponRule::query()->whereGoodsId($product2->goods_id)->first()->id ?? null;
        $goodsTotalPrice = CartService::getInstance()
            ->getCheckoutCartPriceSubGroupon($checkedGoodsList, $grouponRuleId, $grouponPrice);

        $input = OrderSubmitInput::new([
            'addressId' => $address->id,
            'cartId' => 0,
            'couponId' => 0,
            'message' => '备注',
            'grouponRuleId' => $grouponRuleId
        ]);
        $order = OrderService::getInstance()->submit($this->user->id, $input);
        self::assertNotEmpty($order->id);
        self::assertEquals('备注', $order->message);
        self::assertEquals($grouponPrice, $order->groupon_price);
        self::assertEquals($goodsTotalPrice, $order->goods_price);
        self::assertEquals($goodsTotalPrice, $order->order_price);
        self::assertEquals($goodsTotalPrice, $order->actual_price);

        $list = OrderGoods::query()->whereOrderId($order->id)->get()->toArray();
        self::assertCount(2, $list);
//        dd($list);
        $productIds = CartService::getInstance()->getCartList($this->user->id)->pluck('product_id')->toArray();
        self::assertEquals([$product1->id], $productIds);
    }

    /**
     * 插入一条订单记录并返回
     *
     * @return Order|null
     *
     * @throws BusinessException
     */
    private function getOrder(): ?Order
    {
        $this->user = factory(User::class)->state('default_address')->create(['mobile' => '18222106346']);
        $address = AddressService::getInstance()->getDefaultAddress($this->user->id);

        /** @var GoodsProduct $product1 */
        $product1 = factory(GoodsProduct::class)->create(['number' => 10, 'price' => 11.3]);
        /** @var GoodsProduct $product2 */
        $product2 = factory(GoodsProduct::class)->state('groupon')->create(['number' => 10, 'price' => 20.56]);
        /** @var GoodsProduct $product3 */
        $product3 = factory(GoodsProduct::class)->create(['number' => 10, 'price' => 10.6]);

        CartService::getInstance()->add($this->user->id, $product1->goods_id, $product1->id, 1);
        CartService::getInstance()->add($this->user->id, $product2->goods_id, $product2->id, 5);
        CartService::getInstance()->add($this->user->id, $product3->goods_id, $product3->id, 3);

        CartService::getInstance()->updateChecked($this->user->id, [$product1->id], false);

        $grouponRuleId = GrouponRule::query()->whereGoodsId($product2->goods_id)->first()->id ?? null;

        $input = OrderSubmitInput::new([
            'addressId' => $address->id,
            'cartId' => 0,
            'couponId' => 0,
            'message' => '备注',
            'grouponRuleId' => $grouponRuleId
        ]);

        return OrderService::getInstance()->submit($this->user->id, $input);
    }
}
