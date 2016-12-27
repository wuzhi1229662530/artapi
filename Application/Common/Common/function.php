<?php
/**
 * 邮件发送函数
 */
function sendMail($to, $subject, $body) {
 
    Vendor('PHPMailer.PHPMailerAutoload');     
    $mail = new PHPMailer(); //实例化
    $mail->IsSMTP(); // 启用SMTP
    $mail->Host=C('MAIL_HOST'); //smtp服务器的名称（这里以QQ邮箱为例）
    $mail->Port = C('MAIL_PORT');
    $mail->SMTPSecure = C('MAIL_SECURE');
    $mail->SMTPAuth = C('MAIL_SMTPAUTH'); //启用smtp认证
    $mail->Username = C('MAIL_USERNAME'); //你的邮箱名
    $mail->Password = C('MAIL_PASSWORD') ; //邮箱密码
    $mail->From = C('MAIL_FROM'); //发件人地址（也就是你的邮箱地址）
    $mail->FromName = C('MAIL_FROMNAME'); //发件人姓名
    $mail->AddAddress($to,"Hello");
    $mail->WordWrap = 50; //设置每行字符长度
    $mail->IsHTML(C('MAIL_ISHTML')); // 是否HTML格式邮件
    $mail->CharSet=C('MAIL_CHARSET'); //设置邮件编码
    $mail->Subject = $subject; //邮件主题
    $mail->Body = $body;
    $mail->AltBody = "Artbean"; //邮件正文不支持HTML的备用显示
    return($mail->Send());
}
/**
 * 邮箱验证-简单对称加密算法之加密
 * @param String $string 需要加密的字串
 * @param String $skey 加密EKY
 * @return String
 */
function emailEncode($string = '', $skey) {
    $strArr = str_split(base64_encode($string));
    $strCount = count($strArr);
    foreach (str_split($skey) as $key => $value)
        $key < $strCount && $strArr[$key].=$value;
    return str_replace(array('=', '+', '/'), array('O0O0O', 'o000o', 'oo00o'), join('', $strArr));
}
/**
 * 邮箱验证-简单对称加密算法之解密
 * @param String $string 需要解密的字串
 * @param String $skey 解密KEY
 * @return String
 */
function emailDecode($string = '', $skey) {
    $strArr = str_split(str_replace(array('O0O0O', 'o000o', 'oo00o'), array('=', '+', '/'), $string), 2);
    $strCount = count($strArr);
    foreach (str_split($skey) as $key => $value)
        $key <= $strCount  && isset($strArr[$key]) && $strArr[$key][1] === $value && $strArr[$key] = $strArr[$key][0];
    return base64_decode(join('', $strArr));
}
/**
 * 登录成功后记录token 
 */
function Authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
    $ckey_length = 4;
    $key = md5($key ? $key : UC_KEY);
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
    $cryptkey = $keya.md5($keya.$keyc);
    $key_length = strlen($cryptkey);
    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
    $string_length = strlen($string);
    $result = '';
    $box = range(0, 255);
    $rndkey = array();
    for($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }
    for($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }
    for($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if($operation == 'DECODE') {
        if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        return $keyc.str_replace('=', '', base64_encode($result));
    }
}

/**
 * 发送post请求方法请求UPS
 * @param string $url
 * @param array $post_data
 */
function request_post($url = '', $post_data = array()) {
    if (empty($url) || empty($post_data)) {
        return false;
    }
    $o = "";
    foreach ( $post_data as $k => $v ) 
    { 
        $o.= "$k=" . urlencode( $v ). "&" ;
    }
    $post_data = substr($o,0,-1);
    $postUrl = $url;
    $curlPost = $post_data;
    $ch = curl_init();//初始化curl
    curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
    curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
    $data = curl_exec($ch);//运行curl
    curl_close($ch);
    return $data;
}

/**
 * 发送post请求方法提交图片
 */
function request_img_post($url = '', $imgData) {



    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER,array('Content-Type: multipart/form-data'));
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // stop verifying certificate
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($curl, CURLOPT_POST, true); // enable posting
    curl_setopt($curl, CURLOPT_POSTFIELDS, $imgData); // post images 
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); // if any redirection after upload
    $r = curl_exec($curl);  

    curl_close($curl);
    return $r;

//     $url = "http://50.97.60.83:8089/imageface/image";
//     //$post_data = array('photo1' => "@/home/wwwroot/default/artbean/api/api1/Uploads/2016-07-12/5784cd2176b7e.jpg");
//     $cfile = curl_file_create('/home/wwwroot/default/artbean/api/api1/Uploads/2016-07-22/57919f6768ff6.jpg'); // try adding 


