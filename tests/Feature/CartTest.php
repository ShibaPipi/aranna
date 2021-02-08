<?php
/**
 * 购物车功能测试
 *
 * Created By 皮神
 * Date: 2020/12/21
 */

namespace Tests\Feature;

use App\Models\Goods\GoodsProduct;
use App\Models\Users\User;
use App\Services\Goods\GoodsService;
use App\Services\Orders\CartService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CartTest extends TestCase
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

    public function testAdd()
    {
        $response = $this->post('wechat/cart/add', [
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 2
        ], $this->authHeader);
//        dd($response->getOriginalContent());
        $response->assertJson([
            'errno' => 0,
            'errmsg' => '成功',
            'data' => ['2']
        ]);

        $response = $this->post('wechat/cart/add', [
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 3
        ], $this->authHeader);
        $response->assertJson([
            'errno' => 0,
            'errmsg' => '成功',
            'data' => ['5']
        ]);

        $cart = CartService::getInstance()->getInfoByProductId($this->user->id, $this->product->goods_id,
            $this->product->id);
        self::assertEquals(5, $cart->number);
    }

    public function testFastAdd()
    {
        $response = $this->post('wechat/cart/add', [
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 2
        ], $this->authHeader);
//        dd($response->getOriginalContent());
        $response->assertJson([
            'errno' => 0,
            'errmsg' => '成功',
            'data' => ['2']
        ]);

        $response = $this->post('wechat/cart/fastAdd', [
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 5
        ], $this->authHeader);
        $cart = CartService::getInstance()->getInfoByProductId($this->user->id, $this->product->goods_id,
            $this->product->id);
        self::assertEquals($cart->id, $response['data'][0]);
        self::assertEquals(5, $cart->number);
    }

    public function testUpdate()
    {
        $response = $this->post('wechat/cart/add', [
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 3
        ], $this->authHeader);
        $response->assertJson([
            'errno' => 0,
            'errmsg' => '成功',
            'data' => ['3']
        ]);

        $cart = CartService::getInstance()->getInfoByProductId($this->user->id, $this->product->goods_id,
            $this->product->id);
        $response = $this->post('wechat/cart/update', [
            'id' => $cart->id,
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 6
        ], $this->authHeader);
        $response->assertJson(['errno' => 0, 'errmsg' => '成功']);

        $response = $this->post('wechat/cart/update', [
            'id' => $cart->id,
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 11
        ], $this->authHeader);
        $response->assertJson(['errno' => 711, 'errmsg' => '库存不足']);

        $response = $this->post('wechat/cart/update', [
            'id' => $cart->id,
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 0
        ], $this->authHeader);
        $response->assertJson(['errno' => 400]);
    }

    public function testDelete()
    {
        $response = $this->post('wechat/cart/add', [
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 3
        ], $this->authHeader);
        $response->assertJson([
            'errno' => 0,
            'errmsg' => '成功',
            'data' => ['3']
        ]);
        $cart = CartService::getInstance()->getInfoByProductId($this->user->id, $this->product->goods_id,
            $this->product->id);
        self::assertNotNull($cart);

        $this->post('wechat/cart/delete', [
            'productIds' => [$this->product->id,],
        ], $this->authHeader);
        $cart = CartService::getInstance()->getInfoByProductId($this->user->id, $this->product->goods_id,
            $this->product->id);
        self::assertNull($cart);

        $response = $this->post('wechat/cart/delete', [
            'productIds' => [],
        ], $this->authHeader);
        $response->assertJson(['errno' => 400]);
    }

    public function testChecked()
    {
        $response = $this->post('wechat/cart/add', [
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 3
        ], $this->authHeader);
        $response->assertJson([
            'errno' => 0,
            'errmsg' => '成功',
            'data' => ['3']
        ]);

        $cart = CartService::getInstance()->getInfoByProductId($this->user->id, $this->product->goods_id,
            $this->product->id);
        self::assertTrue($cart->checked);

        $this->post('wechat/cart/checked', [
            'productIds' => [$this->product->id,],
            'checked' => 0
        ], $this->authHeader);
        $cart = CartService::getInstance()->getInfoByProductId($this->user->id, $this->product->goods_id,
            $this->product->id);
        self::assertFalse($cart->checked);

        $this->post('wechat/cart/checked', [
            'productIds' => [$this->product->id,],
            'checked' => 1
        ], $this->authHeader);
        $cart = CartService::getInstance()->getInfoByProductId($this->user->id, $this->product->goods_id,
            $this->product->id);
        self::assertTrue($cart->checked);

        $response = $this->post('wechat/cart/checked', [
            'abc123456' => [],
            'checked' => 0
        ], $this->authHeader);
        $response->assertJson(['errno' => 400]);

        $response = $this->post('wechat/cart/checked', [
            'productIds' => [$this->product->id,],
        ], $this->authHeader);
        $response->assertJson(['errno' => 400]);
    }

    public function testIndex()
    {
        $this->post('wechat/cart/add', [
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 3
        ], $this->authHeader);

        $response = $this->post('wechat/cart/index', [], $this->authHeader);
        $response->assertJson([
            'errno' => 0, 'errmsg' => '成功',
            'data' => [
                'cartList' => [
                    [
                        'goodsId' => $this->product->goods_id,
                        'productId' => $this->product->id
                    ]
                ],
                'cartTotal' => [
                    'goodsCount' => 3,
                    'goodsAmount' => 2997.00,
                    'checkedGoodsCount' => 3,
                    'checkedGoodsAmount' => 2997.00
                ]
            ]
        ]);

        $goods = GoodsService::getInstance()->getInfoById($this->product->goods_id);
        $goods->fill(['is_on_sale' => false])->save();
        $response = $this->post('wechat/cart/index', [], $this->authHeader);
        $response->assertJson([
            'errno' => 0, 'errmsg' => '成功',
            'data' => [
                'cartList' => [],
                'cartTotal' => [
                    'goodsCount' => 0,
                    'goodsAmount' => 0,
                    'checkedGoodsCount' => 0,
                    'checkedGoodsAmount' => 0
                ]
            ]
        ]);
        $cart = CartService::getInstance()->getInfoByProductId($this->user->id, $this->product->goods_id,
            $this->product->id);
        self::assertNull($cart);
    }

    public function testCheckout()
    {
        $this->authHeader = $this->getAuthHeader();
        $response = $this->get('wechat/cart/checkout', $this->authHeader);
        dd($response->getOriginalContent());
    }
}
