<?php
declare(strict_types=1);

namespace App\Http\Controllers\Wechat;

use App\CodeResponse;
use App\Enums\SearchHistory\From;
use App\Services\Goods\CategoryService;
use App\Services\Goods\GoodsService;
use App\Services\SearchHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoodsController extends BaseController
{
    protected $middlewareOnly = [];

    public function count(): JsonResponse
    {
        return $this->success((array) GoodsService::getInstance()->countOnSale());
    }

    public function list(Request $request): JsonResponse
    {
        $categoryId = intval($request->input('categoryId'));
        $brandId = intval($request->input('brandId'));
        $keyword = $request->input('keyword', '');
        $isNew = boolval($request->input('isNew'));
        $isHot = boolval($request->input('isHot'));
        $sort = $request->input('sort', 'add_time');
        $order = $request->input('order', 'desc');
        $page = intval($request->input('page', 1));
        $limit = intval($request->input('limit', 10));

        // TODO: 验证参数
        if ($this->isLogin() && !empty($keyword)) {
            SearchHistoryService::getInstance()->save($this->userId(), $keyword, From::WECHAT);
        }

        $columns = [
            'id', 'name', 'brief', 'pic_url', 'is_new', 'is_hot', 'counter_price', 'retail_price'
        ];
        // TODO: 优化参数传递
        $goodsList = GoodsService::getInstance()->list($categoryId, $brandId, $isNew, $isHot, $keyword, $columns, $sort,
            $order,
            $page, $limit);

        $filterCategoryList = GoodsService::getInstance()->l2CategoryList($brandId, $isNew, $isHot, $keyword);

        $goodsList = $this->paginate($goodsList) + compact('filterCategoryList');

        return $this->success($goodsList);
    }

    public function category(Request $request): JsonResponse
    {
        if (empty($id = $request->input('id', 0))) {
            return $this->fail(CodeResponse::INVALID_PARAM);
        }
        if (empty($currentCategory = CategoryService::getInstance()->getById(intval($id)))) {
            return $this->fail(CodeResponse::INVALID_PARAM_VALUE);
        }
        if (0 === $currentCategory->pid) {
            $parentCategory = $currentCategory;
            $brotherCategory = CategoryService::getInstance()->getL2ListByPid($currentCategory->id);
            $currentCategory = $brotherCategory->first() ?? $currentCategory;
        } else {
            $parentCategory = CategoryService::getInstance()->getL1ById($currentCategory->pid);
            $brotherCategory = CategoryService::getInstance()->getL2ListByPid($currentCategory->pid);
        }

        return $this->success(compact('currentCategory', 'parentCategory', 'brotherCategory'));
    }

    public function detail(): JsonResponse
    {

    }
}
