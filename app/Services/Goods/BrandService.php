<?php
/**
 * 品牌服务层
 *
 * Created By 皮神
 * Date: 2021/1/11
 */
declare(strict_types=1);

namespace App\Services\Goods;

use App\Models\Goods\Brand;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BrandService extends BaseService
{
    /**
     * @param  int  $id
     * @return Brand|Model|null
     */
    public function getDetail(int $id)
    {
        return Brand::query()->find($id);
    }

    public function getList(int $page, int $limit, string $sort, string $order, array $columns = ['*'])
    {
        return Brand::query()
            ->when(!empty($sort) && !empty($order), function (Builder $query) use ($sort, $order) {
                return $query->orderBy($sort, $order);
            })->paginate($limit, $columns, 'page', $page);
    }
}
