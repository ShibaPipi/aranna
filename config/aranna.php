<?php
/**
 *
 * Created By 皮神
 * Date: 2020/12/22
 */

return [
    'sms' => [
        'aliyun' => [
            'captcha_template_code' => env('CAPTCHA_TEMPLATE_CODE', ''),
            'new_order_template_code' => env('NEW_ORDER_TEMPLATE_CODE', ''),
        ]
    ],
];
