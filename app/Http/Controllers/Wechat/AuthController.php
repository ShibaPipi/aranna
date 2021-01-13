<?php
declare(strict_types=1);

namespace App\Http\Controllers\Wechat;

use App\CodeResponse;
use App\Exceptions\BusinessException;
use App\Models\User\User;
use App\Services\User\UserService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends BaseController
{
    protected $middlewareOnly = ['info', 'profile'];

    /**
     * 账号信息修改
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $this->user();

        if (!empty($avatar = $request->input('avatar'))) {
            $user->avatar = $avatar;
        }
        if (!empty($gender = $request->input('gender'))) {
            $user->gender = $gender;
        }
        if (!empty($nickname = $request->input('nickname'))) {
            $user->nickname = $nickname;
        }

        return $this->judge($user->save(), CodeResponse::UPDATE_FAILED);
    }

    /**
     * 重置密码
     *
     * @param  Request  $request
     * @return JsonResponse
     *
     * @throws BusinessException
     */
    public function reset(Request $request): JsonResponse
    {
        if (empty($password = $request->input('password')) || empty($mobile = $request->input('mobile')) || empty($code = $request->input('code'))) {
            $this->fail(CodeResponse::INVALID_PARAM);
        }
        UserService::getInstance()->checkCaptcha($mobile, $code);
        if (is_null($user = UserService::getInstance()->getByMobile($mobile))) {
            return $this->fail(CodeResponse::AUTH_MOBILE_UNREGISTERED);
        }
        $user->password = bcrypt($password);

        return $this->judge($user->save(), CodeResponse::UPDATE_FAILED);
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
     *
     * @param  Request  $request
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function captcha(Request $request): JsonResponse
    {
        if (empty($mobile = $request->input('mobile'))) {
            return $this->fail(CodeResponse::INVALID_PARAM);
        }
        $validator = Validator::make(['mobile' => $mobile], ['mobile' => 'regex:/^1[0-9]{10}$/']);
        if ($validator->fails()) {
            return $this->fail(CodeResponse::AUTH_INVALID_MOBILE);
        }
        // 验证手机号是否被注册
        if (!is_null($user = UserService::getInstance()->getByMobile($mobile))) {
            return $this->fail(CodeResponse::AUTH_MOBILE_REGISTERED);
        }
        // 防刷验证，一分钟只能请求一次，一天只能10次
        if (!$lock = Cache::add('reg_captcha_lock_'.$mobile, 1, 60)) {
            return $this->fail(CodeResponse::AUTH_CAPTCHA_FREQUENCY, '验证码一分钟只能获取1次');
        }
        if (!UserService::getInstance()->checkRegCaptchaCount($mobile)) {
            return $this->fail(CodeResponse::AUTH_CAPTCHA_FREQUENCY, '验证码一天只能获取10次');
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
        // 获取账号密码
        $username = $request->input('username');
        $password = $request->input('password');
        // 数据验证
        if (empty($username) || empty($password)) {
            return $this->fail(CodeResponse::INVALID_PARAM);
        }
        // 验证账号是否存在
        $user = UserService::getInstance()->getByUsername($username);
        if (is_null($user)) {
            return $this->fail(CodeResponse::AUTH_NAME_REGISTERED);
        }
        // 对密码进行验证
        if (!Hash::check($password, $user->getAuthPassword())) {
            return $this->fail(CodeResponse::AUTH_INVALID_ACCOUNT, '账号密码错误');
        }
        // 更新登录信息
        $user->last_login_time = now()->toDateTimeString();
        $user->last_login_ip = $request->getClientIp();
        if (!$user->save()) {
            return $this->fail(CodeResponse::UPDATE_FAILED);

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
