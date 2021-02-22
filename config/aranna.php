<?php
/**
 *
 * Created By 皮神
 * Date: 2020/12/22
 */

return [
    'sms' => [
        'aliyun' => [
            'captcha_template_code' => env('ALI_SMS_CAPTCHA_TEMPLATE_CODE', ''),
            'new_order_template_code' => env('ALI_SMS_NEW_ORDER_TEMPLATE_CODE', ''),
        ]
    ],
    'express' => [
        'kdniao' => [
            'app_id' => env('KDNIAO_APP_ID', ''),
            'app_key' => env('KDNIAO_APP_KEY', ''),
            'app_url' => env('KDNIAO_APP_URL', ''),
        ]
    ],
];
