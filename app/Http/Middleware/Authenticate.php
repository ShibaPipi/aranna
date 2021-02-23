<?php

namespace App\Http\Middleware;

use App\Exceptions\BusinessException;
use App\Utils\ResponseCode;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  $request
     * @return string|void
     */
    protected function redirectTo($request)
    {
        if (!$request->expectsJson()) {
            return route('login');
        }
    }

    /**
     * @param  $request
     * @param  array  $guards
     * @return void
     *
     * @throws BusinessException
     * @throws AuthenticationException
     */
    protected function unauthenticated($request, array $guards)
    {
        throwBusinessException_if(
            $request->expectsJson() || in_array('wechat', $guards),
            ResponseCode::NOT_LOGIN
        );

        parent::unauthenticated($request, $guards);
    }
}
