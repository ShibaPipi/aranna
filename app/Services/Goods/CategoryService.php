<?php
/**
 * 分类服务层
 *
 * Created By 皮神
 * Date: 2021/1/11
 */

declare(strict_types=1);

namespace App\Services\Goods;

use App\Models\Goods\Category;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;

class CategoryService extends BaseService
{
    /**
     * 根据 id 获取分类详情
     *
     * @param  int  $id
     * @return Category|null
     */
    public function getCategoryById(int $id): ?Category
    {
        return Category::query()->find($id);
    }

    /**
     * 获取全部一级分类列表
     *
     * @return Category[]|Collection
     */
    public function getL1Categories(): Collection
    {
        return Category::query()->whereLevel('L1')->get();
    }

    /**
     * 根据一级分类 id 获取二级分类列表
     *
     * @param  int  $pid
     * @return Category[]|Collection
     */
    public function getL2CategoriesByPid(int $pid): Collection
    {
        return Category::query()
            ->whereLevel('L2')
            ->wherePid($pid)
            ->get();
    }

    /**
     * 根据 id 获取一级类目
     *
     * @param  int  $id
     * @return Category|null
     */
    public function getL1CategoryById(int $id): ?Category
    {
        return Category::query()->whereLevel('L1')->find($id);
    }

    /**
     * @param  array  $ids
     * @return Category[]|Collection
     */
    public function getL2CategoriesByIds(array $ids): Collection
    {
        if (empty($ids)) {
            return new Collection();
        }

        return Category::query()
            ->whereIn('id', $ids)
            ->whereLevel('L2')
            ->get();
    }
}
