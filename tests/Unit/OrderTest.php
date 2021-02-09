<?php

namespace Tests\Unit;

use App\Exceptions\BusinessException;
use App\Inputs\Orders\OrderSubmitInput;
use App\Jobs\OrderUnpaidTimeoutJob;
use App\Models\Goods\GoodsProduct;
use App\Models\Orders\OrderGoods;
use App\Models\Promotions\GrouponRule;
use App\Models\Users\User;
use App\Services\Orders\CartService;
use App\Services\Orders\OrderService;
use App\Services\Users\AddressService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use DatabaseTransactions;

    public function testJob()
    {
        dispatch(new OrderUnpaidTimeoutJob(1, 2));
    }

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
}
