<?php
/**
 * 购物车服务层
 *
 * Created By 皮神
 * Date: 2020/2/3
 */
declare(strict_types=1);

namespace App\Services\Orders;

use App\CodeResponse;
use App\Exceptions\BusinessException;
use App\Models\Goods\Goods;
use App\Models\Goods\GoodsProduct;
use App\Models\Orders\Cart;
use App\Services\BaseService;
use App\Services\Goods\GoodsService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CartService extends BaseService
{
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
    public function getCheckedList(int $userId)
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
    public function getValidList(int $userId)
    {
        $invalidCartIds = [];
        $list = $this->getList($userId);

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
        $this->listDelete($invalidCartIds);

        return $list;
    }

    /**
     * 获取购物车列表
     *
     * @param  int  $userId
     * @return Cart[]|Builder[]|Collection
     */
    public function getList(int $userId)
    {
        return Cart::query()
            ->whereUserId($userId)
            ->get();
    }

    /**
     * 批量删除购物车商品
     *
     * @param  array  $ids
     * @return bool|int|mixed|null
     *
     * @throws Exception
     */
    public function listDelete(array $ids)
    {
        if (empty($ids)) {
            return 0;
        }

        return Cart::query()
            ->whereIn('id', $ids)
            ->delete();
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
    public function delete(int $userId, array $productIds)
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
     * @return Cart|Model|null
     */
    public function getInfoById(int $userId, int $id, array $columns = ['*'])
    {
        return Cart::query()->whereUserId($userId)->find($id, $columns);
    }

    /**
     * 计算用户购物车内商品总数
     *
     * @param  int  $userId
     * @return mixed
     */
    public function countProducts(int $userId)
    {
        return Cart::query()
            ->whereUserId($userId)
            ->sum('number');
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
     */
    public function fastAdd(int $userId, int $goodsId, int $productId, int $number): Cart
    {
        [$goods, $product] = $this->getGoodsAndProduct($goodsId, $productId);

        if (is_null($cart = $this->getInfoByProductId($userId, $goodsId, $productId))) {
            return $this->newRecord($userId, $goods, $product, $number);
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
     */
    public function add(int $userId, int $goodsId, int $productId, int $number): Cart
    {
        [$goods, $product] = $this->getGoodsAndProduct($goodsId, $productId);

        if (is_null($cart = $this->getInfoByProductId($userId, $goodsId, $productId))) {
            return $this->newRecord($userId, $goods, $product, $number);
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
     * @throws BusinessException
     */
    public function edit(Cart $cart, GoodsProduct $product, int $number): Cart
    {
        if ($number > $product->number) {
            $this->throwBusinessException(CodeResponse::GOODS_NO_STOCK);
        }

        $cart->fill(compact('number'))->save();

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
     * @throws BusinessException
     */
    public function newRecord(int $userId, Goods $goods, GoodsProduct $product, int $number): Cart
    {
        if ($number > $product->number) {
            $this->throwBusinessException(CodeResponse::GOODS_NO_STOCK);
        }

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
     * @return Cart|Model|null
     */
    public function getInfoByProductId(int $userId, int $goodsId, int $productId)
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
     * @throws BusinessException
     */
    public function getGoodsAndProduct(int $goodsId, int $productId): array
    {
        if (is_null($goods = GoodsService::getInstance()->getInfoById($goodsId)) || !$goods->is_on_sale) {
            $this->throwBusinessException(CodeResponse::GOODS_UNSHELVE);
        }

        if (is_null($product = GoodsService::getInstance()->getProductByProductId($productId))) {
            $this->throwBusinessException(CodeResponse::GOODS_NO_STOCK);
        }

        return [$goods, $product];
    }
}
