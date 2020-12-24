<?php

namespace Tests\Feature;

use App\Services\UserService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 *
 * Created By 皮神
 * Date: 2020/12/21
 */
class AuthTest extends TestCase
{
    use DatabaseTransactions;

    public function testRegister()
    {
        $mobile = '13000000001';
        $code = UserService::getInstance()->setCaptcha($mobile);
        $response = $this->post('wechat/auth/register', [
            'username' => 'pipi',
            'password' => 123456,
            'mobile' => $mobile,
            'code' => $code
        ]);
        $response->assertStatus(200);
        $result = $response->getOriginalContent();
        self::assertEquals(0, $result['errno']);
        self::assertNotEmpty($result['data']);
    }

    public function testRegisterErrCode()
    {
        $mobile = '13000000001';
        $response = $this->post('wechat/auth/register', [
            'username' => 'pipi',
            'password' => 123456,
            'mobile' => $mobile,
            'code' => '1212'
        ]);
        $response->assertJson(['errno' => 703, 'errmsg' => '验证码错误']);
    }

    public function testRegisterMobile()
    {
        $response = $this->post('wechat/auth/register', [
            'username' => 'pipi',
            'password' => 123456,
            'mobile' => 13012272221786,
            'code' => 1234
        ]);
        $response->assertStatus(200);
        $result = $response->getOriginalContent();
        self::assertEquals(707, $result['errno']);
    }

    public function testRegCaptcha()
    {
        $response = $this->post('wechat/auth/regCaptcha', ['mobile' => '13682169909']);
        $response->assertJson(['errno' => 0, 'errmsg' => '短信验证码发送成功']);
        $response = $this->post('wechat/auth/regCaptcha', ['mobile' => '13682169909']);
        $response->assertJson(['errno' => 702, 'errmsg' => '验证码一分钟只能获取1次']);
    }

    public function testLogin()
    {
        $response = $this->post('wechat/auth/login', ['username' => 'syp', 'password' => 123456]);
        $token = $response->getOriginalContent()['data']['token'] ?? '';
        echo $token;
        self::assertNotEmpty($token);
    }

    public function testInfo()
    {
        $username = 'user123';
        $response = $this->post('wechat/auth/login', ['username' => $username, 'password' => 'user123']);
        $token = $response->getOriginalContent()['data']['token'] ?? '';
        $res = $this->get('wechat/auth/info', ['Authorization' => 'Bearer '.$token]);
        $user = UserService::getInstance()->getByUsername($username);
        $res->assertJson([
            'data' => [
                'nickName' => $user->nickanme,
                'avatar' => $user->avatar,
                'gender' => $user->gender,
                'mobile' => $user->mobile
            ]
        ]);
    }

    public function testLogout()
    {
        $username = 'user123';
        $response = $this->post('wechat/auth/login', ['username' => $username, 'password' => 'user123']);
        $token = $response->getOriginalContent()['data']['token'] ?? '';
        $headers = ['Authorization' => 'Bearer '.$token];
        $res = $this->get('wechat/auth/info', $headers);
        $user = UserService::getInstance()->getByUsername($username);
        $res->assertJson([
            'data' => [
                'nickName' => $user->nickanme,
                'avatar' => $user->avatar,
                'gender' => $user->gender,
                'mobile' => $user->mobile
            ]
        ]);
        $logoutRes = $this->post('wechat/auth/logout', [], $headers);
        $logoutRes->assertJson(['errno' => 0]);
        $infoRes = $this->get('wechat/auth/info', $headers);
        $infoRes->assertJson(['errno' => 501]);
    }

    public function testReset()
    {
        $mobile = '13012271786';
        $pwd = '123456';
        $code = UserService::getInstance()->setCaptcha($mobile);
        $this->post('wechat/auth/reset',
            ['mobile' => $mobile, 'password' => $pwd, 'code' => $code]
        )->assertJson(['errno' => 0]);
        self::assertTrue(Hash::check($pwd, UserService::getInstance()->getByMobile($mobile)->password));
    }

    public function testProfile()
    {
        $nickname = 'user1111';
        $this->post('wechat/auth/profile',
            ['nickname' => $nickname, 'gender' => 1, 'avatar' => '']
        )->assertJson(['errno' => 0]);
        $user = UserService::getInstance()->getByUsername($nickname);
        self::assertEquals($nickname, $user->nickname);
        self::assertEquals(1, $user->gender);
    }
}
