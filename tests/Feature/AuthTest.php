<?php
/**
 *
 * Created By 皮神
 * Date: 2020/12/21
 */

namespace Tests\Feature;

use App\Services\Users\UserService;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use DatabaseTransactions;

    protected $unregisteredMobile;

    protected function setUp(): void
    {
        $this->unregisteredMobile = '13012121212';

        parent::setUp();
    }

    /**
     * @throws Exception
     */
    public function testRegister()
    {
        $code = UserService::getInstance()->setCaptcha($this->unregisteredMobile);
        $response = $this->post('wechat/auth/register', [
            'username' => 'pipixia',
            'password' => '123456',
            'mobile' => $this->unregisteredMobile,
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
            'password' => '123456',
            'mobile' => $this->unregisteredMobile,
            'code' => '1212'
        ]);
        $response->assertJson(['errno' => 703, 'errmsg' => '验证码错误']);
    }

    public function testRegisterMobile()
    {
        $response = $this->post('wechat/auth/register', [
            'username' => 'pipi',
            'password' => '123456',
            'mobile' => 'kdjfa;k23425',
            'code' => '1234'
        ]);
        $response->assertStatus(200);
        $response->assertJson(["errno" => 400, "errmsg" => "手机号格式不正确"]);
    }

    public function testRegCaptcha()
    {
        $response = $this->post('wechat/auth/regCaptcha');
        $response->assertJson(["errno" => 400, "errmsg" => "手机 必须是一个字符串。 手机号格式不正确"]);
        $response = $this->post('wechat/auth/regCaptcha', ['mobile' => '1111zfaaaa']);
        $response->assertJson(["errno" => 400, "errmsg" => "手机号格式不正确"]);
        $response = $this->post('wechat/auth/regCaptcha', ['mobile' => $this->unregisteredMobile]);
        $response->assertJson(['errno' => 0, 'errmsg' => '短信验证码发送成功']);
        $response = $this->post('wechat/auth/regCaptcha', ['mobile' => $this->unregisteredMobile]);
        $response->assertJson(['errno' => 702, 'errmsg' => '验证码一分钟只能获取1次']);
    }

    public function testLogin()
    {
        $response = $this->post('wechat/auth/login', ['username' => $this->user->username, 'password' => '111111']);
        self::assertEquals(700, $response['errno']);
        $response = $this->post('wechat/auth/login', ['username' => 'syp2', 'password' => '123456']);
        self::assertEquals(709, $response['errno']);
        $response = $this->post('wechat/auth/login', ['username' => $this->user->username, 'password' => '123456']);
        $token = $response->getOriginalContent()['data']['token'] ?? '';
        echo $token;
        self::assertNotEmpty($token);
    }

    public function testInfo()
    {
        $res = $this->get('wechat/auth/info', $this->getAuthHeader());
        $user = UserService::getInstance()->getByUsername($this->user->username);
        $res->assertJson([
            'data' => [
                'nickname' => $user->nickname,
                'avatar' => $user->avatar,
                'gender' => $user->gender,
                'mobile' => $user->mobile
            ]
        ]);
    }

    public function testLogout()
    {
        $headers = $this->getAuthHeader();
        $res = $this->get('wechat/auth/info', $headers);
        $user = UserService::getInstance()->getByUsername($this->user->username);
        $res->assertJson([
            'data' => [
                'nickname' => $user->nickname,
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

    /**
     * @throws Exception
     */
    public function testReset()
    {
        $mobile = $this->user->mobile;
        $password = '123456';
        $code = UserService::getInstance()->setCaptcha($mobile);
        $this->post('wechat/auth/reset',
            compact('mobile', 'password'))
            ->assertJson(['errno' => 400]);
        $this->post('wechat/auth/reset',
            compact('mobile', 'password', 'code'))
            ->assertJson(['errno' => 0]);
        self::assertTrue(Hash::check($password, UserService::getInstance()->getUserByMobile($mobile)->password));
    }

    public function testProfile()
    {
        $nickname = 'user1111';
        $this->post('wechat/auth/profile', ['nickname' => $nickname, 'gender' => 1, 'avatar' => ''],
            $this->getAuthHeader())->assertJson(['errno' => 0]);
        $user = UserService::getInstance()->getByUsername($this->user->username);
        self::assertEquals($nickname, $user->nickname);
        self::assertEquals(1, $user->gender);
    }
}
