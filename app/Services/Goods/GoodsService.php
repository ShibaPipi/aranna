<?php
/**
 * 商品服务层
 *
 * Created By 皮神
 * Date: 2021/1/11
 */
declare(strict_types=1);

namespace App\Services\Goods;

use App\Inputs\Goods\ListInput;
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
     * @param  ListInput  $input
     * @param  array  $columns
     * @return LengthAwarePaginator
     */
    public function list(ListInput $input, array $columns = ['*']): LengthAwarePaginator
    {
        return Goods::query()
            ->where('deleted', 0)
            ->when(!empty($input->categoryId), function (Builder $query) use ($input) {
                $query->where('category_id', $input->categoryId);
            })
            ->commonFilter($input)
            ->orderBy($input->sort, $input->order)
            ->paginate($input->limit, $columns, 'page', $input->page);
    }

    public function l2CategoryList(ListInput $input)
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
