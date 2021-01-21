<?php

namespace Tests\Unit;

use App\CodeResponse;
use App\Exceptions\BusinessException;
use App\Models\Promotions\Coupon;
use App\Models\Promotions\CouponUser;
use App\Services\Promotions\CouponService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use DatabaseTransactions;

    public function testReceiveLimit()
    {
        $this->expectExceptionObject(new BusinessException(CodeResponse::COUPON_EXCEED_LIMIT, '优惠券可领取数量已达上限'));
        CouponService::getInstance()->receive(1, 1);
    }

    public function testReceive()
    {
        $id = Coupon::query()->insertGetId([
            'name' => '活动优惠券',
            'desc' => '活动优惠券',
            'tag' => '满50减20',
            'total' => 0,
            'discount' => 20,
            'min' => 50,
            'limit' => 1,
            'time_type' => 0,
            'days' => 1
        ]);
        $ret = CouponService::getInstance()->receive(1, $id);
        $this->assertTrue($ret);
        $ret = CouponUser::query()->where('user_id', 1)->where('coupon_id', $id)->first();
        $this->assertNotEmpty($ret);
    }
}
