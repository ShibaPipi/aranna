<?php
/**
 * 用户性别
 *
 * Created By 皮神
 * Date: 2021/2/22
 */

namespace App\Enums\Users;

class UserGender
{
    const UNKNOWN = 0;
    const MALE = 1;
    const FEMALE = 2;

    const ALL = [
        self::UNKNOWN,
        self::MALE,
        self::FEMALE
    ];
}
