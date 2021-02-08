<?php

use App\Models\Promotions\Coupon;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

/** @var Factory $factory */
$factory->define(Coupon::class, function (Faker $faker) {
    return [
        'name' => '测试满减券',
        'desc' => '全场通用',
        'tag' => '无限制',
        'total' => 0,
        'discount' => 1.00,
        'min' => 1.00,
        'limit' => 1,
        'type' => 0,
        'status' => 0,
        'goods_type' => 0,
        'goods_value' => '[]',
        'time_type' => 0,
        'days' => 10,
    ];
});
