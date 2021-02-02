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
     * @param  array  $ids
     * @return Goods[]|Collection|\Illuminate\Support\Collection
     */
    public function getListByIds(array $ids)
    {
        if (empty($ids)) {
            return collect();
        }
        return Goods::query()->whereIn('id', $ids)->get();
    }

    /**
     * @param  int  $id
     * @return Goods|Model|null
     */
    public function getListById(int $id)
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
            ->count('id');
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

    public function l2CategoryList(GoodsListInput $input)
    {
        $categoryIds = Goods::query()
            ->commonFilter($input)
            ->select(['category_id'])
            ->pluck('category_id')
            ->unique()
            ->toArray();

        return CategoryService::getInstance()->getL2ListByIds($categoryIds);
    }
}
