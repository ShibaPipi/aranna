<?php
/**
 *
 * Created By 皮神
 * Date: 2020/12/21
 */
declare(strict_types=1);

namespace App\Services;

use App\CodeResponse;
use App\Exceptions\BusinessException;
use App\Models\User;
use App\Notifications\VerificationCode;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Leonis\Notifications\EasySms\Channels\EasySmsChannel;
use Overtrue\EasySms\PhoneNumber;

class UserService extends BaseService
{
    /**
     * @param  string  $username
     * @return User|Model|null
     */
    public function getByUsername(string $username)
    {
        return User::query()->where('username', $username)->where('deleted', 0)->first();
    }

    /**
     * @param  string  $mobile
     * @return User|Model|null
     */
    public function getByMobile(string $mobile)
    {
        return User::query()->where('mobile', $mobile)->where('deleted', 0)->first();
    }

    /**
     * @param  string  $mobile
     * @return bool
     */
    public function checkRegCaptchaCount(string $mobile)
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
     * @param  string  $mobile
     * @param  string  $code
     */
    public function sendCaptchaMsg(string $mobile, string $code)
    {
        if (app()->environment('testing')) {
            return;
        }
        Notification::route(
            EasySmsChannel::class,
            new PhoneNumber($mobile, 86)
        )->notify(new VerificationCode($code));
    }

    /**
     * 验证短信验证码
     * @param  string  $mobile
     * @param  string  $code
     * @return bool
     * @throws BusinessException
     */
    public function checkCaptcha(string $mobile, string $code)
    {
        if (app()->environment('production')) {
            $key = 'sms_captcha_'.$mobile;
            if ($code === Cache::get($key)) {
                Cache::forget($key);
            } else {
                throw new BusinessException(CodeResponse::AUTH_CAPTCHA_MISMATCH);
            }
        }
        return true;
    }

    /**
     * @param  string  $mobile
     * @return string
     * @throws Exception
     */
    public function setCaptcha(string $mobile)
    {
        $code = strval(random_int(100000, 999999));
        Cache::put('sms_captcha_'.$mobile, $code, 600);
        return $code;
    }
}
