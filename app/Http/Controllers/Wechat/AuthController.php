<?php
declare(strict_types=1);

namespace App\Http\Controllers\Wechat;

use App\Utils\ResponseCode;
use App\Enums\Users\UserGender;
use App\Exceptions\BusinessException;
use App\Models\Users\User;
use App\Services\Users\UserService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Throwable;

class AuthController extends BaseController
{
    protected $middlewareOnly = ['info', 'profile'];

    /**
     * 账号信息修改
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function profile(): JsonResponse
    {
        $user = $this->user();

        if (!empty($avatar = $this->verifyString('avatar'))) {
            $user->avatar = $avatar;
        }

        if (!empty($gender = $this->verifyEnum('gender', '', UserGender::ALL))) {
            $user->gender = $gender;
        }

        if (!empty($nickname = $this->verifyString('nickname'))) {
            $user->nickname = $nickname;
        }

        return $this->judge($user->save(), ResponseCode::UPDATE_FAILED);
    }

    /**
     * 重置密码
     *
     * @return JsonResponse
     *
     * @throws BusinessException|Throwable
     */
    public function reset(): JsonResponse
    {
        $password = $this->verifyRequiredString('password');
        $mobile = $this->verifyMobile('mobile', 0);
        $code = $this->verifyRequiredString('code');

        UserService::getInstance()->checkCaptcha($mobile, $code);

        if (is_null($user = UserService::getInstance()->getUserByMobile($mobile))) {
            return $this->fail(ResponseCode::AUTH_MOBILE_UNREGISTERED);
        }

        $user->password = bcrypt($password);

        return $this->judge($user->save(), ResponseCode::UPDATE_FAILED);
    }

    /**
     * 登出
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        auth('wechat')->logout();

        return $this->success();
    }

    /**
     * 用户信息
     *
     * @return JsonResponse
     */
    public function info(): JsonResponse
    {
        $user = $this->user();

        return $this->success([
            'nickname' => $user->nickname,
            'avatar' => $user->avatar,
            'gender' => $user->gender,
            'mobile' => $user->mobile
        ]);
    }

    /**
     * 注册逻辑
     *
     * @param  Request  $request
     * @return JsonResponse
     *
     * @throws BusinessException
     */
    public function register(Request $request): JsonResponse
    {
        $username = $this->verifyRequiredString('username');
        $password = $this->verifyRequiredString('password');
        $mobile = $this->verifyMobile('mobile');
        $code = $this->verifyRequiredString('code');

        if (!empty($user = UserService::getInstance()->getByUsername($username))) {
            return $this->fail(ResponseCode::AUTH_NAME_REGISTERED);
        }

        if (!empty($user = UserService::getInstance()->getUserByMobile($mobile))) {
            return $this->fail(ResponseCode::AUTH_MOBILE_REGISTERED);
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
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function captcha(): JsonResponse
    {
        $mobile = $this->verifyMobile('mobile', 0);
        // 验证手机号是否被注册
        if (!empty($user = UserService::getInstance()->getUserByMobile($mobile))) {
            return $this->fail(ResponseCode::AUTH_MOBILE_REGISTERED);
        }

        // 防刷验证，一分钟只能请求一次，一天只能10次
        if (!$lock = Cache::add('reg_captcha_lock_'.$mobile, 1, 60)) {
            return $this->fail(ResponseCode::AUTH_CAPTCHA_FREQUENCY, '验证码一分钟只能获取1次');
        }

        if (!UserService::getInstance()->checkRegCaptchaCount($mobile)) {
            return $this->fail(ResponseCode::AUTH_CAPTCHA_FREQUENCY, '验证码一天只能获取10次');
        }

        UserService::getInstance()->sendCaptcha($mobile);

        return $this->success(null, '短信验证码发送成功');
    }

    /**
     * 登录
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $username = $this->verifyRequiredString('username');
        $password = $this->verifyRequiredString('password');

        // 验证账号是否存在
        if (empty($user = UserService::getInstance()->getByUsername($username))) {
            return $this->fail(ResponseCode::AUTH_NAME_UNREGISTERED);
        }

        // 对密码进行验证
        if (!Hash::check($password, $user->getAuthPassword())) {
            return $this->fail(ResponseCode::AUTH_INVALID_ACCOUNT, '账号密码错误');
        }

        // 更新登录信息
        $user->last_login_time = now()->toDateTimeString();
        $user->last_login_ip = $request->getClientIp();
        if (!$user->save()) {
            return $this->fail(ResponseCode::UPDATE_FAILED);
        }

        // 获取 token
        $token = auth('wechat')->login($user);

        // 组装数据并返回
        return $this->success([
            'token' => $token,
            'userInfo' => [
                'nickname' => $user->nickname,
                'avatarUrl' => $user->avatar
            ]
        ]);
    }
}
