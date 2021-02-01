<?php

namespace Tests\Unit;

use App\Models\Goods\Goods;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BooleanSoftDeleteTest extends TestCase
{
    use DatabaseTransactions;


    public $goodsId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->goodsId = Goods::query()->insertGetId([
            "goods_sn" => "1006002",
            "name" => "轻奢纯棉刺绣水洗四件套",
            "category_id" => 1008009,
            "brand_id" => 0,
            "gallery" => '',
            "keywords" => "",
            "brief" => "设计师原款，精致绣花",
            "is_on_sale" => 1,
            "sort_order" => 23,
            "pic_url" => "https://yanxuan.nosdn.127.net/8ab2d3287af0cefa2cc539e40600621d.png",
            "share_url" => "",
            "is_new" => 0,
            "is_hot" => 0,
            "unit" => "件",
            "counter_price" => 919.0,
            "retail_price" => 899.0,
            "detail" => "",
            "add_time" => "2018-02-01 00:00:00",
            "update_time" => "2021-02-01 08:35:01",
            "deleted" => 0
        ]);
    }

    public function testSoftDeleteByBuilder()
    {
        $goods = Goods::query()->whereId($this->goodsId)->first();
        self::assertEquals($this->goodsId, $goods->id ?? 0);

        $goods = Goods::withoutTrashed()->whereId($this->goodsId)->first();
        self::assertEquals($this->goodsId, $goods->id ?? 0);

        $ret = Goods::query()->whereId($this->goodsId)->delete();
        self::assertEquals(1, $ret);

        $goods = Goods::query()->whereId($this->goodsId)->first();
        self::assertNull($goods);

        $goods = Goods::withTrashed()->whereId($this->goodsId)->first();
        self::assertEquals($this->goodsId, $goods->id ?? 0);

        $goods = Goods::onlyTrashed()->whereId($this->goodsId)->first();
        self::assertEquals($this->goodsId, $goods->id ?? 0);

        $ret = Goods::withTrashed()->whereId($this->goodsId)->restore();
        self::assertEquals(1, $ret);

        $goods = Goods::onlyTrashed()->whereId($this->goodsId)->first();
        self::assertNull($goods);

        $goods = Goods::query()->whereId($this->goodsId)->first();
        self::assertEquals($this->goodsId, $goods->id ?? 0);

        $ret = Goods::query()->whereId($this->goodsId)->forceDelete();
        self::assertEquals(1, $ret);

        $goods = Goods::query()->whereId($this->goodsId)->first();
        self::assertNull($goods);

        $goods = Goods::onlyTrashed()->whereId($this->goodsId)->first();
        self::assertNull($goods);
    }

    public function testSoftDeleteByModel()
    {
        $goods = Goods::query()->whereId($this->goodsId)->first();
        $goods->delete();
        self::assertTrue($goods->deleted);

        $goods = Goods::query()->whereId($this->goodsId)->first();
        self::assertNull($goods);

        $goods = Goods::onlyTrashed()->whereId($this->goodsId)->first();
        $goods->restore();
        self::assertFalse($goods->deleted);

        $goods = Goods::query()->whereId($this->goodsId)->first();
        self::assertEquals($this->goodsId, $goods->id ?? 0);
    }
}
