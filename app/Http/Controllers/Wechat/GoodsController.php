<?php
declare(strict_types=1);

namespace App\Http\Controllers\Wechat;

use App\CodeResponse;
use App\Enums\SearchHistory\From;
use App\Exceptions\BusinessException;
use App\Inputs\Goods\ListInput;
use App\Services\CollectService;
use App\Services\CommentService;
use App\Services\Goods\BrandService;
use App\Services\Goods\CategoryService;
use App\Services\Goods\GoodsService;
use App\Services\SearchHistoryService;
use Illuminate\Http\JsonResponse;
use stdClass;

class GoodsController extends BaseController
{
    protected $middlewareOnly = [];

    public function count(): JsonResponse
    {
        return $this->success((array) GoodsService::getInstance()->countOnSale());
    }

    /**
     * @return JsonResponse
     *
     * @throws BusinessException
     */
    public function list(): JsonResponse
    {
        $input = ListInput::new();

        if ($this->isLogin() && !empty($keyword)) {
            SearchHistoryService::getInstance()->save($this->userId(), $keyword, From::WECHAT);
        }

        $columns = [
            'id', 'name', 'brief', 'pic_url', 'is_new', 'is_hot', 'counter_price', 'retail_price'
        ];
        $goodsList = GoodsService::getInstance()->list($input, $columns);

        $filterCategoryList = GoodsService::getInstance()->l2CategoryList($input);

        $goodsList = $this->paginate($goodsList) + compact('filterCategoryList');

        return $this->success($goodsList);
    }

    /**
     * @return JsonResponse
     *
     * @throws BusinessException
     */
    public function category(): JsonResponse
    {
        $id = $this->verifyRequiredId('id');
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

    /**
     * @return JsonResponse
     *
     * @throws BusinessException
     */
    public function detail(): JsonResponse
    {
        $id = $this->verifyRequiredId('id');
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
