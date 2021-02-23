<?php
/**
 *
 * Created By 皮神
 * Date: 2020/12/21
 */

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BrandTest extends TestCase
{
    use DatabaseTransactions;

    public function testDetail()
    {
        $response = $this->get('wechat/brand/detail');
        self::assertEquals(401, $response['errno']);
        $response = $this->get('wechat/brand/detail?id=1');
        self::assertEquals(401, $response['errno']);
        $response = $this->get('wechat/brand/detail?id=1001000');
        self::assertEquals(0, $response['errno']);
        self::assertNotEmpty($response['data']);
    }

    public function testList()
    {
        $response = $this->get('wechat/brand/list');
        self::assertNotEmpty($response['data']['list']);
    }
}
