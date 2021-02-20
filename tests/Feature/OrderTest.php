<?php
/**
 * 订单功能测试
 *
 * Created By 皮神
 * Date: 2020/12/21
 */

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use DatabaseTransactions;

    public function testDetail()
    {
        $response = $this->get('wechat/order/detail?orderId=1', $this->getAuthHeader());
        dd($response->getOriginalContent());
    }
}
