<?php
return array(
	//'配置项'=>'配置值'
    'TMPL_PARSE_STRING' => array(
        // '__APP_STATIC__' => __ROOT__.'/Application/static',
        // '__APP_URL__' => __ROOT__."/home"
    ),
    'URL_MODEL' => 2,
    "URL_CASE_INSENSITIVE" => true,
    "URL_HTML_SUFFIX"  => ".html",
    //设置时区
    'DEFAULT_TIMEZONE'=>'America/New_York',

    //自定义配置参数  

    //缓存配置
    "AB_CACHE_EXPIRE_TIME" => 1800,    //默认通用缓存时间
    "AB_CACHE_PREFIX" => "ab",          //默认通用缓存前缀
    "AB_MEMCACHE_HOST"=>"127.0.0.1",    //默认memcache地址
    "AB_MEMCACHE_PORT"=>"3306",         //默认memcahce端口 
    "AB_CACHE_TYPE"=>"1",   //缓存type = 1file缓存方式， 2是memcache
    'DATA_CACHE_KEY'=>'artbean', //如果你使用的是文件方式的缓存机制，那么可以设置DATA_CACHE_KEY参数，避免缓存文件名被猜测到
    
    //web url 域名配置
     '__AB_WEB_URL__'=> AB_WEB_URL,  //web端域名地址,上线修改，在首页index.php
    // "AB_FRONTED" => "http://".AB_WEB_URL."/art/",
    // "AB_BACKEND" => "http://".AB_WEB_URL."/artbean/",
    //验证邮编url
    'CHECKZIPCODEURL'=> AB_FRONTED."ups/validationAddress",
    //获取运费url
    
    'GETSHIPMONEYURL'=>  AB_FRONTED."ups/ratingPackage",
    //获取超重运费url
    "GETFEIGHTSHIPMONEYURL" => AB_FRONTED."ups/ratingFeightPackage",
    //获取物流信息
    "TRACKPACKAGEURL" => AB_FRONTED."ups/tracking",
    //一次性信用卡支付接口地址
    "PAYCREDIT" => AB_FRONTED."bank/chargeCreditCard",
    //获取税费
    "TAXESURL" =>  AB_FRONTED."bank/taxAvc",
    //注册接口
    "REGISTER"=> AB_FRONTED."abBaseUser/addmember",
    //忘记密码
    "FORGETPASSWORD"=>AB_FRONTED."abBaseUser/forgetPassword",

    //发货方式名称配置 及 发货方式代码
    //ups物流运输方式
    "UPSMETHOD"=> array("UPS Ground 3-5 days","UPS 2nd Day Air", 'UPS NEXT Day Air'),
    "UPSCODE" => array("03", "02", "01"),
    "UPS"=>array("03"=>"UPS Ground 3-5 days","02"=>"UPS 2nd Day Air", "01"=>"UPS NEXT Day Air"),
    //大尺寸商品 尺寸参数
    "UPSMAXSIZE" => 165,
    "UPSMAXMETHOD" => array('UPS Freight LTL'),
    "UPSMAXCODE"=>array('308'),
    "UPSMAX"=>array("308"=>"UPS Freight LTL"),
    "UPSISFREE"=>"UPS",
    //修改密码的 时间有效期30分钟
    "CKPSDTIME" => 1800,
     //登录有效时间 为7天
    "SESSIONTIME" => 604800,

    //画框成本价系数
    "RAHMEN_PRICE_PARAM" => 1.5,

    "SHOPCARTMAXNUM" =>40,

    //艺术家提交图片保存ip
    "IMG_URL"=>AB_BACKEND_IMG_URL.":8089/imageface/image",

    //商品画框外线条尺寸大小对应的code 
    "INLINE_SIZE_S" => "8609",
    "INLINE_SIZE_M" => "8610",
    "INLINE_SIZE_L" => "9999",

    // 配置邮件发送服务器(本地测试用)
    // 'MAIL_HOST' =>'smtp.163.com',//smtp服务器的名称
    // 'MAIL_SECURE'=>'ssl',
    // 'MAIL_SMTPAUTH' =>TRUE, //启用smtp认证
    // 'MAIL_USERNAME' =>'Artbean',//你的邮箱名
    // 'MAIL_FROM' =>'wuzhi7306496@163.com',//发件人地址
    // 'MAIL_FROMNAME'=>'Artbean',//发件人姓名
    // 'MAIL_PASSWORD' =>'19920327zhi',//邮箱密码
    // 'MAIL_CHARSET' =>'utf-8',//设置邮件编码
    // 'MAIL_ISHTML' =>TRUE, // 是否HTML格式邮件



    // 配置邮件发送服务器
    // 'MAIL_HOST' =>'smtp.office365.com',//smtp服务器的名称
    // 'MAIL_SECURE'=>'tls',
    // 'MAIL_PORT'=> '587',
    // 'MAIL_SMTPAUTH' =>TRUE, //启用smtp认证
    // 'MAIL_USERNAME' =>'webtest@artbean.com',//你的邮箱名
    // 'MAIL_FROM' =>'webtest@artbean.com',//发件人地址
    // 'MAIL_FROMNAME'=>'Artbean',//发件人姓名
    // 'MAIL_PASSWORD' =>'Mufo85722',//邮箱密码
    // 'MAIL_CHARSET' =>'utf-8',//设置邮件编码
    // 'MAIL_ISHTML' =>TRUE, // 是否HTML格式邮件

    'MAIL_HOST' =>'smtp.mandrillapp.com',//smtp服务器的名称
    'MAIL_SECURE'=>'tls',
    'MAIL_PORT'=> '587',
    'MAIL_SMTPAUTH' =>TRUE, //启用smtp认证
    'MAIL_USERNAME' =>'NOTIFICATIONS@ARTBEAN.COM',//你的邮箱名
    'MAIL_FROM' =>'NOTIFICATIONS@ARTBEAN.COM',//发件人地址
    'MAIL_FROMNAME'=>'Artbean',//发件人姓名
    'MAIL_PASSWORD' =>'rawgdIlhVJf5ph4hGg5tag',//邮箱密码
    'MAIL_CHARSET' =>'utf-8',//设置邮件编码
    'MAIL_ISHTML' =>TRUE, // 是否HTML格式邮件


    //语言包功能
    'LANG_SWITCH_ON'     =>     true,    //开启语言包功能        
    'LANG_AUTO_DETECT'     =>     false, // 取消自动侦测语言
    'DEFAULT_LANG'         =>     'en-us', // 默认语言        
    'LANG_LIST'            =>    'en-us', //必须写可允许的语言列表
    'VAR_LANGUAGE'     => 'l', // 默认语言切换变量

    'DB_PARAMS'    =>    array(\PDO::ATTR_CASE => \PDO::CASE_NATURAL),   //数据库字段大小写，默认全小写，改为正常

);