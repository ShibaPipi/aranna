<?php
/**
 * 系统服务层
 *
 * Created By 皮神
 * Date: 2021/1/11
 */
declare(strict_types=1);

namespace App\Services;

use App\Models\System;

class SystemService extends BaseService
{
    const WX_INDEX_NEW = 'wx_index_new';
    const WX_INDEX_HOT = 'wx_index_hot';
    const WX_INDEX_BRAND = 'wx_index_brand';
    const WX_INDEX_TOPIC = 'wx_index_topic';
    const WX_INDEX_CATALOG_LIST = 'wx_catalog_list';
    const WX_INDEX_CATALOG_GOODS = 'wx_catalog_goods';
    const WX_SHARE = 'wx_share';
    // 运费相关配置
    const EXPRESS_FREIGHT_VALUE = 'express_freight_value';
    const EXPRESS_FREIGHT_MIN = 'express_freight_min';
    // 订单相关配置
    const ORDER_UNPAID_TIMEOUT = 'order_unpaid_timeout';
    const ORDER_UNCONFIRMED = 'order_unconfirmed';
    const ORDER_COMMENT = 'order_comment';
    // 商场相关配置
    const MALL_NAME = 'mall_name';
    const MALL_ADDRESS = 'mall_address';
    const MALL_PHONE = 'mall_phone';
    const MALL_QQ = 'mall_qq';
    const MALL_LONGITUDE = 'mall_longitude';
    const MALL_Latitude = 'mall_latitude';

    /**
     * 获取订单自动确认收货的天数
     *
     * @return int
     */
    public function getOrderUnconfirmed(): int
    {
        return (int) $this->getInfo(self::ORDER_UNCONFIRMED);
    }

    public function getOrderUnpaidTimeoutValue()
    {
        return $this->getInfo(self::ORDER_UNPAID_TIMEOUT);
    }

    public function getExpressFreightValue()
    {
        return $this->getInfo(self::EXPRESS_FREIGHT_VALUE);
    }

    public function getExpressFreightMin()
    {
        return $this->getInfo(self::EXPRESS_FREIGHT_MIN);
    }

    /**
     * @param  string  $key
     * @return string|null
     */
    public function getInfo(string $key): ?string
    {
        $keyValue = System::query()
                ->whereKeyName($key)
                ->first(['key_value'])
                ->key_value
            ?? null;

        if ('true' === $keyValue || 'TRUE' === $keyValue) {
            $keyValue = true;
        }

        if ('false' === $keyValue || 'FALSE' === $keyValue) {
            $keyValue = false;
        }

        return $keyValue;
    }
}
