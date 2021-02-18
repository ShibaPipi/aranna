<?php

namespace App\Models;

use Closure;
use DateTimeInterface;
use Eloquent;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

/**
 * App\Models\BaseModel
 *
 * @method static Builder|BaseModel newModelQuery()
 * @method static Builder|BaseModel newQuery()
 * @method static Builder|BaseModel query()
 * @mixin Eloquent
 */
class BaseModel extends Model
{
    use BooleanSoftDeletes;

    public const CREATED_AT = 'add_time';

    public const UPDATED_AT = 'update_time';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    protected $defaultCasts = [
        'deleted' => 'boolean'
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->mergeCasts($this->defaultCasts);
    }

    /**
     * @param  DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return now()->parse($date)->toDateTimeString();
    }

    /**
     * 手动将数据对象转数组，并将数据字典的键名转换成小驼峰
     *
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

    /**
     * 创建对象的静态方法
     *
     * @return static
     */
    public static function new()
    {
        return new static;
    }

    /**
     * Compare And Swap / Set，乐观锁更新
     *
     * @return int
     *
     * @throws Throwable
     */
    public function cas(): int
    {
        // 如果模型不存在，则抛出异常
        throw_if(!self::exists(), Exception::class, "Model doesn't exist when cas!");

        // 如果没有需要更新的字段，则抛出异常
        if (empty($dirty = $this->getDirty())) {
            return 0;
        }

        // 如果开启时间戳，则主动获取时间戳
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
            $dirty = $this->getDirty();
        }

        // 如果到更新的字段没有被查询出来，则抛出异常
        $diff = array_diff(array_keys($dirty), array_keys($this->original));
        throw_if(!empty($diff), Exception::class, 'Key ['.implode(', ', $diff).'] not exists when cas!');

        // 触发 casing 事件
        if (false === $this->fireModelEvent('casing')) {
            return 0;
        }

        // 组装乐观锁的查询构造器，newModelQuery() 可以不执行软删除字段的查询逻辑
        $query = self::newModelQuery()->where($this->getKeyName(), $this->getKey());
        foreach ($dirty as $key => $value) {
            $query = $query->where($key, $this->getOriginal($key));
        }

        $row = $query->update($dirty);
        if ($row > 0) {
            // 同步模型的 change 属性和 original 属性，触发 cased 事件
            $this->syncChanges();
            $this->fireModelEvent('cased', false);
            $this->syncOriginal();
        }

        return $row;
    }

    /**
     * Register a casing model event with the dispatcher.
     *
     * @param  Closure|string  $callback
     * @return void
     */
    public static function casing($callback)
    {
        static::registerModelEvent('casing', $callback);
    }

    /**
     * Register a cased model event with the dispatcher.
     *
     * @param  Closure|string  $callback
     * @return void
     */
    public static function cased($callback)
    {
        static::registerModelEvent('cased', $callback);
    }
}
