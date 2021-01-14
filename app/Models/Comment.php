<?php
declare(strict_types=1);

namespace App\Models;

class Comment extends BaseModel
{
    protected $table = 'comment';

    protected $casts = [
        'pic_urls' => 'array',
        'deleted' => 'boolean'
    ];
}
