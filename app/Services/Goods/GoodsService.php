<?php
/**
 * 品牌服务层
 *
 * Created By 皮神
 * Date: 2021/1/11
 */
declare(strict_types=1);

namespace App\Services\Goods;

use App\Models\Goods\Goods;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class GoodsService extends BaseService
{
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
