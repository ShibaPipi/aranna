<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 *
 * Created By 皮神
 * Date: 2020/12/21
 */
class CategoryTest extends TestCase
{
    use DatabaseTransactions;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    public function testIndex()
    {
        $response = $this->get('wechat/category/index', $this->getAuthHeader());
        self::assertEquals(0, $response['errno']);
        self::assertNotEmpty($response['data']['categoryList']);
        self::assertNotEmpty($response['data']['currentCategory']);
        self::assertNotEmpty($response['data']['currentSubCategory']);
    }

    public function testCurrent()
    {
        $response = $this->get('wechat/category/current?id=1005000', $this->getAuthHeader());
        self::assertEquals(0, $response['errno']);
        self::assertNotEmpty($response['data']['currentCategory']);
        self::assertNotEmpty($response['data']['currentSubCategory']);
    }
}
