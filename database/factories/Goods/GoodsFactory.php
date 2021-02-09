<?php

use App\Models\Goods\Goods;
use App\Models\Goods\GoodsProduct;
use App\Models\Goods\GoodsSpecification;
use App\Models\Promotions\GrouponRule;
use App\Services\Goods\GoodsService;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

/** @var Factory $factory */
$factory->define(Goods::class, function (Faker $faker) {
    return [
        'goods_sn' => $faker->word,
        'name' => '测试商品'.$faker->word,
        'category_id' => 1008009,
        'brand_id' => 0,
        'gallery' => [],
        'keywords' => '',
        'brief' => '测试',
        'is_on_sale' => 1,
        'sort_order' => $faker->numberBetween(1, 999),
        'pic_url' => $faker->imageUrl(),
        'share_url' => '',
        'is_new' => $faker->boolean,
        'is_hot' => $faker->boolean,
        'unit' => '件',
        'counter_price' => 919,
        'retail_price' => 899,
        'detail' => $faker->text
    ];
});

$factory->define(GoodsProduct::class, function (Faker $faker) {
    /** @var Goods $goods */
    $goods = factory(Goods::class)->create();
    /** @var GoodsSpecification $spec */
    $spec = factory(GoodsSpecification::class)->create([
        'goods_id' => $goods->id
    ]);
    return [
        'goods_id' => $goods->id,
        'specifications' => [$spec->value],
        'price' => 999,
        'number' => 100,
        'url' => $faker->imageUrl(),
    ];
});

$factory->define(GoodsSpecification::class, function () {
    return [
        'goods_id' => 0,
        'specification' => '规格',
        'value' => '标准'
    ];
});

$factory->define(GrouponRule::class, function () {
    return [
        'goods_id' => 0,
        'goods_name' => '',
        'pic_url' => '',
        'discount' => 0,
        'discount_member' => 2,
        'expire_time' => now()->addDays(10)->toDateTimeString(),
        'status' => 0,
    ];
});

$factory->state(GoodsProduct::class, 'groupon', function () {
    return [];
})->afterCreatingState(GoodsProduct::class, 'groupon', function (GoodsProduct $product) {
    $goods = GoodsService::getInstance()->getGoodsById($product->goods_id);
    factory(GrouponRule::class)->create([
        'goods_id' => $product->goods_id,
        'goods_name' => $goods->name,
        'pic_url' => $goods->pic_url,
        'discount' => 1,
    ]);
});
