<?php

namespace Tests\Feature;

use App\Services\UserService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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

}
