<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BaseModel extends Model
{
    public const CREATED_AT = 'add_time';

    public const UPDATED_AT = 'update_time';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    protected $casts = [
        'deleted' => 'boolean'
    ];

    /**
     * @param  DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return now()->parse($date)->toDateTimeString();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $items = parent::toArray();
        $keys = array_keys($items);
        $keys = array_map(function ($key) {
            return lcfirst(Str::studly($key));
        }, $keys);

        return array_combine($keys, array_values($items));
    }
}
