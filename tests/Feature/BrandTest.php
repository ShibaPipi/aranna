<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 *
 * Created By 皮神
 * Date: 2020/12/21
 */
class BrandTest extends TestCase
{
    use DatabaseTransactions;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    public function testDetail()
    {
        $response = $this->get('wechat/brand/detail', $this->getAuthHeader());
        self::assertEquals(401, $response['errno']);
        $response = $this->get('wechat/brand/detail?id=1', $this->getAuthHeader());
        self::assertEquals(402, $response['errno']);
        $response = $this->get('wechat/brand/detail?id=1001000', $this->getAuthHeader());
        self::assertEquals(0, $response['errno']);
        self::assertNotEmpty($response['data']);
    }
}
