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
use Illuminate\Database\Eloquent\Model;

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
            ->get();
    }

    /**
     * 根据 id 获取地址信息
     *
     * @param $userId
     * @param $addressId
     * @return Address|Model|null
     */
    public function getAddress(int $userId, int $addressId)
    {
        return Address::query()
            ->where('user_id', $userId)
            ->where('id', $addressId)
            ->first();
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
    public function delete(int $userId,int $addressId): ?bool
    {
        $address = $this->getAddress($userId, $addressId);

        is_null($address) && $this->throwBusinessException();

        return $address->delete();
    }
}
