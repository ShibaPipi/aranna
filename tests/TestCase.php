<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

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

    /**
     * 获取 token
     *
     * @return mixed|string
     */
    protected function getToken()
    {
        $response = $this->post('wechat/auth/login', ['username' => $this->username, 'password' => $this->password]);

        return $response->getOriginalContent()['data']['token'] ?? '';
    }

    /**
     * 获取 header
     *
     * @return array string[]
     */
    protected function getAuthHeader(): array
    {
        return ['Authorization' => 'Bearer '.$this->getToken()];
    }
}
