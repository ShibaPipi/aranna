<?php
/**
 * 购物车控制器
 *
 * Created By 皮神
 * Date: 2020/12/21
 */
declare(strict_types=1);

namespace App\Http\Controllers\Wechat;

use App\Utils\ResponseCode;
use App\Exceptions\BusinessException;
use App\Services\Goods\GoodsService;
use App\Services\Orders\CartService;
use App\Services\Orders\OrderService;
use App\Services\Promotions\CouponService;
use App\Services\SystemService;
use App\Services\Users\AddressService;
use Exception;
use Illuminate\Http\JsonResponse;

class CartController extends BaseController
{
    /**
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function index(): JsonResponse
    {
        $list = CartService::getInstance()->getValidCartList($this->userId());

        // PHP bcmath 库函数需要传入字符串，由于开启严格模式，因此初始值赋予 '0'
        $goodsCount = 0;
        $goodsAmount = '0';
        $checkedGoodsCount = 0;
        $checkedGoodsAmount = '0';

        foreach ($list as $cart) {
            $amount = bcmul($cart->price, strval($cart->number), 2);
            $goodsCount += $cart->number;
            $goodsAmount = bcadd($goodsAmount, $amount, 2);

            if ($cart->checked) {
                $checkedGoodsCount += $cart->number;
                $checkedGoodsAmount = bcadd($checkedGoodsAmount, $amount, 2);
            }
        }

        return $this->success([
            'cartList' => $list->toArray(),
            'cartTotal' => compact('goodsCount', 'goodsAmount', 'checkedGoodsCount', 'checkedGoodsAmount')
        ]);
    }

    /**
     * 更新购物车商品数量
     *
     * @return JsonResponse
     *
     * @throws BusinessException
     */
    public function update(): JsonResponse
    {
        $id = $this->verifyId('id', 0);
        $goodsId = $this->verifyId('goodsId', 0);
        $productId = $this->verifyId('productId', 0);
        $number = $this->verifyPositiveInteger('number');

        if (is_null($cart = CartService::getInstance()->getCartById($this->userId(), $id))) {
            return $this->invalidParam();
        }

        if ($cart->goods_id != $goodsId || $cart->product_id != $productId) {
            return $this->invalidParam();
        }

        if (is_null($goods = GoodsService::getInstance()->getGoodsById($goodsId)) || !$goods->is_on_sale) {
            return $this->fail(ResponseCode::GOODS_UNSHELVE);
        }

        if (is_null($product = GoodsService::getInstance()->getGoodsProductByProductId($productId)) || $product->number < $number) {
            return $this->fail(ResponseCode::GOODS_NO_STOCK);
        }

        return $this->judge(
            $cart->fill(compact('number'))->save()
        );
    }

    /**
     * 删除购物车商品
     *
     * @throws BusinessException
     * @throws Exception
     */
    public function delete(): JsonResponse
    {
        $productIds = $this->verifyNotEmptyArray('productIds', []);

        CartService::getInstance()->deleteCart($this->userId(), $productIds);

        return $this->index();
    }

    /**
     * 选中或取消选中商品
     *
     * @return JsonResponse
     *
     * @throws BusinessException
     *
     * @throws Exception
     */
    public function checked(): JsonResponse
    {
        $productIds = $this->verifyNotEmptyArray('productIds', []);
        $checked = $this->verifyBoolean('checked', -1);

        CartService::getInstance()->updateChecked($this->userId(), $productIds, $checked);

        return $this->index();
    }

    /**
     * 获取购物车商品件数
     *
     * @return JsonResponse
     */
    public function goodsCount(): JsonResponse
    {
        return $this->success(
            CartService::getInstance()->countCartProducts($this->userId())
        );
    }

    /**
     * 立即购买
     *
     * @return JsonResponse
     *
     * @throws BusinessException
     */
    public function fastAdd(): JsonResponse
    {
        $goodsId = $this->verifyId('goodsId', 0);
        $productId = $this->verifyId('productId', 0);
        $number = $this->verifyPositiveInteger('number', 0);

        $cart = CartService::getInstance()->fastAdd($this->userId(), $goodsId, $productId, $number);

        return $this->success((array) $cart->id);
    }

    /**
     * 加入购物车
     *
     * @return JsonResponse
     *
     * @throws BusinessException
     */
    public function add(): JsonResponse
    {
        $goodsId = $this->verifyId('goodsId', 0);
        $productId = $this->verifyId('productId', 0);
        $number = $this->verifyPositiveInteger('number', 0);

        CartService::getInstance()->add($this->userId(), $goodsId, $productId, $number);

        return $this->success(
            (array) CartService::getInstance()->countCartProducts($this->userId())
        );
    }

    /**
     * 生成预订单（下单前信息确认）
     *     收货地址ID：
     *         如果收货地址ID是空，则查询当前用户的默认地址。
     *     购物车商品ID：
     *         如果购物车商品ID是空，则下单当前用户所有购物车商品；
     *         如果购物车商品ID非空，则只下单当前购物车商品。
     *     优惠券ID：
     *         如果优惠券ID是空，则自动选择合适的优惠券。
     *
     * @return JsonResponse
     * @throws BusinessException
     */
    public function checkout(): JsonResponse
    {
        $cartId = $this->verifyInteger('cartId');
        $addressId = $this->verifyInteger('addressId');
        $couponId = $this->verifyInteger('couponId');
//        $couponUserId = $this->verifyInteger('couponUserId');
        $grouponRuleId = $this->verifyInteger('grouponRuleId');

        if (!$checkedAddress = AddressService::getInstance()->getInfoOrDefault($this->userId(), $addressId)) {
            return $this->invalidParam();
        }

        $addressId = $checkedAddress->id ?? 0;

        // 获取待下单的商品列表
        $checkedGoodsList = CartService::getInstance()->getCheckoutGoodsList($this->userId(), $cartId);

        // 获取订单总价
        $grouponPrice = 0;
        $goodsTotalPrice = CartService::getInstance()
            ->getCheckoutCartPriceSubGroupon($checkedGoodsList, $grouponRuleId, $grouponPrice);

        // 获取优惠券信息
        $availableCouponCount = 0;
        $couponPrice = '0';
        $couponUser = CouponService::getInstance()
            ->getMeetest($this->userId(), $couponId, $goodsTotalPrice,$availableCouponCount);
        if (!$couponUser) {
            $couponId = -1;
            $couponUserId = -1;
        } else {
            $couponId = $couponUser->coupon_id ?? 0;
            $couponUserId = $couponUser->id ?? 0;
            $couponPrice = CouponService::getInstance()->getInfoById($couponId)->discount ?? '0';
        }

        // 获取运费信息
        $freightPrice = OrderService::getInstance()->getFreight($goodsTotalPrice);

        // 获取订单总金额：商品总金额 + 运费 - 优惠券金额
        $orderTotalPrice = bcsub(bcadd($goodsTotalPrice, $freightPrice, 2), $couponPrice, 2);
        $actualPrice = $orderTotalPrice;

        $checkedAddress = $checkedAddress->toArray();
        $checkedGoodsList = $checkedGoodsList->toArray();

        return $this->success(compact(
            'addressId',
            'couponId',
            'couponUserId',
            'cartId',
            'grouponRuleId',
            'grouponPrice',
            'checkedAddress',
            'availableCouponCount',
            'goodsTotalPrice',
            'freightPrice',
            'couponPrice',
            'orderTotalPrice',
            'actualPrice',
            'checkedGoodsList'
        ));
    }
}
