<?php
/**
 *
 * Created By 皮神
 * Date: 2020/12/18
 */

use Illuminate\Support\Facades\Route;

Route::post('auth/register', 'AuthController@register');
Route::post('auth/regCaptcha', 'AuthController@regCaptcha');
