<?php
/**
 * 分类服务层
 *
 * Created By 皮神
 * Date: 2021/1/11
 */
//declare(strict_types=1);

namespace App\Services\Goods;

use App\Models\Goods\Category;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CategoryService extends BaseService
{
    /**
     * 根据 id 获取分类详情
     *
     * @param  int  $id
     * @return Category|Model|null
     */
    public function getById(int $id)
    {
        return Category::query()->find($id);
    }

    /**
     * 获取全部一级分类列表
     *
     * @return Category[]|Collection
     */
    public function getL1List()
    {
        return Category::query()
            ->where('level', 'L1')
            ->where('deleted', 0)
            ->get();
    }

    /**
     * 根据一级分类 id 获取二级分类列表
     *
     * @param  int  $pid
     * @return Category[]|Collection
     */
    public function getL2ListByPid(int $pid)
    {
        return Category::query()
            ->where('level', 'L2')
            ->where(compact('pid'))
            ->where('deleted', 0)
            ->get();
    }

    /**
     * 根据 id 获取一级类目
     *
     * @param  int  $id
     * @return Category|Model|null
     */
    public function getL1ById(int $id)
    {
        return Category::query()
            ->where('level', 'L1')
            ->where(compact('id'))
            ->where('deleted', 0)
            ->first();
    }

    /**
     * @param  array  $ids
     * @return Category[]|Collection
     */
    public function getL2ListByIds(array $ids)
    {
        if (empty($ids)) {
            return collect();
        }
        return Category::query()
            ->whereIn('id', $ids)
            ->where('level', 'L2')
            ->where('deleted', 0)
            ->get();
    }
}
