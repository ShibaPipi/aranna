<?php
/**
 * 购物车服务层
 *
 * Created By 皮神
 * Date: 2020/2/3
 */
declare(strict_types=1);

namespace App\Services\Orders;

use App\Exceptions\BusinessException;
use App\Models\Goods\Goods;
use App\Models\Goods\GoodsProduct;
use App\Models\Orders\Cart;
use App\Services\BaseService;
use App\Services\Goods\GoodsService;
use App\Services\Promotions\GrouponService;
use App\Utils\ResponseCode;
use Exception;
use Illuminate\Support\Collection;
use Throwable;

class CartService extends BaseService
{
    /**
     * 删除购物车商品
     *
     * @param  int  $userId
     * @param  int|null  $cartId
     * @return bool|mixed|null
     *
     * @throws Exception
     */
    public function clearCartGoods(int $userId, int $cartId = null): ?bool
    {
        return empty($cartId)
            ? $this->clearCheckedCartGoods($userId)
            : $this->clearCartGoodsById($userId, $cartId);
    }

    /**
     * 根据购物车 id 清除购物车商品
     *
     * @param  int  $userId
     * @param  int  $cartId
     * @return bool|mixed|null
     * @throws Exception
     */
    public function clearCartGoodsById(int $userId, int $cartId): ?bool
    {
        return Cart::query()
            ->whereUserId($userId)
            ->find($cartId)
            ->delete();
    }

    /**
     * 清除选中的购物车商品
     *
     * @param  int  $userId
     * @return bool|mixed|null
     *
     * @throws Exception
     */
    public function clearCheckedCartGoods(int $userId): ?bool
    {
        return Cart::query()
            ->whereUserId($userId)
            ->whereChecked(1)
            ->delete();
    }

    /**
     * 获取待下单的商品总价（减去团购价格的）
     *
     * @param  Cart[]|Collection  $checkedGoodsList
     * @param  int|null  $grouponRuleId
     * @param $grouponPrice
     * @return string
     */
    public function getCheckoutCartPriceSubGroupon(
        Collection $checkedGoodsList,
        ?int $grouponRuleId,
        &$grouponPrice
    ): string {
        $grouponRule = GrouponService::getInstance()->getGrouponRuleItsId($grouponRuleId);
        $goodsTotalPrice = '0';

        foreach ($checkedGoodsList as $cart) {
            if ($grouponRule && $grouponRule->goods_id == $cart->goods_id) {
                $grouponPrice = bcmul($grouponRule->discount, strval($cart->number), 2);
                $price = bcsub($cart->price, $grouponRule->discount, 2);
            } else {
                $price = $cart->price;
            }
            $price = bcmul($price, strval($cart->number), 2);
            $goodsTotalPrice = bcadd($goodsTotalPrice, $price, 2);
        }

        return $goodsTotalPrice;
    }

    /**
     * 获取待下单的商品列表
     *     如果传入 cartId，则为立即购买
     *     否则，获取已选择的购物车商品列表
     *
     * @param  int  $userId
     * @param  int|null  $cartId
     * @return Cart[]|Collection
     *
     * @throws Throwable
     */
    public function getCheckoutGoodsList(int $userId, int $cartId = null): Collection
    {
        $checkedGoodsList = $cartId
            ? collect([CartService::getInstance()->getCartById($userId, $cartId)])
            : CartService::getInstance()->getCheckedCartList($userId);

        $this->throwInvalidParamIf($checkedGoodsList->isEmpty());

        return $checkedGoodsList;
    }

    /**
     * 更新商品勾选状态
     *
     * @param  int  $userId
     * @param  array  $productId
     * @param  int  $checked
     * @return bool|int
     */
    public function updateChecked(int $userId, array $productId, int $checked)
    {
        return Cart::query()
            ->whereUserId($userId)
            ->whereIn('product_id', $productId)
            ->update(compact('checked'));
    }

    /**
     * 获取已经选中的购物车列表
     *
     * @param  int  $userId
     * @return Cart[]|Collection
     */
    public function getCheckedCartList(int $userId): Collection
    {
        return Cart::query()
            ->whereUserId($userId)
            ->whereChecked(1)
            ->get();
    }

    /**
     * 获取购物车列表，自动删除无效商品并返回（下架、删除的）
     *
     * @param  int  $userId
     * @return Cart[]|Collection
     *
     * @throws Exception
     */
    public function getValidCartList(int $userId): Collection
    {
        $invalidCartIds = [];
        $list = $this->getCartList($userId);

        $goodsList = GoodsService::getInstance()->getListByIds(
            $list->pluck('goods_id')->toArray()
        )->keyBy('id');

        $list = $list->filter(function (Cart $cart) use ($goodsList, &$invalidCartIds) {
            /** @var Goods $goods */
            $goods = $goodsList->get($cart->goods_id);

            $valid = !empty($goods) && $goods->is_on_sale;
            if (!$valid) {
                $invalidCartIds[] = $cart->id;
            }

            return $valid;
        });

        // TODO: 让用户自行删除购物车商品，更符合产品层的用户体验
        $this->deleteCartByIds($invalidCartIds);

        return $list;
    }

