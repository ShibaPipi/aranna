<?php
declare(strict_types=1);

namespace App\Http\Controllers\Wechat;

use App\CodeResponse;
use App\Enums\SearchHistory\From;
use App\Services\CollectService;
use App\Services\CommentService;
use App\Services\Goods\BrandService;
use App\Services\Goods\CategoryService;
use App\Services\Goods\GoodsService;
use App\Services\SearchHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use stdClass;

class GoodsController extends BaseController
{
    protected $middlewareOnly = [];

    public function count(): JsonResponse
    {
        return $this->success((array) GoodsService::getInstance()->countOnSale());
    }

    public function list(Request $request): JsonResponse
    {
        $categoryId = $this->verifyId('categoryId');
        $brandId = $this->verifyId('brandId');
        $keyword = $this->verifyString('keyword', '');
        $isNew = $this->verifyBoolean('isNew');
        $isHot = $this->verifyBoolean('isHot');
        $sort = $this->verifyEnum('sort', 'add_time', ['add_time', 'retail_price', 'name']);
        $order = $this->verifyEnum('order', 'desc', ['desc', 'asc']);
        $page = $this->verifyInteger('page', 1);
        $limit = $this->verifyInteger('limit', 10);

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
        if (empty($id = intval($request->input('id', 0)))) {
            return $this->fail(CodeResponse::INVALID_PARAM);
        }
        if (empty($currentCategory = CategoryService::getInstance()->getById($id))) {
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

    public function detail(Request $request): JsonResponse
    {
        if (empty($id = intval($request->input('id', 0)))) {
            return $this->fail(CodeResponse::INVALID_PARAM);
        }
        if (empty($info = GoodsService::getInstance()->getById($id))) {
            return $this->fail(CodeResponse::INVALID_PARAM_VALUE);
        }

        $attribute = GoodsService::getInstance()->getAttributes($id);
        $specificationList = GoodsService::getInstance()->getSpecifications($id);
        $productList = GoodsService::getInstance()->getProducts($id);
        $issue = GoodsService::getInstance()->getIssues();
        $brand = $info->brand_id ? BrandService::getInstance()->getDetail($info->brand_id) : new stdClass();
        $comment = CommentService::getInstance()->getWithUserInfo($id);
        // TODO：团购
        $groupon = [];
        $userHasCollect = 0;
        if ($this->isLogin()) {
            $userHasCollect = CollectService::getInstance()->countByGoodsId($this->userId(), $id);
            GoodsService::getInstance()->saveFootprint($this->userId(), $id);
        }
        // TODO：系统配置
        $share = false;
        $shareImage = $info->shareUrl;

        return $this->success(compact('info', 'issue', 'userHasCollect', 'comment', 'specificationList', 'productList',
            'attribute', 'brand', 'groupon', 'share', 'shareImage'));
    }
}
