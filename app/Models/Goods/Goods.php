<?php
declare(strict_types=1);

namespace App\Models\Goods;

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
     * @param  int  $brandId
     * @param  bool  $isNew
     * @param  bool  $isHot
     * @param  string  $keyword
     * @return Builder
     */
    public function scopeCommonFilter(
        Builder $query,
        int $brandId,
        bool $isNew,
        bool $isHot,
        string $keyword
    ): Builder {
        return $query
            ->when(!empty($brandId), function (Builder $query) use ($brandId) {
                $query->where('brand_id', $brandId);
            })
            ->when(!empty($isNew), function (Builder $query) use ($isNew) {
                $query->where('is_new', $isNew);
            })
            ->when(!empty($isHot), function (Builder $query) use ($isHot) {
                $query->where('is_hot', $isHot);
            })
            ->when(!empty($keyword), function (Builder $query) use ($keyword) {
                $query->where(function (Builder $query) use ($keyword) {
                    $query->where('keywords', 'like', "%${keyword}%")
                        ->orWhere('name', 'like', "%${keyword}%");
                });
            });
    }
}
