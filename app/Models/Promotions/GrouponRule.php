<?php

namespace App\Models\Promotions;

use App\Models\BaseModel;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * App\Models\Promotions\GrouponRule
 *
 * @property int $id
 * @property int $goods_id 商品表的商品ID
 * @property string $goods_name 商品名称
 * @property string|null $pic_url 商品图片或者商品货品图片
 * @property string $discount 优惠金额
 * @property int $discount_member 达到优惠条件的人数
 * @property string|null $expire_time 团购过期时间
 * @property int|null $status 团购规则状态，正常上线则0，到期自动下线则1，管理手动下线则2
 * @property Carbon $add_time 创建时间
 * @property Carbon|null $update_time 更新时间
 * @property bool|null $deleted 逻辑删除
 * @method static Builder|GrouponRule newModelQuery()
 * @method static Builder|GrouponRule newQuery()
 * @method static Builder|GrouponRule query()
 * @method static Builder|GrouponRule whereAddTime($value)
 * @method static Builder|GrouponRule whereDeleted($value)
 * @method static Builder|GrouponRule whereDiscount($value)
 * @method static Builder|GrouponRule whereDiscountMember($value)
 * @method static Builder|GrouponRule whereExpireTime($value)
 * @method static Builder|GrouponRule whereGoodsId($value)
 * @method static Builder|GrouponRule whereGoodsName($value)
 * @method static Builder|GrouponRule whereId($value)
 * @method static Builder|GrouponRule wherePicUrl($value)
 * @method static Builder|GrouponRule whereStatus($value)
 * @method static Builder|GrouponRule whereUpdateTime($value)
 * @mixin Eloquent
 */
class GrouponRule extends BaseModel
{
    //
}
