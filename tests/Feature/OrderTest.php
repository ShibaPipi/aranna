<?php
/**
 * 订单功能测试
 *
 * Created By 皮神
 * Date: 2020/12/21
 */

namespace Tests\Feature;

use App\Models\Goods\GoodsProduct;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var GoodsProduct
     */
    private $product;

    private $authHeader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->product = factory(GoodsProduct::class)->create(
            ['number' => 10]);
        $this->authHeader = $this->getAuthHeader($this->user->username, '123456');
    }

    public function testSubmit()
    {
        $response = $this->post('wechat/order/submit', [
            'cartId'=>1,
            'addressId'=>1,
            'couponId'=>1,
            'couponUserId'=>1,
        ], $this->authHeader);
        dd($response->getOriginalContent());
    }
}
