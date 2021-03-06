<?php
/**
 * 商品服务层
 *
 * Created By 皮神
 * Date: 2021/1/11
 */
declare(strict_types=1);

namespace App\Services\Goods;

use App\Inputs\Goods\GoodsListInput;
use App\Models\Goods\Category;
use App\Models\Goods\Footprint;
use App\Models\Goods\Goods;
use App\Models\Goods\GoodsAttribute;
use App\Models\Goods\GoodsProduct;
use App\Models\Goods\GoodsSpecification;
use App\Models\Goods\Issue;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class GoodsService extends BaseService
{
    /**
     * 增加商品货品库存
     *
     * @param  int  $productId
     * @param  int  $number
     * @return int
     *
     * @throws Throwable
     */
    public function addStock(int $productId, int $number): int
    {
        $product = $this->getGoodsProductByProductId($productId);
        $product->number += $number;

        return $product->cas();
    }

    /**
     * 减去商品货品库存
     *
     * @param  int  $productId
     * @param  int  $number
     * @return int
     *
     * @throws Throwable
     */
    public function reduceStock(int $productId, int $number): int
    {
        $product = $this->getGoodsProductByProductId($productId);
        $product->number -= $number;

        return $product->cas();
    }

    /**
     * 根据 id 获取商品
     *
     * @param  int  $goodsId
     * @param  array|string[]  $columns
     * @return Goods
     */
    public function getGoodsById(int $goodsId, array $columns = ['*']): Goods
    {
        return Goods::query()->findOrFail($goodsId, $columns);
    }

    /**
     * @param  array  $ids
     * @return Goods[]|Collection
     */
    public function getListByIds(array $ids): Collection
    {
        if (empty($ids)) {
            return new Collection;
        }

        return Goods::query()->whereIn('id', $ids)->get();
    }

    /**
     * @param  int  $goodsId
     * @return GoodsAttribute[]|Collection
     */
    public function getAttributes(int $goodsId): Collection
    {
        return GoodsAttribute::query()->whereGoodsId($goodsId)->get();
    }

    /**
     * @param  int  $goodsId
     * @return Collection
     */
    public function getSpecifications(int $goodsId): Collection
    {
        $specs = GoodsSpecification::query()->whereGoodsId($goodsId)->get();

        return $specs->groupBy('specification')
            ->map(function ($item, $key) {
                return ['name' => $key, 'valueList' => $item];
            })
            ->values();
    }

    /**
     * 根据商品货品 id 数组获取商品货品
     *
     * @param  array  $productIds
     * @return GoodsProduct[]|Collection
     */
    public function getGoodsProductsByProductIds(array $productIds): Collection
    {
        if (empty($productIds)) {
            return new Collection;
        }

        return GoodsProduct::query()->whereIn('id', $productIds)->get();
    }

    /**
     * 根据商品货品 id 获取商品货品
     *
     * @param  int  $productId
     * @param  array|string[]  $columns
     * @return GoodsProduct
     */
    public function getGoodsProductByProductId(int $productId, array $columns = ['*']): GoodsProduct
    {
        return GoodsProduct::query()->findOrFail($productId, $columns);
    }

    /**
     * @param  int  $goodsId
     * @return GoodsProduct[]|Collection
     */
    public function getProducts(int $goodsId): Collection
    {
        return GoodsProduct::query()->whereGoodsId($goodsId)->get();
    }

    /**
     * @param  int  $page
     * @param  int  $limit
     * @return Issue[]|Collection
     */
    public function getIssues(int $page = 1, int $limit = 4): Collection
    {
        return Issue::query()->forPage($page, $limit)->get();
    }

    /**
     * 保存用户足迹
     *
     * @param  int  $userId
     * @param  int  $goodsId
     * @return bool
     */
    public function saveFootprint(int $userId, int $goodsId): bool
    {
        return Footprint::new()->fill([
            'user_id' => $userId,
            'goods_id' => $goodsId
        ])->save();
    }

    /**
     * 获取在售商品数
     *
     * @return int
     */
    public function countOnSale(): int
    {
        return Goods::query()->whereIsOnSale(1)->count('id');
    }

    /**
     * @param  GoodsListInput  $input
     * @param  array  $columns
     * @return LengthAwarePaginator
     */
    public function list(GoodsListInput $input, array $columns = ['*']): LengthAwarePaginator
    {
        return Goods::query()
            ->when(!empty($input->categoryId), function (Builder $query) use ($input) {
                $query->where('category_id', $input->categoryId);
            })
            ->commonFilter($input)
            ->orderBy($input->sort, $input->order)
            ->paginate($input->limit, $columns, 'page', $input->page);
    }

    /**
     * @param  GoodsListInput  $input
     * @return Category[]|Collection
     */
    public function l2CategoryList(GoodsListInput $input): Collection
    {
        $categoryIds = Goods::query()
            ->commonFilter($input)
            ->select(['category_id'])
            ->pluck('category_id')
            ->unique()
            ->toArray();

        return CategoryService::getInstance()->getL2CategoriesByIds($categoryIds);
    }
}
