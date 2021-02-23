<?php
declare(strict_types=1);

namespace App\Http\Controllers\Wechat;

use App\Utils\ResponseCode;
use App\Services\Goods\CategoryService;
use Illuminate\Http\JsonResponse;

class CategoryController extends BaseController
{
    protected $middlewareOnly = [];

    public function index(): JsonResponse
    {
        $categoryList = CategoryService::getInstance()->getL1Categories();
        if (empty($id = $this->verifyId())) {
            $currentCategory = $categoryList->first();
        } else {
            $currentCategory = $categoryList->where('id', $id)->first();
        }
        $currentSubCategory = !is_null($currentCategory) ? CategoryService::getInstance()->getL2CategoriesByPid($currentCategory->id) : null;

        return $this->success(compact('categoryList', 'currentCategory', 'currentSubCategory'));
    }

    public function current(): JsonResponse
    {
        if (empty($id = $this->verifyId())) {
            $this->fail(ResponseCode::INVALID_PARAM);
        }

        if (is_null($currentCategory = CategoryService::getInstance()->getL1CategoryById($id))) {
            return $this->fail(ResponseCode::INVALID_PARAM_VALUE);
        }

        $currentSubCategory = CategoryService::getInstance()->getL2CategoriesByPid($currentCategory->id);

        return $this->success(compact('currentCategory', 'currentSubCategory'));
    }
}
