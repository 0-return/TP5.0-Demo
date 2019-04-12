<?php
 // +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
header("Content-Type: application/x-www-form-urlencoded;charset=utf8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods:GET,POST");
//跨域且使用session时不能使用 *
header("Access-Control-Allow-Credentials: true" );//是否携带cookie
// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');

// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';
// 定义前台
define('BIND_MODULE', 'index');
