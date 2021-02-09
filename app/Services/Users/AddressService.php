<?php
/**
 * 地址服务层
 *
 * Created By 皮神
 * Date: 2021/1/11
 */
declare(strict_types=1);

namespace App\Services\Users;

use App\Exceptions\BusinessException;
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
     * @return Address|null
     *
     * @throws BusinessException
     */
    public function getInfoOrDefault(int $userId, int $id = null): ?Address
    {
        $address = $id ? $this->getAddressById($userId, $id) : $this->getDefaultAddress($userId);

        if (!$address) {
            $this->throwInvalidParamValueException();
        }

        return $address;
    }

    /**
     * 获取默认地址
     *
     * @param  int  $userId
     * @return Address|null
     */
    public function getDefaultAddress(int $userId): ?Address
    {
//        dd(Address::query()
//            ->whereUserId($userId)
//            ->whereIsDefault(1)
//            ->first());
        return Address::query()
            ->whereUserId($userId)
            ->whereIsDefault(1)
            ->first();
    }

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
            ->get();
    }

    /**
     * 根据 id 删除地址信息
     *
     * @param $userId
     * @param $addressId
     * @return bool|null
     *
     * @throws BusinessException
     * @throws Exception
     */
    public function delete(int $userId, int $addressId): ?bool
    {
        if (is_null($address = $this->getAddressById($userId, $addressId))) {
            $this->throwBusinessException();
        }

        return $address->delete();
    }

    /**
     * 根据 id 获取地址信息
     *
     * @param $userId
     * @param $addressId
     * @return Address|null
     */
    public function getAddressById(int $userId, int $addressId): ?Address
    {
        return Address::query()
            ->where('user_id', $userId)
            ->where('id', $addressId)
            ->first();
    }
}
