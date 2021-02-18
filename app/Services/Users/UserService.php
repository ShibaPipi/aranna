<?php
/**
 *
 * Created By 皮神
 * Date: 2020/12/21
 */
declare(strict_types=1);

namespace App\Services\Users;

use App\CodeResponse;
use App\Exceptions\BusinessException;
use App\Models\Users\User;
use App\Notifications\VerificationCode;
use App\Services\BaseService;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Leonis\Notifications\EasySms\Channels\EasySmsChannel;
use Overtrue\EasySms\PhoneNumber;

class UserService extends BaseService
{
    /**
     * 根据 id 获取用户
     *
     * @param  int  $userId
     * @return User|null
     */
    public function getUserById(int $userId): ?User
    {
        return User::query()->find($userId);
    }

    /**
     * 根据 id 查询多个用户
     *
     * @param  array  $ids
     * @return User[]|Collection
     */
    public function getByIds(array $ids): Collection
    {
        if (empty($ids)) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $ids)
            ->get();
    }

    /**
     * 根据用户名查询用户
     *
     * @param  string  $username
     * @return User|null
     */
    public function getByUsername(string $username): ?User
    {
        return User::query()
            ->where('username', $username)
            ->first();
    }

    /**
     * 根据手机号查询用户
     *
     * @param  string  $mobile
     * @return User|null
     */
    public function getByMobile(string $mobile): ?User
    {
        return User::query()
            ->where('mobile', $mobile)
            ->first();
    }

    /**
     * 发送短信验证码
     *
     * @param  string  $mobile
     * @return void
     *
     * @throws Exception
     */
    public function sendCaptcha(string $mobile): void
    {
        $code = self::getInstance()->setCaptcha($mobile);
        UserService::getInstance()->sendCaptchaMsg($mobile, $code);
    }

    /**
     * 验证一天内获取的验证码是否超过 10 次
     *
     * @param  string  $mobile
     * @return bool
     */
    public function checkRegCaptchaCount(string $mobile): bool
    {
        $countKey = 'sms_captcha_count_'.$mobile;

        if (Cache::has($countKey)) {
            $count = Cache::increment('sms_captcha_count_'.$mobile);
            if ($count > 10) {
                return false;
            }
        } else {
            Cache::put($countKey, 1, now()->tomorrow()->diffInSeconds(now()));
        }

        return true;
    }

    /**
     * 发送验证码短信
     *
     * @param  string  $mobile
     * @param  string  $code
     * @return void
     */
    public function sendCaptchaMsg(string $mobile, string $code): void
    {
        if ('production' === app()->environment()) {
            Notification::route(
                EasySmsChannel::class,
                new PhoneNumber($mobile, 86)
            )->notify(new VerificationCode($code));
        }
    }

    /**
     * 验证短信验证码
     *
     * @param  string  $mobile
     * @param  string  $code
     * @return bool
     *
     * @throws BusinessException
     */
    public function checkCaptcha(string $mobile, string $code): bool
    {
        $key = 'sms_captcha_'.$mobile;

        if ($code !== Cache::get($key)) {
            throw new BusinessException(CodeResponse::AUTH_CAPTCHA_MISMATCH);
        }

        Cache::forget($key);

        return true;
    }

    /**
     * 设置验证码，非生产环境固定为 6 个 1
     *
     * @param  string  $mobile
     * @return string
     *
     * @throws Exception
     */
    public function setCaptcha(string $mobile): string
    {
        $code = 'production' === app()->environment()
            ? strval(random_int(100000, 999999)) : '111111';
        Cache::put('sms_captcha_'.$mobile, $code, 600);

        return $code;
    }
}
