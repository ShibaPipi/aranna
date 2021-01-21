<?php
/**
 *
 * Created By 皮神
 * Date: 2020/12/21
 */

namespace Tests\Feature;

use App\Models\User\Address;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use DatabaseTransactions;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

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
        self::assertEmpty($address);
    }
}
