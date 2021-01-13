<?php
declare(strict_types=1);

namespace App\Http\Controllers\Wechat;

use App\CodeResponse;
use App\Services\Goods\BrandService;
use Illuminate\Http\Request;

class BrandController extends BaseController
{
    protected $middlewareOnly = [];

    public function list(Request $request)
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $sort = $request->input('sort', 'add_time');
        $order = $request->input('order', 'desc');

        $columns = ['id', 'name', 'desc', 'pic_url', 'floor_price'];
        $list = BrandService::getInstance()->getList(intval($page), intval($limit), $sort, $order, $columns);

        return $this->successPaginate($list);
    }

    public function detail(Request $request)
    {
        if (empty($id = $request->input('id'))) {
            return $this->fail(CodeResponse::INVALID_PARAM);
        }
        if (is_null($brand = BrandService::getInstance()->getDetail(intval($id)))) {
            return $this->fail(CodeResponse::INVALID_PARAM_VALUE);
        }

        return $this->success($brand->toArray());
    }
}
