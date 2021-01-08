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

    protected $username;
    protected $password;
    protected $mobile;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        $this->username = 'user123';
        $this->password = 'user123';
        $this->mobile = '13000000001';

        parent::__construct($name, $data, $dataName);
    }

    public function testRegister()
    {
        $code = UserService::getInstance()->setCaptcha($this->mobile);
        $response = $this->post('wechat/auth/register', [
            'username' => 'pipi',
            'password' => 123456,
            'mobile' => $this->mobile,
            'code' => $code
        ]);
        $response->assertStatus(200);
        $result = $response->getOriginalContent();
        self::assertEquals(0, $result['errno']);
        self::assertNotEmpty($result['data']);
    }

    public function testRegisterErrCode()
    {
        $response = $this->post('wechat/auth/register', [
            'username' => 'pipi',
            'password' => 123456,
            'mobile' => $this->mobile,
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
        $res = $this->get('wechat/auth/info', ['Authorization' => 'Bearer '.$this->getToken()]);
        $user = UserService::getInstance()->getByUsername($this->username);
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
        $headers = ['Authorization' => 'Bearer '.$this->getToken()];
        $res = $this->get('wechat/auth/info', $headers);
        $user = UserService::getInstance()->getByUsername($this->username);
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
        $password = '123456';
        $code = UserService::getInstance()->setCaptcha($mobile);
        $this->post('wechat/auth/reset', compact('mobile', 'password', 'code'))->assertJson(['errno' => 0]);
        self::assertTrue(Hash::check($password, UserService::getInstance()->getByMobile($mobile)->password));
    }

    public function testProfile()
    {
        $nickname = 'user1111';
        $this->post('wechat/auth/profile', ['nickname' => $nickname, 'gender' => 1, 'avatar' => ''],
            ['Authorization' => 'Bearer '.$this->getToken()])->assertJson(['errno' => 0]);
        $user = UserService::getInstance()->getByUsername($this->username);
        self::assertEquals($nickname, $user->nickname);
        self::assertEquals(1, $user->gender);
    }

    protected function getToken()
    {
        $response = $this->post('wechat/auth/login', ['username' => $this->username, 'password' => $this->password]);

        return $response->getOriginalContent()['data']['token'] ?? '';
    }
}
