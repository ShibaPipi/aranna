<?php

use App\Models\Goods\GoodsProduct;

/**
 * 购物车功能测试
 *
 * Created By 皮神
 * Date: 2020/12/21
 */

namespace Tests\Unit;

use App\Models\Goods\GoodsProduct;
use App\Models\Promotions\Coupon;
use App\Models\Promotions\GrouponRule;
use App\Services\Orders\CartService;
use App\Services\Promotions\CouponService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CartTest extends TestCase
{
    use DatabaseTransactions;

    public function testGetCheckoutPriceSubGrouponSimple()
    {
        /** @var GoodsProduct $product1 */
        $product1 = factory(GoodsProduct::class)->create(['number' => 10, 'price' => 11.3]);
        /** @var GoodsProduct $product2 */
        $product2 = factory(GoodsProduct::class)->create(['number' => 10, 'price' => 20.56]);
        /** @var GoodsProduct $product3 */
        $product3 = factory(GoodsProduct::class)->create(['number' => 10, 'price' => 10.6]);
        CartService::getInstance()->updateChecked($this->user->id, [$product3->id], false);

        CartService::getInstance()->add($this->user->id, $product1->goods_id, $product1->id, 2);
        CartService::getInstance()->add($this->user->id, $product2->goods_id, $product2->id, 1);

        $checkedGoodsList = CartService::getInstance()->getCheckedList($this->user->id);
        $grouponPrice = 0;
        $goodsTotalPrice = CartService::getInstance()
            ->getCheckoutPriceSubGroupon($checkedGoodsList, null, $grouponPrice);
        self::assertEquals(43.16, $goodsTotalPrice);
    }

    public function testGetCheckoutPriceSubGroupon()
    {
        /** @var GoodsProduct $product1 */
        $product1 = factory(GoodsProduct::class)->create(['number' => 10, 'price' => 11.3]);
        /** @var GoodsProduct $product2 */
        $product2 = factory(GoodsProduct::class)->state('groupon')->create(['number' => 10, 'price' => 20.56]);
        /** @var GoodsProduct $product3 */
        $product3 = factory(GoodsProduct::class)->create(['number' => 10, 'price' => 10.6]);

        CartService::getInstance()->updateChecked($this->user->id, [$product1->id], false);

        CartService::getInstance()->add($this->user->id, $product2->goods_id, $product2->id, 5);
        CartService::getInstance()->add($this->user->id, $product3->goods_id, $product3->id, 3);

        $checkedGoodsList = CartService::getInstance()->getCheckedList($this->user->id);
        $grouponPrice = 0;
        $grouponRuleId = GrouponRule::query()->whereGoodsId($product2->goods_id)->first()->id ?? null;
        $goodsTotalPrice = CartService::getInstance()
            ->getCheckoutPriceSubGroupon($checkedGoodsList, $grouponRuleId, $grouponPrice);
        self::assertEquals(5, $grouponPrice);
        self::assertEquals(129.6, $goodsTotalPrice);
    }

    public function testGetMeetest()
    {
        $goodsTotalPrice = '1000.50';

        /** @var Coupon $coupon1 */
        $coupon1 = factory(Coupon::class)->create([
            'name' => '测试优惠券1',
            'discount' => 1.00,
        ]);
        /** @var Coupon $coupon2 */
        $coupon2 = factory(Coupon::class)->create([
            'name' => '测试优惠券1',
            'discount' => 0.50,
        ]);
        /** @var Coupon $coupon3 */
        $coupon3 = factory(Coupon::class)->create([
            'name' => '测试优惠券1',
            'discount' => 2.00,
        ]);

        CouponService::getInstance()->receive($this->user->id, $coupon1->id);
        CouponService::getInstance()->receive($this->user->id, $coupon2->id);
        CouponService::getInstance()->receive($this->user->id, $coupon3->id);

        $couponUsers = CouponService::getInstance()->getUsableListByUserId($this->user->id);
        self::assertEquals(3, $couponUsers->count());

        $availableCouponCount = 0;
        $couponUser = CouponService::getInstance()
            ->getMeetest($this->user->id, null, $goodsTotalPrice, $availableCouponCount);
        self::assertEquals(3, $availableCouponCount);
        self::assertEquals($coupon3->id, $couponUser->coupon_id);
//        dd($couponUser->toArray());

        $availableCouponCount = 0;
        $couponUser = CouponService::getInstance()
            ->getMeetest($this->user->id, $coupon2->id, $goodsTotalPrice, $availableCouponCount);
        self::assertEquals(3, $availableCouponCount);
        self::assertEquals($coupon2->id, $couponUser->coupon_id);
    }
}
