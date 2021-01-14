<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 *
 * Created By 皮神
 * Date: 2020/12/21
 */
class GoodsTest extends TestCase
{
    use DatabaseTransactions;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    public function testCount()
    {
        $response = $this->get('wechat/goods/count');
        self::assertNotEmpty($response['data']);
    }

    public function testCategory()
    {
        $response = $this->get('wechat/goods/category');
        self::assertEquals($response['errno'], 401);
        $response = $this->get('wechat/goods/category?id=1');
        self::assertEquals($response['errno'], 402);
        $response = $this->get('wechat/goods/category?id=1008009');
        self::assertNotEmpty($response['data']);
        $response = $this->get('wechat/goods/category?id=1005000');
        self::assertNotEmpty($response['data']);
    }

    public function testList()
    {
        $response = $this->get('wechat/goods/list');
        self::assertEquals($response['errno'], 401);
        $response = $this->get('wechat/goods/list?categoryId=1008009');
        self::assertNotEmpty($response['data']);
        $response = $this->get('wechat/goods/list?brandId=1001000');
        self::assertNotEmpty($response['data']);
        $response = $this->get('wechat/goods/list?keyword=天然', $this->getAuthHeader());
//        dump($response->getOriginalContent());
        self::assertNotEmpty($response['data']);
        $response = $this->get('wechat/goods/list?isNew=1');
        self::assertNotEmpty($response['data']);
        $response = $this->get('wechat/goods/list?isHot=1');
        self::assertNotEmpty($response['data']);
        $response = $this->get('wechat/goods/list?page=2&limit=5');
        self::assertEquals(count($response['data']['list']), 5);
        self::assertEquals($response['data']['page'], 2);
    }

    public function testDetail()
    {
        $response = $this->get('wechat/goods/detail');
        self::assertEquals($response['errno'], 401);
        $response = $this->get('wechat/goods/detail?id=1');
        self::assertEquals($response['errno'], 402);
        $response = $this->get('wechat/goods/detail?id=1181000');
        self::assertNotEmpty($response['data']);
        dd($response->getOriginalContent());
    }
}