    /**
     * 获取购物车列表
     *
     * @param  int  $userId
     * @return Cart[]|Collection
     */
    public function getCartList(int $userId): Collection
    {
        return Cart::query()->whereUserId($userId)->get();
    }

    /**
     * 批量删除购物车商品
     *
     * @param  array  $ids
     * @return bool|int|mixed|null
     *
     * @throws Exception
     */
    public function deleteCartByIds(array $ids)
    {
        if (empty($ids)) {
            return 0;
        }

        return Cart::query()->whereIn('id', $ids)->delete();
    }

    /**
     * 删除购物车商品
     *
     * @param  int  $userId
     * @param  array  $productIds
     * @return bool|int|mixed|null
     *
     * @throws Exception
     */
    public function deleteCart(int $userId, array $productIds)
    {
        return Cart::query()
            ->whereUserId($userId)
            ->whereIn('product_id', $productIds)
            ->delete();
    }

    /**
     * 根据 id 获取用户购物车信息
     *
     * @param  int  $userId
     * @param  int  $id
     * @param  array|string[]  $columns
     * @return Cart
     */
    public function getCartById(int $userId, int $id, array $columns = ['*']): Cart
    {
        return Cart::query()->whereUserId($userId)->findOrFail($id, $columns);
    }

    /**
     * 计算用户购物车内商品总数
     *
     * @param  int  $userId
     * @return mixed
     */
    public function countCartProducts(int $userId)
    {
        return Cart::query()->whereUserId($userId)->sum('number');
    }

    /**
     * 立即购买
     *
     * @param  int  $userId
     * @param  int  $goodsId
     * @param  int  $productId
     * @param  int  $number
     * @return Cart
     *
     * @throws BusinessException
     * @throws Throwable
     */
    public function fastAdd(int $userId, int $goodsId, int $productId, int $number): Cart
    {
        [$goods, $product] = $this->getGoodsAndProduct($goodsId, $productId);

        if (is_null($cart = $this->getCartByProductId($userId, $goodsId, $productId))) {
            return $this->newCartRecord($userId, $goods, $product, $number);
        }

        return $this->edit($cart, $product, $number);
    }

    /**
     * 加购
     *
     * @param  int  $userId
     * @param  int  $goodsId
     * @param  int  $productId
     * @param  int  $number
     * @return Cart
     *
     * @throws BusinessException
     * @throws Throwable
     */
    public function add(int $userId, int $goodsId, int $productId, int $number): Cart
    {
        [$goods, $product] = $this->getGoodsAndProduct($goodsId, $productId);

        if (is_null($cart = $this->getCartByProductId($userId, $goodsId, $productId))) {
            return $this->newCartRecord($userId, $goods, $product, $number);
        }

        $number += $cart->number;

        return $this->edit($cart, $product, $number);
    }

    /**
     * 编辑购物车商品数量
     *
     * @param  Cart  $cart
     * @param  GoodsProduct  $product
     * @param  int  $number
     * @return Cart
     *
     * @throws Throwable
     */
    public function edit(Cart $cart, GoodsProduct $product, int $number): Cart
    {
        $this->throwIf($number > $product->number, ResponseCode::GOODS_NO_STOCK);

        $cart->number = $number;
        $cart->save();

        return $cart;
    }

    /**
     * 新增一条购物车记录
     *
     * @param  int  $userId
     * @param  Goods  $goods
     * @param  GoodsProduct  $product
     * @param  int  $number
     * @return Cart
     *
     * @throws Throwable
     */
    public function newCartRecord(int $userId, Goods $goods, GoodsProduct $product, int $number): Cart
    {
        $this->throwIf($number > $product->number, ResponseCode::GOODS_NO_STOCK);

        $cart = Cart::new();

        $cart->goods_id = $goods->id;
        $cart->goods_sn = $goods->goods_sn;
        $cart->goods_name = $goods->name;
        $cart->product_id = $product->id;
        $cart->pic_url = $product->url ?: $goods->pic_url;
        $cart->price = $product->price;
        $cart->specifications = $product->specifications;
        $cart->user_id = $userId;
        $cart->checked = true;
        $cart->number = $number;

        $cart->save();

        return $cart;
    }

    /**
     * 根据商品和货品 id 获取购物车商品
     *
     * @param  int  $userId
     * @param  int  $goodsId
     * @param  int  $productId
     * @return Cart|null
     */
    public function getCartByProductId(int $userId, int $goodsId, int $productId): ?Cart
    {
        return Cart::query()
            ->whereUserId($userId)
            ->whereGoodsId($goodsId)
            ->whereProductId($productId)
            ->first();
    }

    /**
     * 获取商品和货品信息
     *
     * @param  int  $goodsId
     * @param  int  $productId
     * @return array
     *
     * @throws Throwable
     */
    public function getGoodsAndProduct(int $goodsId, int $productId): array
    {
        $goods = GoodsService::getInstance()->getGoodsById($goodsId);

        $this->throwIf(!$goods->is_on_sale, ResponseCode::GOODS_UNSHELVE);

        $product = GoodsService::getInstance()->getGoodsProductByProductId($productId);

        return [$goods, $product];
    }
}
