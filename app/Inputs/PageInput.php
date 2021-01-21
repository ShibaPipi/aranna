<?php
/**
 *
 * Created By 皮神
 * Date: 2021/1/18
 */
declare(strict_types=1);

namespace App\Inputs;

use Illuminate\Validation\Rule;

class PageInput extends Input
{
    public $sort = 'add_time';
    public $order = 'desc';
    public $page = 1;
    public $limit = 10;

    public function rules(): array
    {
        return [
            'page' => 'integer',
            'limit' => 'integer',
            'sort' => 'string',
            'order' => Rule::in(['desc', 'asc'])
        ];
    }
}
