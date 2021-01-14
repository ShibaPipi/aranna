<?php
/**
 * 收藏服务层
 *
 * Created By 皮神
 * Date: 2021/1/11
 */
declare(strict_types=1);

namespace App\Services;

use App\Enums\Collect\Type;
use App\Models\Collect;

class CollectService extends BaseService
{
    /**
     * @param  int  $userId
     * @param  int  $goodsId
     * @return int
     */
    public function countByGoodsId(int $userId, int $goodsId): int
    {
        return Collect::query()
            ->where('user_id', $userId)
            ->where('value_id', $goodsId)
            ->where('type', Type::Goods)
            ->count('id');
    }
}
