<?php
/**
 *
 * Created By 皮神
 * Date: 2020/12/21
 */
declare(strict_types=1);

namespace App\Services\User;

use App\CodeResponse;
use App\Exceptions\BusinessException;
use App\Models\User\User;
use App\Notifications\VerificationCode;
use App\Services\BaseService;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Leonis\Notifications\EasySms\Channels\EasySmsChannel;
use Overtrue\EasySms\PhoneNumber;

class UserService extends BaseService
{
    /**
     * @param  array  $ids
     * @return User[]|Collection
     */
    public function getByIds(array $ids)
    {
        if (empty($ids)) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $ids)
            ->where('deleted', 0)
            ->get();
    }

    /**
     * @param  string  $username
     * @return User|Model|null
     */
    public function getByUsername(string $username)
    {
        return User::query()
            ->where('username', $username)
            ->where('deleted', 0)
            ->first();
    }

    /**
     * @param  string  $mobile
     * @return User|Model|null
     */
    public function getByMobile(string $mobile)
    {
        return User::query()
            ->where('mobile', $mobile)
            ->where('deleted', 0)
            ->first();
    }

    /**
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

        if ($code === Cache::get($key)) {
            Cache::forget($key);
        } else {
            throw new BusinessException(CodeResponse::AUTH_CAPTCHA_MISMATCH);
        }

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
        if ('production' !== app()->environment()) {
            $code = '111111';
        } else {
            $code = strval(random_int(100000, 999999));
        }
        Cache::put('sms_captcha_'.$mobile, $code, 600);

        return $code;
    }
}
