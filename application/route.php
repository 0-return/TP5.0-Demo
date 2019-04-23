<?php
 // +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\Route;

//首页文章
Route::post('api_web/:version/article', 'api_web/:version.Home/article');
//导航
Route::post('api_web/:version/nav', 'api_web/:version.Plug/nav');
//协议
Route::post('api_web/:version/agreement', 'api_web/:version.Plug/agreement');
//图片轮播
Route::post('api_web/:version/advert', 'api_web/:version.Plug/advert');




//所有路由匹配不到情况下触发该路由
Route::miss('\app\Exception::miss');
