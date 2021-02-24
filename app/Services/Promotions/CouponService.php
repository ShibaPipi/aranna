<?php
/**
 * 优惠券服务层
 *
 * Created By 皮神
 * Date: 2021/1/18
 */
declare(strict_types=1);

namespace App\Services\Promotions;

use App\Enums\Coupons\CouponGoodsType;
use App\Enums\Coupons\CouponStatus;
use App\Enums\Coupons\CouponTimeType;
use App\Enums\Coupons\CouponType;
use App\Inputs\PageInput;
use App\Models\Promotions\Coupon;
use App\Models\Promotions\CouponUser;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CouponService extends BaseService
{
    /**
     * 获取优惠券信息
     *     如果用户自己选择了一张优惠券，则判断该优惠券是否可以使用并返回
     *     否则，获取当前可以使用的优惠力度最大的优惠券
     *     如果用户没有优惠券，返回 null
     *
     * @param  int  $userId
     * @param  int|null  $couponId
     * @param  string  $price
     * @param  int  $availableCouponCount
     * @return CouponUser|null
     */
    public function getMeetest(int $userId, ?int $couponId, string $price, int &$availableCouponCount): ?CouponUser
    {
        $couponUsers = $this->getMeetestAndSort($userId, $price);
        $availableCouponCount = $couponUsers->count();

        if (-1 == $couponId) {
            return null;
        }

        if ($couponId) {
            $coupon = $this->getCouponById($couponId);
            $couponUser = CouponUserService::getInstance()->getCouponUserByCouponId($userId, $couponId);
            if ($this->checkUsableWithPrice($coupon, $couponUser, $price)) {
                return $couponUser;
            }
        }

        return $couponUsers->first();
    }

    /**
     * 获取用户此订单可用的、优惠力度最大的用户优惠券，并按照优惠价格从高到低排序
     *
     * @param  int  $userId
     * @param  string  $price
     * @return CouponUser[]|Collection
     */
    public function getMeetestAndSort(int $userId, string $price)
    {
        $couponUsers = CouponUserService::getInstance()->getUsableListByUserId($userId);
        $couponIds = $couponUsers->pluck('coupon_id')->toArray();
        $coupons = CouponService::getInstance()->getInfoByIds($couponIds)->keyBy('id');

        return $couponUsers->filter(function (CouponUser $couponUser) use ($price, $coupons) {
            /** @var Coupon $coupon */
            $coupon = $coupons->get($couponUser->coupon_id);

            return CouponService::getInstance()->checkUsableWithPrice($coupon, $couponUser, $price);
        })->sortByDesc(function (CouponUser $couponUser) use ($coupons) {
            /** @var Coupon $coupon */
            $coupon = $coupons->get($couponUser->coupon_id);

            return $coupon->discount;
        });
    }

    /**
     * 验证订单价格是否可以使用优惠券
     *
     * @param  Coupon  $coupon
     * @param  CouponUser  $couponUser
     * @param  string  $price
     * @return bool
     */
    public function checkUsableWithPrice(Coupon $coupon, CouponUser $couponUser, string $price): bool
    {
        if (empty($coupon)
            || $coupon->id != $couponUser->coupon_id
            || CouponStatus::NORMAL != $coupon->status
            || CouponGoodsType::ALL != $coupon->goods_type
            || 1 == bccomp(strval($coupon->min), $price)
        ) {
            return false;
        }

        $now = now();
        switch ($coupon->time_type) {
            case CouponTimeType::TIME:
                if ($now->isBefore($now->parse($coupon->start_time)) || $now->isAfter($now->parse($coupon->end_time))) {
                    return false;
                }
                break;
            case CouponTimeType::DAYS:
                $expired = $now->parse($couponUser->add_time)->addDays($coupon->days);
                if ($now->isAfter($expired)) {
                    return false;
                }
                break;
            default:
                return false;
        }

        return true;
    }

    /**
     * @param  PageInput  $input
     * @param  array|string[]  $columns
     * @return LengthAwarePaginator
     */
    public function list(PageInput $input, array $columns = ['*']): LengthAwarePaginator
    {
        return Coupon::query()
            ->where('type', CouponType::COMMON)
            ->where('status', CouponStatus::NORMAL)
            ->orderBy($input->sort, $input->order)
            ->paginate($input->limit, $columns, 'page', $input->page);
    }


    /**
     * 根据 id 列表获取优惠券
     *
     * @param  array  $ids
     * @param  array|string[]  $columns
     * @return Coupon[]|Collection
     */
    public function getInfoByIds(array $ids, array $columns = ['*'])
    {
        return Coupon::query()
            ->whereIn('id', $ids)
            ->get($columns);
    }

    /**
     * 根据 id 获取优惠券
     *
     * @param  int  $id
     * @param  array|string[]  $columns
     * @return Coupon
     */
    public function getCouponById(int $id, array $columns = ['*']): Coupon
    {
        return Coupon::query()->findOrFail($id, $columns);
    }
}
