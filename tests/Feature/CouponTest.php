<?php
/**
 *
 * Created By 皮神
 * Date: 2020/12/21
 */

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use DatabaseTransactions;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    public function testList()
    {
        $response = $this->get('wechat/coupon/list');
        self::assertEquals(0, $response['errno']);
    }

    public function testMyList()
    {
        $response = $this->get('wechat/coupon/myList', $this->getAuthHeader());
        self::assertEquals(0, $response['errno']);
        self::assertCount(2, $response['data']['list']);
        $response = $this->get('wechat/coupon/myList?status=0', $this->getAuthHeader());
        self::assertEquals(0, $response['errno']);
        self::assertCount(1, $response['data']['list']);
        $response = $this->get('wechat/coupon/myList?status=1', $this->getAuthHeader());
        self::assertEquals(0, $response['errno']);
        self::assertCount(1, $response['data']['list']);
        $response = $this->get('wechat/coupon/myList?status=2', $this->getAuthHeader());
        self::assertEquals(0, $response['errno']);
        self::assertCount(0, $response['data']['list']);
    }
}
