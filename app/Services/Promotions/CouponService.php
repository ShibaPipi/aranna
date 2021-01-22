<?php
/**
 * 优惠券服务层
 *
 * Created By 皮神
 * Date: 2021/1/18
 */
declare(strict_types=1);

namespace App\Services\Promotions;

use App\CodeResponse;
use App\Enums\Coupon\CouponStatus;
use App\Enums\Coupon\CouponTimeType;
use App\Enums\Coupon\CouponType;
use App\Exceptions\BusinessException;
use App\Inputs\PageInput;
use App\Models\Promotions\Coupon;
use App\Models\Promotions\CouponUser;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class CouponService extends BaseService
{
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
            ->where('deleted', 0)
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
     * @param  array  $ids
     * @param  array|string[]  $columns
     * @return Coupon[]|Collection
     */
    public function getByIds(array $ids, array $columns = ['*'])
    {
        return Coupon::query()
            ->where('deleted', 0)
            ->whereIn('id', $ids)
            ->get($columns);
    }

    public function getById(int $id, array $columns = ['*'])
    {
        return Coupon::query()
            ->where('deleted', 0)
            ->find($id, $columns);
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
            ->where('deleted', 0)
            ->count('id');
    }

    public function countReceivedByUserId(int $userId, int $couponId): int
    {
        return CouponUser::query()
            ->where('deleted', 0)
            ->where('coupon_id', $couponId)
            ->where('user_id', $userId)
            ->count('id');
    }

    /**
     * @param  int  $userId
     * @param  int  $couponId
     * @return bool
     *
     * @throws BusinessException
     */
    public function receive(int $userId, int $couponId): bool
    {
        if (is_null($coupon = CouponService::getInstance()->getById($couponId))) {
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
