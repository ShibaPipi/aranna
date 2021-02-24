<?php
/**
 * 用户优惠券服务层
 *
 * Created By 皮神
 * Date: 2021/2/23
 */
declare(strict_types=1);

namespace App\Services\Promotions;

use App\Enums\Coupons\CouponStatus;
use App\Enums\Coupons\CouponTimeType;
use App\Enums\Coupons\CouponType;
use App\Enums\CouponUsers\CouponUserStatus;
use App\Exceptions\BusinessException;
use App\Inputs\PageInput;
use App\Models\Promotions\CouponUser;
use App\Services\BaseService;
use App\Utils\ResponseCode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class CouponUserService extends BaseService
{
    /**
     * 领取优惠券
     *
     * @param  int  $userId
     * @param  int  $couponId
     * @return bool
     *
     * @throws BusinessException
     * @throws Throwable
     */
    public function receive(int $userId, int $couponId): bool
    {
        $coupon = CouponService::getInstance()->getCouponById($couponId);

        // 判断优惠券是否被领取完
        if ($coupon->total > 0) {
            $fetchedCount = $this->countReceived($couponId);

            $this->throwIf($fetchedCount >= $coupon->total, ResponseCode::COUPON_EXCEED_LIMIT);
        }

        // 判断用户可领取上限
        if ($coupon->limit > 0) {
            $userFetchedCount = $this->countReceivedByUserId($userId, $couponId);

            $this->throwIf($userFetchedCount >= $coupon->limit,
                ResponseCode::COUPON_EXCEED_LIMIT,
                '优惠券可领取数量已达上限');
        }

        if ($coupon->type != CouponType::COMMON) {
            $this->throwBusinessException(ResponseCode::COUPON_RECEIVE_FAIL, '优惠券类型不支持领取');
        }

        if ($coupon->status == CouponStatus::OUT) {
            $this->throwBusinessException(ResponseCode::COUPON_EXCEED_LIMIT);
        }

        if ($coupon->status == CouponStatus::EXPIRED) {
            $this->throwBusinessException(ResponseCode::COUPON_RECEIVE_FAIL, '优惠券已过期，不能领取');
        }

        if ($coupon->time_type == CouponTimeType::TIME) {
            $startTime = $coupon->start_time;
            $endTime = $coupon->end_time;
        } else {
            $startTime = now();
            $endTime = $startTime->copy()->addDays($coupon->days);
        }

        return CouponUser::new()->fill([
            'coupon_id' => $couponId,
            'user_id' => $userId,
            'start_time' => $startTime,
            'end_time' => $endTime
        ])->save();
    }

    /**
     * 获取用户优惠券列表
     *
     * @param  int  $userId
     * @param  PageInput  $input
     * @param  int|null  $status
     * @param  array|string[]  $columns
     * @return LengthAwarePaginator
     */
    public function myCoupons(
        int $userId,
        PageInput $input,
        ?int $status = null,
        array $columns = ['*']
    ): LengthAwarePaginator {
        return CouponUser::query()->where('user_id', $userId)
            ->when(!is_null($status), function (Builder $query) use ($status) {
                return $query->where('status', $status);
            })
            ->orderBy($input->sort, $input->order)
            ->paginate($input->limit, $columns, 'page', $input->page);
    }

    /**
     * 根据优惠券 id 获取用户优惠券信息
     *
     * @param  int  $userId
     * @param  int  $couponId
     * @return CouponUser
     */
    public function getCouponUserByCouponId(int $userId, int $couponId): CouponUser
    {
        return CouponUser::query()
            ->whereUserId($userId)
            ->whereCouponId($couponId)
            ->orderBy('id')
            ->firstOrFail();
    }

    /**
     * 根据 id 获取用户优惠券信息
     *
     * @param  int  $id
     * @param  array|string[]  $columns
     * @return CouponUser
     */
    public function getCouponUserById(int $id, array $columns = ['*']): CouponUser
    {
        return CouponUser::query()->findOrFail($id, $columns);
    }

    /**
     * 获取优惠券已经被领取的数量
     *
     * @param  int  $couponId
     * @return int
     */
    public function countReceived(int $couponId): int
    {
        return CouponUser::query()->whereCouponId($couponId)->count('id');
    }

    /**
     * 获取用户领取某张优惠券的数量
     *
     * @param  int  $userId
     * @param  int  $couponId
     * @return int
     */
    public function countReceivedByUserId(int $userId, int $couponId): int
    {
        return CouponUser::query()
            ->whereCouponId($couponId)
            ->whereUserId($userId)
            ->count('id');
    }

    /**
     * 获取用户可以使用的优惠券列表
     *
     * @param  int  $userId
     * @return CouponUser[]|Collection
     */
    public function getUsableListByUserId(int $userId)
    {
        return CouponUser::query()
            ->whereUserId($userId)
            ->whereStatus(CouponUserStatus::USABLE)
            ->get();
    }
}
