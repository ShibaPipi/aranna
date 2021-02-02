<?php

namespace App\Http\Controllers\Wechat;

use App\Exceptions\BusinessException;
use App\Inputs\PageInput;
use App\Models\Goods\Goods;
use App\Models\Promotions\GrouponRule;
use App\Services\Goods\GoodsService;
use App\Services\Promotions\GrouponService;
use Illuminate\Http\JsonResponse;

class GrouponController extends BaseController
{
    protected $middlewareOnly = [];

    public function test()
    {
        $rule = GrouponService::getInstance()->getRulesById(1);

        return response(GrouponService::getInstance()->createShareImage($rule));
    }

    /**
     * 团购列表
     *
     * @return JsonResponse
     *
     * @throws BusinessException
     */
    public function list(): JsonResponse
    {
        $pageInput = PageInput::new();

        $rulesList = GrouponService::getInstance()->getRules($pageInput);

        $rules = collect($rulesList->items());
        $goodsIds = $rules->pluck('goods_id')->toArray();
        $goodsList = GoodsService::getInstance()->getListByIds($goodsIds)->keyBy('id');

        $list = $rules->map(function (GrouponRule $rule) use ($goodsList) {
            /** @var Goods $goods */
            $goods = $goodsList->get($rule->goods_id);

            return [
                'id' => $goods->id,
                'name' => $goods->name,
                'brief' => $goods->brief,
                'picUrl' => $goods->pic_url,
                'counterPrice' => $goods->counter_price,
                'retailPrice' => $goods->retail_price,
                'grouponPrice' => bcsub($goods->retail_price, $rule->discount, 2),
                'grouponDiscount' => $rule->discount,
                'grouponMember' => $rule->discount_member,
                'expireTime' => $rule->expire_time
            ];
        });

        $list = $this->paginate($list);

        return $this->success($list);
    }
}
