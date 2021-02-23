<?php
declare(strict_types=1);

namespace Tests;

use App\Models\Users\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * @var User $user
     */
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = factory(User::class)->create();
    }

    /**
     * 获取 token
     *
     * @param  string  $username
     * @param  string  $password
     * @return mixed|string
     */
    protected function getToken(string $username, string $password): string
    {
        $response = $this->post('wechat/auth/login', compact('username', 'password'));

        return $response->getOriginalContent()['data']['token'] ?? '';
    }

    /**
     * 获取 header
     *
     * @return array string[]
     */
    protected function getAuthHeader(string $username = '', string $password = ''): array
    {
        $username = $username ?: $this->user->username;
        $password = $password ?: '123456';

        return ['Authorization' => 'Bearer '.$this->getToken($username, $password)];
    }
}
