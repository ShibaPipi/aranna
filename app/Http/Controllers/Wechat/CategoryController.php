<?php
declare(strict_types=1);

namespace App\Http\Controllers\Wechat;

use App\CodeResponse;
use App\Services\Goods\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends BaseController
{
    protected $middlewareOnly = [];

    public function index(Request $request): JsonResponse
    {
        $categoryList = CategoryService::getInstance()->getL1List();
        if (empty($id = $request->input('id'))) {
            $currentCategory = $categoryList->first();
        } else {
            $currentCategory = $categoryList->where('id', $id)->first();
        }
        $currentSubCategory = !is_null($currentCategory) ? CategoryService::getInstance()->getL2ListByPid($currentCategory->id) : null;

        return $this->success(compact('categoryList', 'currentCategory', 'currentSubCategory'));
    }

    public function current(Request $request): JsonResponse
    {
        if (empty($id = $request->input('id'))) {
            $this->fail(CodeResponse::INVALID_PARAM);
        }
        if (is_null($currentCategory = CategoryService::getInstance()->getL1ById(intval($id)))) {
            return $this->fail(CodeResponse::INVALID_PARAM_VALUE);
        }
        $currentSubCategory = CategoryService::getInstance()->getL2ListByPid($currentCategory->id);

        return $this->success(compact('currentCategory', 'currentSubCategory'));
    }
}
