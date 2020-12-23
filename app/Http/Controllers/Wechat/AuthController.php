<?php
declare(strict_types=1);

namespace App\Http\Controllers\Wechat;

use App\CodeResponse;
use App\Exceptions\BusinessException;
use App\Models\User;
use App\Services\UserService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class AuthController extends BaseController
{
    /**
     * 注册逻辑
     * @param  Request  $request
     * @return JsonResponse
     *
     * @throws BusinessException
     */
    public function register(Request $request): JsonResponse
    {
        // 获取参数
        $username = $request->input('username');
        $password = $request->input('password');
        $mobile = $request->input('mobile');
        $code = $request->input('code');
        if (empty($username) || empty($password) || empty($mobile) || empty($code)) {
            return $this->fail(CodeResponse::INVALID_PARAM);
        }
        $user = UserService::getInstance()->getByUsername($username);
        if (!is_null($user)) {
            return $this->fail(CodeResponse::AUTH_NAME_REGISTERED);
        }
        $validator = Validator::make(['mobile' => $mobile], ['mobile' => 'regex:/^1[0-9]{10}$/']);
        if ($validator->fails()) {
            return $this->fail(CodeResponse::AUTH_INVALID_MOBILE);
        }
        $user = UserService::getInstance()->getByMobile($mobile);
        if (!is_null($user)) {
            return $this->fail(CodeResponse::AUTH_MOBILE_REGISTERED);
        }
        // 验证短信验证码
        UserService::getInstance()->checkCaptcha($mobile, $code);
        // 写入用户表
        $user = new User;
        $user->username = $username;
        $user->password = bcrypt($password);
        $user->mobile = $mobile;
        $user->avatar = 'https://yanxuan.nosdn.127.net/80841d741d7fa3073e0ae27bf487339f.jpg?imageView&quality=90&thumbnail=64x64';
        $user->nickname = $username;
        $user->last_login_time = now()->toDateTimeString();
        $user->last_login_ip = $request->getClientIp();
        $user->save();

        return $this->success([
            'token' => '',
            'userInfo' => [
                'nickname' => $username,
                'avatarUrl' => $user->avatar
            ]
        ]);
    }

    /**
     * 发送短信验证码
     * @param  Request  $request
     * @return JsonResponse
     * @throws Exception
     */
    public function regCaptcha(Request $request): JsonResponse
    {
        // 获取手机号
        $mobile = $request->input('mobile');
        if (empty($mobile)) {
            return $this->fail(CodeResponse::INVALID_PARAM);
        }
        $validator = Validator::make(['mobile' => $mobile], ['mobile' => 'regex:/^1[0-9]{10}$/']);
        if ($validator->fails()) {
            return $this->fail(CodeResponse::AUTH_INVALID_MOBILE);
        }
        // 验证手机号是否被注册
        $user = UserService::getInstance()->getByMobile($mobile);
        if (!is_null($user)) {
            return $this->fail(CodeResponse::AUTH_MOBILE_REGISTERED);
        }
        // 防刷验证，一分钟只能请求一次，一天只能10次
        $lock = Cache::add('reg_captcha_lock_'.$mobile, 1, 60);
        if (!$lock) {
            return $this->fail(CodeResponse::AUTH_CAPTCHA_FREQUENCY, '验证码一分钟只能获取1次');
        }
        if (!UserService::getInstance()->checkRegCaptchaCount($mobile)) {
            return $this->fail(CodeResponse::AUTH_CAPTCHA_FREQUENCY, '验证码一天只能获取10次');
        }
        // 随机生成6位验证码
        $code = UserService::getInstance()->setCaptcha($mobile);
        // 发送短信
        UserService::getInstance()->sendCaptchaMsg($mobile, $code);

        return $this->success(null, '短信验证码发送成功');
    }
}
