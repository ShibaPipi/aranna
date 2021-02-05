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

    public function testList()
    {
        $response = $this->get('wechat/groupon/list');
//        dd($response->getOriginalContent());
        self::assertEquals(0, $response['errno']);
        self::assertCount(1, $response['data']['list']);
    }
}
