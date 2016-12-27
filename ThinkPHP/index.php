<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用入口文件

// 检测PHP环境
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');

// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG',True);
//线上真是环境地址
define('AB_WEB_URL', "50.97.37.167:8080");
//前台地址
define("AB_FRONTED", "http://".AB_WEB_URL."/art/");
//后台引用图片地址
define("AB_BACKEND", "http://".AB_WEB_URL."/artbean/");


//前台地址
define("AB_FRONTED_IMG_URL", "http://".AB_WEB_URL."/art/");
//后台引用图片地址
define("AB_BACKEND_IMG_URL", "http://50.97.37.171");
// 定义应用目录
define('APP_PATH','./Application/');

// 引入ThinkPHP入口文件
require './ThinkPHP/ThinkPHP.php';

// 亲^_^ 后面不需要任何代码了 就是如此简单