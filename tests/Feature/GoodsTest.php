<?php
/**
 *
 * Created By 皮神
 * Date: 2020/12/21
 */

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GoodsTest extends TestCase
{
    use DatabaseTransactions;

    public function testCount()
    {
        $response = $this->get('wechat/goods/count');
        self::assertNotEmpty($response['data']);
    }

    public function testCategory()
    {
        $response = $this->get('wechat/goods/category');
        self::assertEquals(400, $response['errno']);
        $response = $this->get('wechat/goods/category?id=abs32');
        self::assertEquals(400, $response['errno']);
//        dd($response->getOriginalContent());
        $response = $this->get('wechat/goods/category?id=1');
        self::assertEquals(402, $response['errno']);
        $response = $this->get('wechat/goods/category?id=1008009');
        self::assertNotEmpty($response['data']);
        $response = $this->get('wechat/goods/category?id=1005000');
        self::assertNotEmpty($response['data']);
    }

    public function testList()
    {
        $response = $this->get('wechat/goods/list');
        self::assertEquals(0, $response['errno']);
        $response = $this->get('wechat/goods/list?categoryId=1008009');
        self::assertNotEmpty($response['data']);
        $response = $this->get('wechat/goods/list?brandId=1001000');
        self::assertNotEmpty($response['data']);
        $response = $this->get('wechat/goods/list?keyword=天然', $this->getAuthHeader());
        self::assertNotEmpty($response['data']);
        $response = $this->get('wechat/goods/list?isNew=0');
        self::assertNotEmpty($response['data']);
        $response = $this->get('wechat/goods/list?isHot=1');
        self::assertEquals(25, $response['data']['total']);
        $response = $this->get('wechat/goods/list?isHot=13');
        self::assertEquals(400, $response['errno']);
        $response = $this->get('wechat/goods/list?sort=name&order=asc');
//        dd($response->getOriginalContent());
        self::assertEquals(1025005, $response['data']['list'][0]['id']);
        $response = $this->get('wechat/goods/list?sort=id&order=abc');
        self::assertEquals(400, $response['errno']);
        $response = $this->get('wechat/goods/list?page=2&limit=5');
        self::assertCount(5, $response['data']['list']);
        self::assertEquals(2, $response['data']['page']);
    }

    public function testDetail()
    {
        $response = $this->get('wechat/goods/detail');
        self::assertEquals(400, $response['errno']);
        $response = $this->get('wechat/goods/detail?id=1');
        self::assertEquals(402, $response['errno']);
        $response = $this->get('wechat/goods/detail?id=1181000');
        self::assertNotEmpty($response['data']);
    }
}
