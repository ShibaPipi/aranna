<?php

namespace Tests\Unit;

use App\CodeResponse;
use App\Exceptions\BusinessException;
use App\Services\Users\UserService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AuthTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testCheckRegCaptchaCount()
    {
        $mobile = '13012271786';
        foreach (range(0, 9) as $i) {
            $pass = UserService::getInstance()->checkRegCaptchaCount($mobile);
            self::assertTrue($pass);
        }
        $pass = UserService::getInstance()->checkRegCaptchaCount($mobile);
        self::assertFalse($pass);
        Cache::forget('reg_captcha_count_'.$mobile);
        $pass = UserService::getInstance()->checkRegCaptchaCount($mobile);
        self::assertFalse($pass);
    }

    /**
     * @throws BusinessException
     * @throws Exception
     */
    public function testCheckCaptcha()
    {
        $mobile = '13012271786';
        $code = UserService::getInstance()->setCaptcha($mobile);
        $pass = UserService::getInstance()->checkCaptcha($mobile, $code);
        self::assertTrue($pass);

//        $this->expectException(BusinessException::class);
//        $this->expectExceptionCode(CodeResponse::AUTH_CAPTCHA_MISMATCH[0]);
        $this->expectExceptionObject(new BusinessException(CodeResponse::AUTH_CAPTCHA_MISMATCH));
        UserService::getInstance()->checkCaptcha($mobile, $code);
    }
}
