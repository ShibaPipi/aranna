<?php
declare(strict_types=1);

namespace App\Models\Goods;

use App\Models\BaseModel;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * App\Models\Goods\GoodsProduct
 *
 * @property int $id
 * @property int $goods_id 商品表的商品ID
 * @property array $specifications 商品规格值列表，采用JSON数组格式
 * @property float $price 商品货品价格
 * @property int $number 商品货品数量
 * @property string|null $url 商品货品图片
 * @property Carbon|null $add_time 创建时间
 * @property Carbon|null $update_time 更新时间
 * @property bool|null $deleted 逻辑删除
 * @method static Builder|GoodsProduct newModelQuery()
 * @method static Builder|GoodsProduct newQuery()
 * @method static Builder|GoodsProduct query()
 * @method static Builder|GoodsProduct whereAddTime($value)
 * @method static Builder|GoodsProduct whereDeleted($value)
 * @method static Builder|GoodsProduct whereGoodsId($value)
 * @method static Builder|GoodsProduct whereId($value)
 * @method static Builder|GoodsProduct whereNumber($value)
 * @method static Builder|GoodsProduct wherePrice($value)
 * @method static Builder|GoodsProduct whereSpecifications($value)
 * @method static Builder|GoodsProduct whereUpdateTime($value)
 * @method static Builder|GoodsProduct whereUrl($value)
 * @mixin Eloquent
 */
class GoodsProduct extends BaseModel
{
    protected $casts = [
        'specifications' => 'array',
        'price' => 'float'
    ];
}
