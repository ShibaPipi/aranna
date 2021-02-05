<?php
/**
 *
 * Created By 皮神
 * Date: 2020/12/21
 */

namespace Tests\Feature;

use App\Models\Users\Address;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use DatabaseTransactions;

    public function testList()
    {
        $response = $this->get('wechat/address/list', $this->getAuthHeader());
        self::assertEquals(0, $response['errno']);
        self::assertNotEmpty($response['data']);
    }

    public function testDelete()
    {
        $address = Address::query()->first();
        self::assertNotEmpty($address);
        $response = $this->post('wechat/address/delete', ['id' => $address->id], $this->getAuthHeader());
        $response->assertJson(['errno' => 0]);
        $address = Address::query()->find($address->id);
        self::assertNull($address);
        $response = $this->post('wechat/address/delete', ['id' => 0], $this->getAuthHeader());
        $response->assertJson(['errno' => 400, 'errmsg' => '参数验证错误']);
        $response = $this->post('wechat/address/delete', [], $this->getAuthHeader());
        $response->assertJson(['errno' => 400, 'errmsg' => '参数验证错误']);
    }
}
