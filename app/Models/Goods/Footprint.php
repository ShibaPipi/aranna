<?php
declare(strict_types=1);

namespace App\Models\Goods;

use App\Models\BaseModel;

class Footprint extends BaseModel
{
    protected $table = 'footprint';

    protected $fillable = [
        'user_id', 'goods_id'
    ];
}
