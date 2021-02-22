<?php
/**
 * 物流服务层
 *
 * Created By 皮神
 * Date: 2021/2/20
 */
declare(strict_types=1);

namespace App\Services\Orders;

use App\Services\BaseService;

class ExpressService extends BaseService
{
    /**
     * Json方式 查询订单物流轨迹
     *
     * @param  string  $shipChannel
     * @param  string  $shipSn
     * @return mixed
     */
    public function getOrderTracesByJson(string $shipChannel, string $shipSn)
    {
        $requestData = "{'OrderCode':'','ShipperCode':'$shipChannel','LogisticCode':'$shipSn'}";

        $data = [
            'EBusinessID' => config('aranna.express.kdniao.app_id'),
            'RequestType' => '1002',
            'RequestData' => urlencode($requestData),
            'DataType' => '2',
            'DataSign' => $this->encrypt($requestData, config('aranna.express.kdniao.app_key')),
        ];

        return json_decode($this->sendPost(config('aranna.express.kdniao.app_url'), $data), true);
    }

    /**
     * post提交数据
     *
     * @param  string  $url  请求Url
     * @param  array  $data  提交的数据
     * @return string|false url响应返回的html
     */
    private function sendPost(string $url, array $data)
    {
        $postData = http_build_query($data);

        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => $postData,
                'timeout' => 15 * 60 // 超时时间（单位:s）
            ]
        ];

        $context = stream_context_create($options);

        return file_get_contents($url, false, $context);
    }

    /**
     * 生成签名
     *
     * @param  string  $data
     * @param  string  $appKey
     * @return string
     */
    private function encrypt(string $data, string $appKey): string
    {
        return urlencode(base64_encode(md5($data.$appKey)));
    }

    /**
     * 根据快递公司 code 获取快递公司名
     *
     * @param  string|null  $code
     * @return string
     */
    public function getExpressName(?string $code): string
    {
        return [
                'ZTO' => '中通快递',
                'YTO' => '圆通速递',
                'YD' => '韵达速递',
                'YZPY' => '邮政快递包裹',
                'EMS' => 'EMS',
                'DBL' => '德邦快递',
                'FAST' => '快捷快递',
                'ZJS' => '宅急送',
                'TNT' => 'TNT快递',
                'UPS' => 'UPS',
                'DHL' => 'DHL',
                'FEDEX' => 'FEDEX联邦(国内件)',
                'FEDEX_GJ' => 'FEDEX联邦(国际件)',
            ][$code] ?? '';
    }
}
