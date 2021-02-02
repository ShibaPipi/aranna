<?php
/**
 * 团购服务层
 *
 * Created By 皮神
 * Date: 2021/2/2
 */
declare(strict_types=1);

namespace App\Services\Promotions;

use App\CodeResponse;
use App\Enums\GrouponRules\GrouponRuleStatus;
use App\Enums\Groupons\GrouponStatus;
use App\Exceptions\BusinessException;
use App\Inputs\PageInput;
use App\Models\Promotions\Groupon;
use App\Models\Promotions\GrouponRule;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\AbstractFont;
use Intervention\Image\Facades\Image;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GrouponService extends BaseService
{
    /**
     * 支付成功后，更新团购状态
     *
     * @param  int  $orderId
     * @return void
     *
     * @throws BusinessException
     */
    public function handlePaymentSucceed(int $orderId): void
    {
        if (is_null($groupon = $this->getInfoByOrderId($orderId))) {
            return;
        }

        $rule = $this->getRulesById($groupon->rules_id);
        if (0 == $groupon->groupon_id) {
            $groupon->share_url = $this->createShareImage($rule);
        }

        $groupon->status = GrouponStatus::ON;
        if (!$groupon->save()) {
            $this->throwBusinessException(CodeResponse::UPDATE_FAILED);
        }

        if (0 == $groupon->groupon_id) {
            return;
        }

        if ($this->countGrouponJoin($groupon->groupon_id) < ($rule->discount_member - 1)) {
            return;
        }

        $row = Groupon::query()
            ->where(function (Builder $query) use ($groupon) {
                return $query->where('groupon_id', $groupon->id)
                    ->orWhere('id', $groupon->id);
            })
            ->update(['status' => GrouponStatus::SUCCEED]);

        if (0 == $row) {
            $this->throwBusinessException(CodeResponse::UPDATE_FAILED);
        }
    }

    /**
     * 创建团购分享图片
     * 1. 获取链接，创建二维码
     * 2. 合成图片
     * 3. 保存图片，返回图片地址
     *
     * @param  GrouponRule  $rule
     * @return string
     */
    public function createShareImage(GrouponRule $rule): string
    {
        $shareUrl = route('home.redirectShareUrl', ['type' => 'groupon', 'id' => $rule->goods_id]);
//        dd($shareUrl);
        $qrcode = QrCode::format('png')->size(290)->margin(1)->generate($shareUrl);

        $goodsImage = Image::make($rule->pic_url)->resize(660, 660);
        $image = Image::make(resource_path('images/back_groupon.png'))
            ->insert($qrcode, 'top-left', 460, 770)
            ->insert($goodsImage, 'top-left', 71, 69)
            ->text($rule->goods_name, 65, 867, function (AbstractFont $font) {
                $font->color([167, 136, 69]);
                $font->size(28);
                $font->file(resource_path('fonts/msyh.ttc'));
            });

        $filepath = 'groupon/'.now()->toDateString().'/'.Str::random().'.png';
        Storage::disk('public')->put($filepath, $image->encode());
        Storage::url($filepath);

        return Storage::url($filepath);
    }

    /**
     * 根据订单 id 获取团购信息
     *
     * @param  int  $orderId
     * @return Groupon|Model|null
     */
    public function getInfoByOrderId(int $orderId)
    {
        return Groupon::query()->whereOrderId($orderId)->first();
    }

    /**
     * 新增一条团购记录，根据 linkId 判断开团或参团
     *
     * @param  int  $userId
     * @param  int  $orderId
     * @param  int  $ruleId
     * @param  int|null  $linkId
     * @return int|null 开团 id
     */
    public function openOrJoin(int $userId, int $orderId, int $ruleId, int $linkId = null)
    {
        if (is_null($ruleId) || $ruleId <= 0) {
            return null;
        }

        $groupon = Groupon::new();
        $groupon->order_id = $orderId;
        $groupon->user_id = $userId;
        $groupon->status = GrouponStatus::NONE;
        $groupon->rules_id = $ruleId;

        if (is_null($linkId) || $linkId <= 0) {
            $groupon->creator_user_id = $userId;
            $groupon->creator_user_time = now()->toDateTimeString();
            $groupon->groupon_id = 0;
            $groupon->save();

            return $groupon->id;
        }

        $openGroupon = $this->getInfo($linkId);
        $groupon->creator_user_id = $openGroupon->creator_user_id;
        $groupon->groupon_id = $linkId;
        $groupon->share_url = $openGroupon->share_url;
        $groupon->save();

        return $linkId;
    }

    /**
     * @param  int  $id
     * @param  array|string[]  $columns
     * @return Groupon|Model|null
     */
    public function getInfo(int $id, array $columns = ['*'])
    {
        return Groupon::query()->find($id, $columns);
    }

    /**
     * 校验用户是否可以参与活着开始某个团购活动
     *
     * @param  int  $userId
     * @param  int  $ruleId
     * @param  int|null  $linkId
     * @return void
     *
     * @throws BusinessException
     */
    public function checkValid(int $userId, int $ruleId, int $linkId = null): void
    {
        if (is_null($linkId) || $ruleId <= 0) {
            return;
        }

        if (is_null($rule = $this->getRulesById($ruleId))) {
            $this->throwBusinessException();
        }

        if ($rule->status == GrouponRuleStatus::DOWN_EXPIRE) {
            $this->throwBusinessException(CodeResponse::GROUPON_EXPIRED);
        }

        if ($rule->status == GrouponRuleStatus::DOWN_ADMIN) {
            $this->throwBusinessException(CodeResponse::GROUPON_OFFLINE);
        }

        if (is_null($linkId) || $linkId <= 0) {
            return;
        }

        if ($this->countGrouponJoin($linkId) >= ($rule->discount_member - 1)) {
            $this->throwBusinessException(CodeResponse::GROUPON_FULL);
        }

        if ($this->isOpenOrJoin($userId, $linkId)) {
            $this->throwBusinessException(CodeResponse::GROUPON_JOIN);
        }
    }

    /**
     * 用户是否参与或开启某个团购活动
     *
     * @param $userId
     * @param $grouponId
     * @return bool
     */
    public function isOpenOrJoin($userId, $grouponId): bool
    {
        return Groupon::query()
            ->whereUserId($userId)
            ->where(function (Builder $query) use ($grouponId) {
                return $query->where('groupon_id', $grouponId)
                    ->orWhere('id', $grouponId);
            })
            ->where('status', '!=', GrouponStatus::NONE)
            ->exists();
    }

    /**
     * 获取参团人数
     *
     * @param  int  $openGrouponId  开团团购 id
     * @return int
     */
    public function countGrouponJoin(int $openGrouponId): int
    {
        return Groupon::query()
            ->whereGrouponId($openGrouponId)
            ->where('status', '!=', GrouponStatus::NONE)
            ->count('id');
    }

    /**
     * 根据 id 获取团购规则
     *
     * @param  int  $id
     * @param  array|string[]  $columns
     * @return GrouponRule|Model|null
     */
    public function getRulesById(int $id, array $columns = ['*'])
    {
        return GrouponRule::query()->find($id, $columns);
    }

    /**
     * 获取团购规则
     *
     * @param  PageInput  $input
     * @param  string[]  $columns
     * @return LengthAwarePaginator
     */

    public function getRules(PageInput $input, array $columns = ['*']): LengthAwarePaginator
    {
        return GrouponRule::query()
            ->whereStatus(GrouponRuleStatus::ON)
            ->orderBy($input->sort, $input->order)
            ->paginate($input->limit, $columns, 'page', $input->page);
    }
}
