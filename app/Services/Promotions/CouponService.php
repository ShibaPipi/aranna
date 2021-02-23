<?php
/**
 * 优惠券服务层
 *
 * Created By 皮神
 * Date: 2021/1/18
 */
declare(strict_types=1);

namespace App\Services\Promotions;

use App\Utils\CodeResponse;
use App\Enums\Coupons\CouponGoodsType;
use App\Enums\Coupons\CouponStatus;
use App\Enums\Coupons\CouponTimeType;
use App\Enums\Coupons\CouponType;
use App\Enums\CouponUsers\CouponUserStatus;
use App\Exceptions\BusinessException;
use App\Inputs\PageInput;
use App\Models\Promotions\Coupon;
use App\Models\Promotions\CouponUser;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CouponService extends BaseService
{
    /**
     * 获取优惠券信息
     *     如果用户自己选择了一张优惠券，则判断该优惠券是否可以使用并返回
     *     否则，获取当前可以使用的优惠力度最大的优惠券
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
            $coupon = $this->getInfoById($couponId);
            $couponUser = $this->getCouponUserByCouponId($userId, $couponId);
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
        $couponUsers = CouponService::getInstance()->getUsableListByUserId($userId);
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
     * 根据优惠券 id 获取用户优惠券信息
     *
     * @param  int  $userId
     * @param  int  $couponId
     * @return CouponUser|null
     */
    public function getCouponUserByCouponId(int $userId, int $couponId): ?CouponUser
    {
        return CouponUser::query()
            ->whereUserId($userId)
            ->whereCouponId($couponId)
            ->orderBy('id')
            ->first();
    }

    /**
     * 根据 id 获取用户优惠券信息
     *
     * @param  int  $id
     * @param  array|string[]  $columns
     * @return CouponUser|null
     */
    public function getCouponUserById(int $id, array $columns = ['*']): ?CouponUser
    {
        return CouponUser::query()->find($id, $columns);
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
            || empty($couponUser)
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
     * @param  int  $userId
     * @param  PageInput  $input
     * @param  int|null  $status
     * @param  array|string[]  $columns
     * @return LengthAwarePaginator
     */
    public function myList(
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
     * @return Coupon|null
     */
    public function getInfoById(int $id, array $columns = ['*']): ?Coupon
    {
        return Coupon::query()->find($id, $columns);
    }

    /**
     * 获取优惠券已经被领取的数量
     *
     * @param  int  $couponId
     * @return int
     */
    public function countReceived(int $couponId): int
    {
        return CouponUser::query()
            ->where('coupon_id', $couponId)
            ->count('id');
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
            ->where('coupon_id', $couponId)
            ->where('user_id', $userId)
            ->count('id');
    }

    /**
     * 领取优惠券
     *
     * @param  int  $userId
     * @param  int  $couponId
     * @return bool
     *
     * @throws BusinessException
     */
    public function receive(int $userId, int $couponId): bool
    {
        if (is_null($coupon = CouponService::getInstance()->getInfoById($couponId))) {
            $this->throwBusinessException(CodeResponse::INVALID_PARAM_VALUE);
        }
        // 判断优惠券是否被领取完
        if ($coupon->total > 0) {
            $fetchedCount = CouponService::getInstance()->countReceived($couponId);
            if ($fetchedCount >= $coupon->total) {
                $this->throwBusinessException(CodeResponse::COUPON_EXCEED_LIMIT);
            }
        }
        // 判断用户可领取上限
        if ($coupon->limit > 0) {
            $userFetchedCount = CouponService::getInstance()->countReceivedByUserId($userId, $couponId);
            if ($userFetchedCount >= $coupon->limit) {
                $this->throwBusinessException(CodeResponse::COUPON_EXCEED_LIMIT, '优惠券可领取数量已达上限');
            }
        }

        if ($coupon->type != CouponType::COMMON) {
            $this->throwBusinessException(CodeResponse::COUPON_RECEIVE_FAIL, '优惠券类型不支持领取');
        }

        if ($coupon->status == CouponStatus::OUT) {
            $this->throwBusinessException(CodeResponse::COUPON_EXCEED_LIMIT);
        }

        if ($coupon->status == CouponStatus::EXPIRED) {
            $this->throwBusinessException(CodeResponse::COUPON_RECEIVE_FAIL, '优惠券已过期，不能领取');
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
}
