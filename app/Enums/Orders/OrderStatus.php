<?php
/**
 * 订单状态
 *
 * Created By 皮神
 * Date: 2021/2/2
 */
declare(strict_types=1);

namespace App\Enums\Orders;

class OrderStatus
{
    const CREATED = 101;
    const PAID = 201;
    const SHIPPING = 301;
    const CONFIRMED = 401;
    const CANCELED = 102;
    const AUTO_CANCELED = 103;
    const ADMIN_CANCELED = 104;
    const REFUNDING = 202;
    const REFUND_CONFIRMED = 203;
    const GROUPON_TIMEOUT = 204;
    const AUTO_CONFIRMED = 402;

    const STATUS_TEXT_MAP = [
        self::CREATED => '未付款',
        self::CANCELED => "已取消",
        self::AUTO_CANCELED => "已取消(系统)",
        self::ADMIN_CANCELED => "已取消(管理员)",
        self::PAID => "已付款",
        self::REFUNDING => "订单取消，退款中",
        self::REFUND_CONFIRMED => "已退款",
        self::GROUPON_TIMEOUT => "已超时团购",
        self::SHIPPING => "已发货",
        self::CONFIRMED => "已收货",
        self::AUTO_CONFIRMED => "已收货(系统)",
    ];
}
