<?php
declare(strict_types=1);

namespace App\Http\Controllers\Wechat;

use App\Utils\ResponseCode;
use App\Exceptions\BusinessException;
use App\Inputs\PageInput;
use App\Services\Goods\BrandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends BaseController
{
    protected $middlewareOnly = [];

    /**
     * 获取品牌列表
     *
     * @return JsonResponse
     *
     * @throws BusinessException
     */
    public function list(): JsonResponse
    {
        $input = PageInput::new();

        $columns = ['id', 'name', 'desc', 'pic_url', 'floor_price'];
        $list = BrandService::getInstance()->getBrands($input, $columns);

        return $this->successPaginate($list);
    }

    /**
     * 获取品牌详情
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function detail(): JsonResponse
    {
        if (empty($id = $this->verifyId())) {
            return $this->fail(ResponseCode::INVALID_PARAM);
        }

        if (is_null($brand = BrandService::getInstance()->getBrand($id))) {
            return $this->fail(ResponseCode::INVALID_PARAM_VALUE);
        }

        return $this->success($brand->toArray());
    }
}
