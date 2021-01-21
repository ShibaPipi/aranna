<?php

namespace App\Models\Promotions;

use App\Models\BaseModel;

class CouponUser extends BaseModel
{
    protected $table = 'coupon_user';

    protected $fillable = [
        'coupon_id',
        'user_id',
        'start_time',
        'end_time'
    ];
}
