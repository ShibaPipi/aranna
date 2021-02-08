<?php
/**
 * 优惠券使用状态
 *
 * Created By 皮神
 * Date: 2021/2/5
 */
declare(strict_types=1);

namespace App\Enums\CouponUsers;

class CouponUserStatus
{
    const USABLE = 0;
    const USED = 1;
    const EXPIRED = 2;
    const OUT = 3;
}