//     $imgdata = array('photo1' => $cfile);
// print_r($imgdata);exit();
//     $curl = curl_init();
//     curl_setopt($curl, CURLOPT_URL, $url);
//     // curl_setopt($curl, CURLOPT_USERAGENT,'Opera/9.80 (Windows NT 6.2; Win64; x64) Presto/2.12.388 Version/12.15');
//     curl_setopt($curl, CURLOPT_HTTPHEADER,array('Content-Type: multipart/form-data'));
//     curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // stop verifying certificate
//     curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($curl, CURLOPT_POST, true); // enable posting
//     curl_setopt($curl, CURLOPT_POSTFIELDS, $imgdata); // post images 
//     curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); // if any redirection after upload
//     $r = curl_exec($curl);
//     if ( $r == false ) {
//         echo 111;
//         print_r(curl_error($curl));
//     } else {
//         echo 22;
//         print_r($r);
//     }
//     exit();
//     curl_close($curl);
//     return $data;









//     $target=$url;

//     //$cfile = curl_file_create('G:\wamp\www\artbean2\code\trunk\webService\Uploads\2016-07-21\57907ff503d3f.jpg"','image/jpeg','photo1'); // try adding 
 
//     $imgdata = array('photo1' => "@G:\wamp\www\artbean2\code\\trunk\webService\Uploads\\2016-07-21\\57907ff503d3f.jpg");

//     //$imgdata = array('photo1' => "@/home/wwwroot/default/artbean/api/api1/Uploads/2016-07-12/5784cd2176b7e.jpg");

//     $curl = curl_init();
//         curl_setopt($curl, CURLOPT_URL, $target);
// //        curl_setopt($curl, CURLOPT_USERAGENT,'Opera/9.80 (Windows NT 6.2; Win64; x64) Presto/2.12.388 Version/12.15');
// //        curl_setopt($curl, CURLOPT_HTTPHEADER,array('User-Agent: Opera/9.80 (Windows NT 6.2; Win64; x64) Presto/2.12.388 Version/12.15','Referer: http://someaddress.tld','Content-Type: multipart/form-data'));
//         curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // stop verifying certificate
//         curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
//         curl_setopt($curl, CURLOPT_POST, true); // enable posting
//         curl_setopt($curl, CURLOPT_POSTFIELDS, $imgdata); // post images 
// //        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); // if any redirection after upload
//     $r = curl_exec($curl);

//     curl_close($curl);

//     print_r($r);exit();
//     return $reply;
}


//数据过滤函数 批量
function add_slashes($string) {      
     if (is_array($string)) {      
         foreach ($string as $key => $value) {      
             $string[$key] = add_slashes($value);      
         }      
     } else {      
         $string = addslashes($string);      
     }  
     return $string;      
} 
//替换HTML尾标签


//判断手机号码格式 目前是规则 等于10位数字
function ckMobile($mobile_num) {
    return preg_match("/^\d{10}$/", $mobile_num );
}
/**
 * 缓存方式 
 * file  memcache
 */
function abCache( $type = 1,$prefix = "", $expire_time = 1800) {
    $cache_type = !empty($type) ? $type : C('AB_CACHE_TYPE');
    if ( 1 == $cache_type ) {
        S( array('type'=>'file','prefix'=> empty($prefix) ? C('AB_CACHE_PREFIX') : $prefix,'expire'=>$expire_time) );
    }else if ( 2 == $cache_type ) {
        S( array('type'=>'memcache','host'=>C('AB_MEMCACHE_HOST'),'port'=>C('AB_MEMCACHE_PORT'),'prefix'=>empty($prefix) ? C('AB_CACHE_PREFIX') : $prefix,'expire'=>$expire_time));
    }
    
}

/**
 * 生成随机数（大小写字母和数字）
 * $len 生成的长度
 * $i  防止 随机种子相同乘数
 * $chars 原始字符
 */
function getRandomString($len, $i, $chars=null)
{
    if (is_null($chars)){
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    }
    mt_srand(10000000*(double)microtime()*$i);
    for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++){
        $str .= $chars[mt_rand(0, $lc)];  
    }
    return $str;
}

function ckEmail($email) {
    $pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
    return preg_match( $pattern, $email );
}

function ckUsername($username) {
    $pattern = "/^[a-zA-Z0-9]{1,30}$/i";
    return preg_match( $pattern, $username );
}

function ckWebsite($url) {
    $pattern = '/\b(([\w-]+:\/\/?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|\/)))/';
    return preg_match( $pattern, $url );
}

function request_get($url){  
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  FALSE);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_REFERER, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function getInt($fenshu) {

    $int1 = explode(" ",$fenshu);

    if ( count($int1)  == 1 ) {
        $int2 = explode("/",$int1[0]);

        if ( count($int2) == 1 ) {
            $int =  $int2[0];
        } else if ( count($int2) == 2 ) {
            $int = $int2[0] / $int2[1];

        }
    } else if ( count($int1) == 2 ) {
        $int4 = $int1[0];
        $int5 = explode("/", $int1[1]);
        $int6 = $int5[0] / (int)$int5[1];
        $int7 = sprintf("%.2f",$int6);
        $int = $int4 + $int7;
        
    }
    return $int;
}

