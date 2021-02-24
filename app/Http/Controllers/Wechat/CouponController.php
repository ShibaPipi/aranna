<?php
declare(strict_types=1);

namespace App\Http\Controllers\Wechat;

use App\Exceptions\BusinessException;
use App\Inputs\PageInput;
use App\Models\Promotions\Coupon;
use App\Models\Promotions\CouponUser;
use App\Services\Promotions\CouponService;
use App\Services\Promotions\CouponUserService;
use Illuminate\Http\JsonResponse;
use Throwable;

class CouponController extends BaseController
{
    protected $middlewareExcept = ['list'];

    /**
     * @return JsonResponse
     *
     * @throws BusinessException
     */
    public function list(): JsonResponse
    {
        $pageInput = PageInput::new();
        $columns = ['id', 'name', 'desc', 'tag', 'discount', 'min', 'days', 'start_time', 'end_time'];
        $list = CouponService::getInstance()->list($pageInput, $columns);

        return $this->successPaginate($list);
    }

    /**
     * 我的优惠券列表
     *
     * @return JsonResponse
     *
     * @throws BusinessException
     * @throws Throwable
     */
    public function myList(): JsonResponse
    {
        $status = $this->verifyInteger('status');
        $page = PageInput::new();
        $list = CouponUserService::getInstance()->myCoupons($this->userId(), $page, $status);

        $couponUserList = collect($list->items());
        $couponIds = $couponUserList->pluck('coupon_id')->toArray();
        $coupons = CouponService::getInstance()->getInfoByIds($couponIds)->keyBy('id');
        $myList = $couponUserList->map(function (CouponUser $item) use ($coupons) {
            /** @var Coupon $coupon */
            $coupon = $coupons->get($item->coupon_id);

            return [
                'id' => $item->id,
                'cid' => $coupon->id,
                'name' => $coupon->name,
                'desc' => $coupon->desc,
                'tag' => $coupon->tag,
                'min' => $coupon->min,
                'discount' => $coupon->discount,
                'startTime' => $item->start_time,
                'endTime' => $item->end_time,
                'available' => false
            ];
        });

        $list = $this->paginate($list, $myList);

        return $this->success($list);
    }

    /**
     * 领取优惠券
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function receive(): JsonResponse
    {
        $couponId = $this->verifyId('couponId', 0);

        CouponUserService::getInstance()->receive($this->userId(), $couponId);

        return $this->success();
    }
}
