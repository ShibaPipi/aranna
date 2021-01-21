<?php
declare(strict_types=1);

namespace App\Models\Goods;

use App\Inputs\Goods\GoodsListInput;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;

class Goods extends BaseModel
{
    protected $table = 'goods';

    protected $casts = [
        'counter_price' => 'float',
        'retail_price' => 'float',
        'is_new' => 'boolean',
        'is_hot' => 'boolean',
        'gallery' => 'array',
        'is_on_sale' => 'boolean',
        'deleted' => 'boolean'
    ];

    /**
     * @param  Builder  $query
     * @param  int|null  $brandId
     * @param  int|null  $isNew
     * @param  int|null  $isHot
     * @param  string|null  $keyword
     * @return Builder
     */
    public function scopeCommonFilter(Builder $query, GoodsListInput $input): Builder
    {
        return $query
            ->when(!empty($input->brandId), function (Builder $query) use ($input) {
                $query->where('brand_id', $input->brandId);
            })
            ->when(!is_null($input->isNew), function (Builder $query) use ($input) {
                $query->where('is_new', $input->isNew);
            })
            ->when(!is_null($input->isHot), function (Builder $query) use ($input) {
                $query->where('is_hot', $input->isHot);
            })
            ->when(!empty($input->keyword), function (Builder $query) use ($input) {
                $query->where(function (Builder $query) use ($input) {
                    $query->where('keywords', 'like', "%{$input->keyword}%")
                        ->orWhere('name', 'like', "%{$input->keyword}%");
                });
            });
    }
}
