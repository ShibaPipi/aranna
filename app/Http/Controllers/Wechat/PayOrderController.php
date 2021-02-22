<?php
/**
 * 订单支付控制器
 *
 * Created By 皮神
 * Date: 2020/2/22
 */
declare(strict_types=1);

namespace App\Http\Controllers\Wechat;

use App\Exceptions\BusinessException;
use App\Services\Orders\PayOrderService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Yansongda\LaravelPay\Facades\Pay;
use Yansongda\Pay\Exceptions\GatewayException;
use Yansongda\Pay\Exceptions\InvalidArgumentException;
use Yansongda\Pay\Exceptions\InvalidConfigException;
use Yansongda\Pay\Exceptions\InvalidSignException;

class PayOrderController extends BaseController
{
    protected $middlewareExcept = [
        'wechatNotify',
        'alipayNotify',
        'alipayReturn'
    ];

    /**
     * 支付宝同步回调
     *
     * @return Application|\Illuminate\Http\RedirectResponse|Redirector
     *
     * @throws InvalidConfigException
     * @throws InvalidSignException
     * @throws Throwable
     * @throws GatewayException
     */
    public function alipayReturn()
    {
        $data = Pay::alipay()->find(request()->input())->toArray();

        Log::info('Alipay return', $data);

        DB::transaction(function () use ($data) {
            PayOrderService::getInstance()->alipayNotify($data);
        });

        return redirect(env('H5_URL').'/#/user/order/list/0');
    }

    /**
     * 支付宝回调
     *
     * @return Response
     *
     * @throws InvalidSignException
     * @throws Throwable
     * @throws InvalidConfigException
     */
    public function alipayNotify(): Response
    {
        $data = Pay::alipay()->verify()->toArray();

        Log::info('Alipay notify', $data);

        DB::transaction(function () use ($data) {
            PayOrderService::getInstance()->alipayNotify($data);
        });

        return Pay::alipay()->success();
    }

    /**
     * 支付宝手机浏览器支付
     *
     * @return JsonResponse
     * @throws BusinessException
     * @throws Throwable
     */
    public function h5alipay(): JsonResponse
    {
        $orderId = $this->verifyId('orderId', 0);

        $data = Pay::alipay()->wap(
            PayOrderService::getInstance()->getAlipayParams($this->userId(), $orderId)
        )->getContent();

        return $this->success(compact('data'));
    }

    /**
     * 微信回调
     *
     * @return Response
     *
     * @throws Throwable
     * @throws InvalidArgumentException
     * @throws InvalidSignException
     */
    public function wechatNotify(): Response
    {
        $data = Pay::wechat()->verify()->toArray();

        Log::info('Wechat notify', $data);

        DB::transaction(function () use ($data) {
            PayOrderService::getInstance()->wechatNotify($data);
        });

        return Pay::wechat()->success();
    }

    /**
     * 微信手机浏览器支付
     *
     * @return RedirectResponse
     *
     * @throws BusinessException|Throwable
     */
    public function h5wechat(): RedirectResponse
    {
        $orderId = $this->verifyId('orderId', 0);

        return Pay::wechat()->wap(
            PayOrderService::getInstance()->getWechatPayParams($this->userId(), $orderId)
        );
    }
}
