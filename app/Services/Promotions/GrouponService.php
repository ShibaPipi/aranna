<?php
/**
 * 团购服务层
 *
 * Created By 皮神
 * Date: 2021/2/2
 */
declare(strict_types=1);

namespace App\Services\Promotions;

use App\Enums\GrouponRules\GrouponRuleStatus;
use App\Inputs\PageInput;
use App\Models\Promotions\GrouponRule;
use App\Services\BaseService;

class GrouponService extends BaseService
{

    public function getRules(PageInput $input, $columns = ['*'])
    {
        return GrouponRule::query()
            ->whereStatus(GrouponRuleStatus::ON)
            ->orderBy($input->sort, $input->order)
            ->paginate($input->limit, $columns, 'page', $input->page);
    }
}
