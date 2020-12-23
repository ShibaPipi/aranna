<?php
/**
 *
 * Created By 皮神
 * Date: 2020/12/23
 */

namespace App\Services;

class BaseService
{
    protected static $instance;

    /**
     * 防止类被外部实例化
     */
    private function __construct()
    {
    }

    /**
     * 防止类被外部克隆
     */
    private function __clone()
    {
    }

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (static::$instance instanceof static) {
            return static::$instance;
        }
        static::$instance = new static;
        return static::$instance;
    }
}
