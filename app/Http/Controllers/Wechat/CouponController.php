<?php
declare(strict_types=1);

namespace App\Http\Controllers\Wechat;

use App\Exceptions\BusinessException;
use App\Inputs\PageInput;
use App\Models\Promotions\Coupon;
use App\Models\Promotions\CouponUser;
use App\Services\Promotions\CouponService;
use Illuminate\Http\JsonResponse;

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

    public function myList()
    {
        $status = $this->verifyInteger('status');
        $page = PageInput::new();
        $list = CouponService::getInstance()->mylist($this->userId(), $page, $status);

        $couponUserList = collect($list->items());
        $couponIds = $couponUserList->pluck('coupon_id')->toArray();
        $coupons = CouponService::getInstance()->getByIds($couponIds)->keyBy('id');
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
     * @throws BusinessException
     */
    public function receive(): JsonResponse
    {
        $couponId = $this->verifyId('couponId', 0);

        CouponService::getInstance()->receive($this->userId(), $couponId);

        return $this->success();
    }
}
