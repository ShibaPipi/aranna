<?php
declare(strict_types=1);

namespace App\Models\Goods;

use App\Models\BaseModel;

class GoodsProduct extends BaseModel
{
    protected $table = 'goods_product';

    protected $casts = [
        'specifications' => 'array',
        'price' => 'float',
        'deleted' => 'boolean'
    ];
}
