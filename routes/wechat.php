<?php
/**
 *
 * Created By 皮神
 * Date: 2020/12/18
 */

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', 'AuthController@register'); //账号注册
    Route::post('regCaptcha', 'AuthController@captcha'); // 获取验证码
    Route::post('login', 'AuthController@login'); //账号登录
    Route::get('info', 'AuthController@info'); //用户信息
    Route::post('logout', 'AuthController@logout'); //账号登出
    Route::post('profile', 'AuthController@profile'); //账号修改
    Route::post('reset', 'AuthController@reset'); //账号密码重置
    Route::post('captcha', 'AuthController@captcha'); // 获取验证码
});

//Route::any('home/index', ''); //首页数据接口
//Route::any('catalog/index', ''); //分类目录全部分类数据接口
//Route::any('catalog/current', ''); //分类目录当前分类数据接口
//Route::any('goods/count', ''); //统计商品总数
//Route::any('goods/list', ''); //获得商品列表
//Route::any('goods/category', ''); //获得分类数据
//Route::any('goods/detail', ''); //获得商品的详情
//Route::any('brand/list', ''); //品牌列表
//Route::any('brand/detail', ''); //品牌详情
//Route::any('cart/index', ''); //获取购物车的数据
//Route::any('cart/add', ''); //
//Route::any('cart/fastadd', ''); //
//Route::any('cart/update', ''); //
//Route::any('cart/delete', ''); //
//Route::any('cart/checked', ''); //
//Route::any('cart/goodscount', ''); //
//Route::any('cart/checkout', ''); //
//Route::any('collect/list', ''); //收藏列表
//Route::any('collect/addordelete', ''); //添加或取消收藏
//Route::any('topic/list', ''); //专题列表
//Route::any('topic/detail', ''); //专题详情
//Route::any('topic/related', ''); //相关专题
//Route::any('address/list', ''); //收货地址列表
//Route::any('address/detail', ''); //收货地址详情
//Route::any('address/save', ''); //保存收货地址
//Route::any('address/delete', ''); //保存收货地址
//Route::any('order/submit', ''); //
//Route::any('order/prepay', ''); //
//Route::any('order/h5pay', ''); //
//Route::any('order/list', ''); //订单列表
//Route::any('order/detail', ''); //订单详情
//Route::any('order/cancel', ''); //取消订单
//Route::any('order/refund', ''); //退款取消订单
//Route::any('order/delete', ''); //删除订单
//Route::any('order/confirm', ''); //确认收货
//Route::any('feedback/submit', ''); //添加反馈
//Route::any('groupon/list', ''); //团购列表
//Route::any('coupon/list', ''); //优惠券列表
//Route::any('coupon/mylist', ''); //我的优惠券列表
//Route::any('coupon/selectlist', ''); //当前订单可用优惠券列表
//Route::any('coupon/receive', ''); //优惠券领取
//Route::any('user/index', ''); //个人页面用户相关信息
//Route::any('issue/list', ''); //帮助信息any
