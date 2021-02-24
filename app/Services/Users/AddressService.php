<?php
/**
 * 地址服务层
 *
 * Created By 皮神
 * Date: 2021/1/11
 */
declare(strict_types=1);

namespace App\Services\Users;

use App\Models\Users\Address;
use App\Services\BaseService;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class AddressService extends BaseService
{
    /**
     * 获取地址，若不传地址 id 则获取用户默认地址
     *
     * @param  int  $userId
     * @param  int|null  $id
     * @return Address
     */
    public function getInfoOrDefault(int $userId, int $id = null): Address
    {
        return $id
            ? $this->getAddressById($userId, $id)
            : $this->getDefaultAddress($userId);
    }

    /**
     * 获取默认地址
     *
     * @param  int  $userId
     * @return Address
     */
    public function getDefaultAddress(int $userId): Address
    {
        return Address::query()
            ->whereUserId($userId)
            ->whereIsDefault(1)
            ->firstOrFail();
    }

    /**
     * 获取地址列表
     *
     * @param  int  $userId
     * @return Address[]|Collection
     */
    public function getListByUserId(int $userId): Collection
    {
        return Address::query()->whereUserId($userId)->get();
    }

    /**
     * 根据 id 删除地址信息
     *
     * @param $userId
     * @param $addressId
     * @return bool|null
     *
     * @throws Exception
     */
    public function delete(int $userId, int $addressId): ?bool
    {
        return $this->getAddressById($userId, $addressId)->delete();
    }

    /**
     * 根据 id 获取地址信息
     *
     * @param $userId
     * @param $addressId
     * @return Address
     */
    public function getAddressById(int $userId, int $addressId): Address
    {
        return Address::query()->whereUserId($userId)->findOrFail($addressId);
    }
}
