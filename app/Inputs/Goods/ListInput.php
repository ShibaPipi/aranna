<?php
/**
 *
 * Created By 皮神
 * Date: 2021/1/15
 */

namespace App\Inputs\Goods;

use App\Inputs\Input;
use Illuminate\Validation\Rule;

class ListInput extends Input
{
    public $categoryId;
    public $brandId;
    public $isNew;
    public $isHot;
    public $keyword;
    public $sort = 'add_time';
    public $order = 'desc';
    public $page = 1;
    public $limit = 10;

    public function rules(): array
    {
        return [
            'categoryId' => 'integer|digits_between:1,20',
            'brandId' => 'integer|digits_between:1,20',
            'keyword' => 'string',
            'isNew' => 'boolean',
            'isHot' => 'boolean',
            'page' => 'integer',
            'limit' => 'integer',
            'sort' => Rule::in(['add_time', 'retail_price', 'name']),
            'order' => Rule::in(['desc', 'asc'])
        ];
    }
}