function getGoodsShipSize($longth, $width) {
    $min = $width;
    $max = $longth;
    if ( $longth < $width) {
        $min = $longth;
        $max = $width;
    }
    if ( $max < 11.8 ) {
        $box = 1;
        $longth = '21.7';
        $width = "21.7";
    } else if ($max >= 11.8 && $max < 17.7) {
        $box = 2;
        $longth = '27.6';
        $width = "27.6";
    } else if ($max >= 17.7 && $max < 23.6) {
        $box = 3;
        $longth = '33.5';
        $width = "33.5";
    } else if ($max >= 23.6 && $max < 29.5) {
        $box = 4;
        $longth = '39.4';
        $width = "39.4";
    } else if ($max >= 29.5 && $max < 35.4) {
        $box = 5;
        $longth = '45.3';
        $width = "45.3";
    } else if ($max >= 35.4 && $max < 39.4) {
        $box = 6;
        $longth = '49.2';
        $width = "49.2";
    } else if ($max >= 39.4 && $max < 45.3) {
        $box = 7;
        $longth = '55.1';
        $width = "55.1";
    } else if ( $max >= 45.3 && $max < 51.2  && ($max/$min) <= 1.5  ) {
        $box = 8;
        $longth = '61.0';
        $width = "61.0";
    } else if (45.3 <= $max && $max < 51.2 &&  ($max/$min) > 1.5) {
        $box = 9;
        $longth = '61.0';
        $width = "41.3";
    } else if (51.2 <= $max && $max < 59) {
        $box = 10;
        $longth = '68.9';
        $width = "49.2";
    }
    return array("goods_longth"=>$longth, "goods_width"=>$width,"box"=>$box);
} 

//获取相框尺寸
function getGoodsShipSizeWithRahmen($longth, $width) {
    $min = $width;
    $max = $longth;
    if ( $longth < $width) {
        $min = $longth;
        $max = $width;
    }
    if ( $max < 18.5 ) {
        $box = 1;
        $longth = '21.7';
        $width = "21.7";
    } else if ($max >= 18.5 && $max < 24.4) {
        $box = 2;
        $longth = '27.6';
        $width = "27.6";
    } else if ($max >= 24.4 && $max < 30.3) {
        $box = 3;
        $longth = '33.5';
        $width = "33.5";
    } else if ($max >= 30.3 && $max < 36.2) {
        $box = 4;
        $longth = '39.4';
        $width = "39.4";
    } else if ($max >= 36.2 && $max < 42.1) {
        $box = 5;
        $longth = '45.3';
        $width = "45.3";
    } else if ($max >= 42.1 && $max < 46.1) {
        $box = 6;
        $longth = '49.2';
        $width = "49.2";
    } else if ($max >= 46.1 && $max < 52.0) {
        $box = 7;
        $longth = '55.1';
        $width = "55.1";
    } else if ( $max >= 52.0 && $max < 57.9   && ($max/$min) <= 1.5  ) {
        $box = 8;
        $longth = '61.0';
        $width = "61.0";
    } else if (52.0 <= $max && $max < 57.9  &&  ($max/$min) > 1.5) {
        $box = 9;
        $longth = '61.0';
        $width = "41.3";
    } else if (57.9 <= $max && $max < 65.7) {
        $box = 10;
        $longth = '68.9';
        $width = "49.2";
    }
    return array("goods_longth"=>$longth, "goods_width"=>$width,"box"=>$box);
} 



//获取玻璃
function getGoodsShipBags($longth, $width) {
    $min = $width;
    $max = $longth;
    if ( $longth < $width) {
        $min = $longth;
        $max = $width;
    }
    if ( $max < 24 ) {
        $bag = 24;
    } else if ($max >= 24 && $max < 34 ) {
        $bag = 34;
    } else if ($max >= 34 && $max < 50 ) {
        $bag = 50;
    } else if ($max >= 50 && $max < 64) {
        $bag = 64;
    }
    return array( "bag"=>$bag);
}
 
//删除uploads下图片
function deldir($dir) {
    //先删除目录下的文件：
    $dh = opendir($dir);
    while ( $file = readdir($dh) ) {
        if ( $file != "." && $file != ".." ) {
            $fullpath = $dir."/".$file;
            if ( !is_dir( $fullpath ) )  {
                unlink( $fullpath );
            } else {
                deldir( $fullpath );
            }
        }
    } 
    closedir($dh); 
    //删除当前文件夹： 
    if( rmdir($dir) ) { 
        return true; 
    } else { 
        return false; 
    }

}

function _get_client_ip($type = 0) {
    $type       =  $type ? 1 : 0;
    static $ip  =   NULL;
    if ($ip !== NULL) return $ip[$type];
    if($_SERVER['HTTP_X_REAL_IP']){//nginx 代理模式下，获取客户端真实IP
        $ip=$_SERVER['HTTP_X_REAL_IP'];     
    }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {//客户端的ip
        $ip     =   $_SERVER['HTTP_CLIENT_IP'];
    }elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {//浏览当前页面的用户计算机的网关
        $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $pos    =   array_search('unknown',$arr);
        if(false !== $pos) unset($arr[$pos]);
        $ip     =   trim($arr[0]);
    }elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip     =   $_SERVER['REMOTE_ADDR'];//浏览当前页面的用户计算机的ip地址
    }else{
        $ip=$_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u",ip2long($ip));
    $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
 }

 
