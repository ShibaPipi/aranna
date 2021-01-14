<?php
/**
 * 品牌服务层
 *
 * Created By 皮神
 * Date: 2021/1/11
 */
declare(strict_types=1);

namespace App\Services\Goods;

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
use Illuminate\Database\Eloquent\Model;

class GoodsService extends BaseService
{
    /**
     * @param  int  $id
     * @return Goods|Model|null
     */
    public function getById(int $id)
    {
        return Goods::query()->find($id);
    }

    /**
     * @param  int  $goodsId
     * @return GoodsAttribute[]|Collection
     */
    public function getAttributes(int $goodsId)
    {
        return GoodsAttribute::query()
            ->where('goods_id', $goodsId)
            ->where('deleted', 0)
            ->get();
    }

    /**
     * @param  int  $goodsId
     * @return Collection|\Illuminate\Support\Collection
     */
    public function getSpecifications(int $goodsId)
    {
        $specs = GoodsSpecification::query()
            ->where('goods_id', $goodsId)
            ->where('deleted', 0)
            ->get();

        return $specs->groupBy('specification')
            ->map(function ($item, $key) {
                return ['name' => $key, 'valueList' => $item];
            })
            ->values();
    }

    public function getProducts(int $goodsId)
    {
        return GoodsProduct::query()
            ->where('goods_id', $goodsId)
            ->where('deleted', 0)
            ->get();
    }

    public function getIssues(int $page = 1, int $limit = 4)
    {
        return Issue::query()
            ->forPage($page, $limit)
            ->get();
    }

    /**
     * @param  int  $userId
     * @param  int  $goodsId
     * @return bool
     */
    public function saveFootprint(int $userId, int $goodsId): bool
    {
        $footprint = new Footprint;
        $footprint->fill([
            'user_id' => $userId,
            'goods_id' => $goodsId
        ]);

        return $footprint->save();
    }

    /**
     * 获取在售商品数
     *
     * @return int
     */
    public function countOnSale(): int
    {
        return Goods::query()
            ->where('is_on_sale', 1)
            ->where('deleted', 0)
            ->count('id');
    }

    /**
     * @param  int  $categoryId
     * @param  int  $brandId
     * @param  bool  $isNew
     * @param  bool  $isHot
     * @param  string  $keyword
     * @param  string  $sort
     * @param  string  $order
     * @param  int  $page
     * @param  int  $limit
     * @return LengthAwarePaginator
     */
    public function list(
        int $categoryId,
        int $brandId,
        bool $isNew,
        bool $isHot,
        string $keyword,
        array $columns = ['*'],
        string $sort = 'add_time',
        string $order = 'desc',
        int $page = 1,
        int $limit = 10
    ): LengthAwarePaginator {
        return Goods::query()
            ->where('deleted', 0)
            ->when(!empty($categoryId), function (Builder $query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->commonFilter($brandId, $isNew, $isHot, $keyword)
            ->orderBy($sort, $order)
            ->paginate($limit, $columns, 'page', $page);
    }

    public function l2CategoryList(
        int $brandId,
        bool $isNew,
        bool $isHot,
        string $keyword
    ) {
        $categoryIds = Goods::query()
            ->commonFilter($brandId, $isNew, $isHot, $keyword)
            ->select(['category_id'])
            ->pluck('category_id')
            ->unique()
            ->toArray();

        return CategoryService::getInstance()->getL2ListByIds($categoryIds);
    }
}
