<?php
/**
 * 搜索历史服务层
 *
 * Created By 皮神
 * Date: 2021/1/11
 */
declare(strict_types=1);

namespace App\Services;

use App\Models\SearchHistory;

class SearchHistoryService extends BaseService
{
    public function save(int $userId, string $keyword, string $from)
    {
        $searchHistory = new SearchHistory;
        $searchHistory->fill([
            'user_id' => $userId,
            'keyword' => $keyword,
            'from' => $from
        ]);
        $searchHistory->save();

        return $searchHistory;
    }
}
