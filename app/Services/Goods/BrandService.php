<?php
/**
 * 品牌服务层
 *
 * Created By 皮神
 * Date: 2021/1/11
 */
declare(strict_types=1);

namespace App\Services\Goods;

use App\Inputs\PageInput;
use App\Models\Goods\Brand;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class BrandService extends BaseService
{
    /**
     * @param  int  $id
     * @return Brand|null
     */
    public function getBrand(int $id): ?Brand
    {
        return Brand::query()->find($id);
    }

    /**
     * 获取品牌列表
     *
     * @param  PageInput  $input
     * @param  array|string[]  $columns
     * @return LengthAwarePaginator
     */
    public function getBrands(PageInput $input, array $columns = ['*']): LengthAwarePaginator
    {
        return Brand::query()
            ->when(!empty($sort) && !empty($order), function (Builder $query) use ($input) {
                return $query->orderBy($input->sort, $input->order);
            })->paginate($input->limit, $columns, 'page', $input->page);
    }
}
