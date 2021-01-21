<?php

namespace App\Models\Promotions;

use App\Models\BaseModel;

class Coupon extends BaseModel
{
    protected $table = 'coupon';

    protected $casts = [
        'discount' => 'float',
        'min' => 'float',
        'deleted' => 'boolean'
    ];
}
