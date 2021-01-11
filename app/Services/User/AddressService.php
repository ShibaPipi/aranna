<?php
/**
 * 地址服务层
 *
 * Created By 皮神
 * Date: 2021/1/11
 */
declare(strict_types=1);

namespace App\Services\User;

use App\Models\User\Address;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;

class AddressService extends BaseService
{
    /**
     * 获取地址列表
     *
     * @param  int  $userId
     * @return Address[]|Collection
     */
    public function getListByUserId(int $userId): Collection
    {
        return Address::query()
            ->where('user_id', $userId)
            ->where('deleted', 0)
            ->get();
    }

    public function getAddress($userId, $addressId)
    {
        return Address::query()
            ->where('user_id', $userId)
            ->where('id', $addressId)
            ->where('deleted', 0)
            ->first();
    }

    public function delete($userId, $addressId)
    {
        $address = $this->getAddress($userId, $addressId);

        is_null($address) && $this->throwBusinessException();

        return $address->delete();
    }
}