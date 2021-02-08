<?php
/**
 * 订单状态
 *
 * Created By 皮神
 * Date: 2021/2/2
 */

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
}
