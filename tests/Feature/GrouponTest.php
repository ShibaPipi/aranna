<?php
/**
 *
 * Created By 皮神
 * Date: 2020/12/21
 */

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GrouponTest extends TestCase
{
    use DatabaseTransactions;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    public function testList()
    {
        $response = $this->get('wechat/groupon/list');

        self::assertEquals(0, $response['errno']);
        self::assertCount(1, $response['data']['list']);
    }
}
