<?php
declare(strict_types=1);

namespace App\Models\Goods;

use App\Models\BaseModel;

class Brand extends BaseModel
{
    protected $table = 'brand';

    protected $casts = [
        'floor_price' => 'float'
    ];
}
