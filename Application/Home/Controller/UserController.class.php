<?php
namespace Home\Controller;
use Home\Controller\MyController;
use Think\Verify;
class UserController extends MyController {
    public $expire_time = 1500;   //修改密码的 时间有效期30分钟
    public $session_time = 604800;  //登录有效时间 为7天

    public function _empty() {
        $this->errRender();
    }
    /**
     *  注册邮箱验证
     */
    // public function emailVerify() {
    //     $verify = I("get.verify");
    //     if ( !$verify ) {
    //         echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />".L('出现异常错误');exit();
    //     }
    //     $token = emailDecode($verify, "artbean");
    //     $arr = explode("+", $token);
        
    //     if ( !ckEmail($arr[0] )) {
    //         echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />".L('出现异常错误');exit();
    //     }
    //     if ( $arr[2]  <  date( "Y-m-d H:i:s", strtotime( "-2day", time() ) ) ) {
    //         echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />".L('该链接已过期，请重新发送邮箱验证');exit();
    //     }
    //     $user = D("BaseUser");
    //     $userinfo = $user->field("id, email, username ")->where("email = '".$arr[0]."' and user_status = 0")->select();
    //     if ( count( $userinfo ) > 0 && $userinfo[0]['username'] == $arr[1]) {
    //         $data['update_time'] = date("Y-m-d H:i:s", time());
    //         $data['user_status'] = 1;  //账户修改为正常状态

    //         $res = $user->where("email = '".$arr[0]."' and username = '".$userinfo[0]['username']."'")->filter('strip_tags')->save($data);
    //         if ( $res ) {
    //             echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />".L("激活成功");exit();
    //         }
    //     } else {
    //         echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />".L('请重新发送邮箱验证');exit();
    //     }

    // }

    /**
     * 登陆接口
     */
    public function login() {
        // try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            if ( !isset( $getPost['email'] ) || empty( $getPost['email'] ) || !isset( $getPost['password'] ) || empty( $getPost['password'] )  ) {
                $this->_render(self::Err_No_Data);
            }
            $user = D("BaseUser");
            //判断邮箱是否已经被注册

            $userinfo = $user->_getUserInfo( $getPost['email'] );
            //$userinfo = $user->where("email = '".$getPost['email']."'  and userpsd = '".$getPost['password']."'")->find();
            $userinfo = $userinfo[0];

            if ( count( $userinfo ) == 0) {
                $this->_render(self::Err_No_User);
            }
            if ( $userinfo['userpsd'] != $getPost['password'] ) {
                $this->_render(self::Err_No_User);
            }
            //没有验证邮箱
            if ( 0 == $userinfo['user_status'] ) {
                $this->_render(self::Err_User_No_Verify);
            }
            //用户被锁定
            if ( 0 != $userinfo['is_able']) {
                $this->_render(self::Err_User_Locked);
            }
            $session = D("BaseSession");
            $user_session = $session->field('id, lastactive')->where("   email = '%s'  ", array($getPost['email']))->find();
            if ( !empty( $user_session ) ) {
                $this->deleteSessionToken($getPost['email'], $session);
            }
            $time        = time();
            $str        = '{"id":"'.$userinfo['id'].'","email":"'.$userinfo['email'].'","logintime":"'.$time.'"}';
            $token      = urlencode(Authcode($str,'ENCODE',$time));
            $data['email'] = $userinfo['email'];
            $data['token'] = $token;
            $data['status'] = 1;
            $data['dateline'] = $time;
            $data['lastactive'] = $time;
            $res = $session->data($data)->filter('strip_tags')->add();
            $userinfo['token'] = $token;
            unset($userinfo['userpsd']);
            unset($userinfo['user_status']);
            unset($userinfo['is_able']);
            if ( $res ) {
                $this->_render(self::Err_Suc, $userinfo);
            }
            $this->_render(self::Err_Failure);
        // } catch (\Think\Exception $e) {
        //     $this->_render(self::Err_Failure);
        // }
    
        // $res['token'] = $token;
        // $res['email'] = $userinfo['email'];
        // $res['user_id'] = $userinfo['id'];
        // $res['username'] = $userinfo['username'];
        // $shopCart = D("ShopEnshrine");
        // $shopCartList = $shopCart->getShopCartList($userinfo['id']);
        // $res['countshopcart'] = count($shopCartList);
        // $favorite = D("UserFavorite");
        // $favoriteList = $favorite->getFavoriteList($userinfo['id']);
        // $res['countfavorite'] = count($favoriteList);
        // $res['mobile'] = $userinfo['mobile'];
        
    }

    public function logout() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            if ( !isset( $getPost['email'] ) || empty( $getPost['email'] ) || !isset( $getPost['token'] ) || empty( $getPost['token'] )  ) {
                $this->_render(self::Err_No_Data);
            }
            $this->ckSessionToken($getPost['email'], $getPost['token']);
            $this->deleteSessionToken($getPost['email']);
            $this->_render(self::Err_Suc, L("成功退出") );
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    
    }


//http://192.168.1.101:8080/art/abBaseUser/addmember?username=wuzhi&password=11111&email=1229662530@qq.com&flag=1
//{"msg":"Registered success, please go to the mailbox activation！","state":0,"success":true}
    public function register() {    
        if ( !IS_POST ) {
            $this->_render(self::Err_Not_Legal);
        }
        $post_data = file_get_contents('php://input','r');
        if ( !$post_data ) {
            $this->_render(self::Err_No_Data);
        }
        $getPost = json_decode($post_data, 1);
        if ( !isset( $getPost['verify'] ) || empty( $getPost['verify'] ) || !isset( $getPost['username'] ) || empty( $getPost['username'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] ) || !isset( $getPost['password'] ) || empty( $getPost['password'] )  ) {
            $this->_render(self::Err_No_Data);
        }
        session_id(session_id());
        $captcha = new  Verify();
        if ( !$captcha->check( $getPost["verify"] ) ) {
            $this->_render(self::Err_Verify_False);
        }
        if (   !ckUsername($getPost['username'])  ) {
            $this->_render(self::Err_Username_Wrong);
        }
        if ( !ckEmail($getPost['email'] ) ) {
            $this->_render(self::Err_Email_Wrong);
        }



        // $reg_url = C("REGISTER");
        // $res = file_get_contents($reg_url."?username=".$getPost['username']."&userpsd=".$getPost['password']."&email=".$getPost['email']."&flag=1");
        // $res = json_decode($res, true);
        
        // if ( $res['status'] == 0 ) {
        //     $this->_render(self::Err_Suc,L('成功'), 'Email has been sent, please activate quickly!');
        // }
        // $this->_render(self::Err_Failure,'',$res['msg']);


        $user = D("BaseUser");
      
        try{
            $userinfo = $user->where("type in (0) and email = '".$getPost['email']."'")->find();
            $reg_time = date("Y-m-d H:i:s", time());
            $data['username']       = $getPost['username'];
            $data['userpsd']        = $getPost['password'];
     
            if ( $userinfo ) {
                $this->_render(self::Err_Email_Exist);
            }
            $data['email']      = $getPost['email'];
            $data['create_time']    = $reg_time;
            $data['user_status']    = 1;     //没验证邮箱 禁用状态
            $data['is_able'] = 0;
            $data['delete_status']    = 0;
            if ($user->data($data)->filter('strip_tags')->add()) {
                $this->_render(self::Err_Suc,L('成功'), "Registration success!");
            }
            $this->_render(self::Err_Failure);
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
        // //数据库中没有此email信息
        // if ( count($userinfo) == 0) {
        //     $data['email']      = $getPost['email'];
        //     $data['create_time']    = $reg_time;
        //     $data['user_status']    = 0;     //没验证邮箱 禁用状态
        //     $data['is_able'] = 0;
        //     $data['delete_status']    = 0;
        //     $result = $user->data($data)->filter('strip_tags')->add();
        // } else if ( count($userinfo)  > 0 && $userinfo['user_status'] == 0 && $userinfo['delete_status'] == 0  ) {
        //     //有记录 但是禁用状态 可以继续激活或者重新认证
        //     if (  ( empty( $userinfo['update_time'] ) &&  $userinfo['create_time'] < date( "Y-m-d H:i:s", strtotime( "-2day", time() ) ) ) || ( !empty( $userinfo['update_time'] ) &&  $userinfo['update_time'] < date( "Y-m-d H:i:s", strtotime( "-2day", time() ) ) )  ) {
        //         $data['update_time'] = $reg_time;
        //         if ( $data['username'] == $userinfo['username'] ) {
        //             unset($data['username']);
        //         }
        //         if ( $data['userpsd'] == $userinfo['userpsd'] ) {
        //             unset($data['userpsd']);
        //         }
        //         $result = $user->where('email = "'.$getPost['email'].'"')->filter('strip_tags')->save($data);
        //     } else {
        //         $this->_render(self::Err_Email_Verifing);
        //     }
        // } else  {
        //     $this->_render(self::Err_Email_Exist);
        // }
        // if ( $result ) {
        //     $token = emailEncode( $getPost['email'].'+'.$getPost['username'].'+'.$reg_time, "artbean" );

        //     $url =  "https://mandrillapp.com/api/1.0/templates/info.json?key=rawgdIlhVJf5ph4hGg5tag&name=User%20Sign%20Up%20Confirmation%20Template" ;
        //     $res = request_get($url);
        //     $res = json_decode($res, true);
        //     $subject = $res['name'];
        //     $body = $res['code'];
        //     $body = str_replace("#activation_link_here", "http://".$_SERVER['HTTP_HOST'].__ROOT__."/home/user/emailVerify?verify=".$token, $body);
        //     SendMail( $getPost['email'], $subject, $body );
        //     $da = "账号注册成功，请前往您的邮箱验证";
        //     $this->_render(self::Err_Suc,L($da),$da);
 
        // }
        // $this->_render(self::Err_Failure);
    }

    //验证token 每打开一次app去验证当前token是否已过期 主要提示是否在其他应用上登录
    public function ckToken() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            if (!isset( $getPost['token'] ) || empty( $getPost['token'] )  || !isset( $getPost['email'] ) || empty( $getPost['email'] )  )   {
                $this->_render(self::Err_No_Data);
            }
            $session =   D("BaseSession");
            $user_session = $session->field('id, token, lastactive')->where("  email = '%s'  ",  $getPost['email'] )->find();
            if ( empty( $user_session ) ) {
                $this->_render(self::Err_Active_Unable);
            }
            if ( $user_session['lastactive'] + C('SESSIONTIME') < time() ) {
                $this->_render(self::Err_Login_Expire);
            }
            if ( $getPost['token'] != $user_session['token'] ) {
                $this->_render(self::Err_Login_Invalid);
            }
            $this->_render(self::Err_Suc, null);
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    
    }

    //更新userinfo 主要维护 侧滑内的个人信息
    public function updateUserInfo() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            if (!isset( $getPost['token'] ) || empty( $getPost['token'] ) ||!isset( $getPost['user_id'] ) || empty( $getPost['user_id'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] )  )   {
                $this->_render(self::Err_No_Data);
            }
            $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id']);
            $user = D("BaseUser");
            $userinfo = $user->updateUserInfo( $getPost['email'], $getPost['user_id'] );
            $this->_render(self::Err_Suc, $userinfo[0]);
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    
    }

    /**
     * 个人资料设置
     */
    // public function userSetting() {
    //     try{
    //         if ( !IS_POST ) {
    //             $this->_render(self::Err_Not_Legal);
    //         }
    //         $post_data = file_get_contents('php://input','r');
    //         if ( !$post_data ) {
    //             $this->_render(self::Err_No_Data);
    //         }
    //         $getPost = json_decode($post_data, 1);

    //         if (!isset( $getPost['token'] ) || empty( $getPost['token'] ) ||!isset( $getPost['user_id'] ) || empty( $getPost['user_id'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] ) || !isset( $getPost['re_username'] ) || empty( $getPost['re_username']) || !isset( $getPost['re_mobile'] ) || !isset( $getPost['re_ext'] ) ) {
    //             $this->_render(self::Err_No_Data);
    //         }

    //         $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id']);
    //         if ( !empty($getPost['re_mobile']) && !ckMobile( $getPost['re_mobile'] ) ) {
    //             $this->_render(self::Err_Phone_Wrong);
    //         }
    //         if ( !empty($getPost['re_ext']) &&  !preg_match("/^\d{1,4}$/", $getPost['re_ext'] ) ) {
    //             $this->_render(self::Err_Failure, "", "ext格式不正确");
    //         }
    //         $data['mobile'] = $getPost['re_mobile'];
    //         $data['username'] = $getPost['re_username'];
    //         $data['ext'] = $getPost['re_ext'];
    //         $user = D("BaseUser");
    //         $res = $user->where('email = "%s" and id = %d', array($getPost['email'], $getPost['user_id']))->filter('strip_tags')->save($data);

    //         if ( $res !== false ) {
    //             $userinfo = $user->getUserInfo('email = "%s" and id = %d', array($getPost['email'], $getPost['user_id']));
                
    //             $this->_render(self::Err_Suc, $userinfo[0]);
    //         }
    //         $this->_render(self::Err_Failure);
    //     } catch (\Think\Exception $e) {
    //         $this->_render(self::Err_Failure);
    //     }
    
    // }
    /**
     * 个人资料设置用户名
     */
    public function userSettingUsername() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);

            if (!isset( $getPost['token'] ) || empty( $getPost['token'] ) ||!isset( $getPost['user_id'] ) || empty( $getPost['user_id'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] ) || !isset( $getPost['re_username'] ) || empty( $getPost['re_username'])  ) {
                $this->_render(self::Err_No_Data);
            }

            $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id']);

            if ( !ckUsername($getPost['re_username'])) {
                $this->_render(self::Err_Username_Wrong);
            }
            $data['username'] = $getPost['re_username'];

            $user = D("BaseUser");
            $res = $user->where('email = "%s" and id = %d', array($getPost['email'], $getPost['user_id']))->filter('strip_tags')->save($data);

            if ( $res !== false ) {
                $userinfo = $user->getUserInfo('email = "%s" and id = %d', array($getPost['email'], $getPost['user_id']));
                
                $this->_render(self::Err_Suc, $userinfo[0]);
            }
            $this->_render(self::Err_Failure);
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    
    }

    /**
     * 个人资料设置电话号码
     */
    public function userSettingPhone() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            if (!isset( $getPost['token'] ) || empty( $getPost['token'] ) ||!isset( $getPost['user_id'] ) || empty( $getPost['user_id'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] )  || !isset( $getPost['re_mobile'] ) || empty($getPost['re_mobile']) || !isset( $getPost['re_ext'] )   ) {
                $this->_render(self::Err_No_Data);
            }

            $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id']);
            if ( !empty($getPost['re_mobile']) && !ckMobile( $getPost['re_mobile'] ) ) {
                $this->_render(self::Err_Phone_Wrong);
            }
            if ( !empty($getPost['re_ext']) &&  !preg_match("/^\d{1,4}$/", $getPost['re_ext'] ) ) {
                $this->_render(self::Err_Failure, "", L("ext格式不正确") );
            }
            $data['mobile'] = $getPost['re_mobile'];
            $data['ext'] = $getPost['re_ext'];
            $user = D("BaseUser");
            $res = $user->where('email = "%s" and id = %d', array($getPost['email'], $getPost['user_id']))->filter('strip_tags')->save($data);

            if ( $res !== false ) {
                $userinfo = $user->getUserInfo('email = "%s" and id = %d', array($getPost['email'], $getPost['user_id']));
                $url =  "https://mandrillapp.com/api/1.0/templates/info.json?key=rawgdIlhVJf5ph4hGg5tag&name=Change%20Phone%20Number%20-%20Final" ;
                $res = request_get($url);
                $res = json_decode($res, true);
                $subject = $res['name'];
                $body = $res['code'];
                $body = str_replace("[Customer Name]", $userinfo[0]['username'], $body);
                $body = str_replace("[New Phone Number]", $getPost['re_mobile'], $body);
                SendMail( $getPost['email'], $subject, $body );
                $this->_render(self::Err_Suc, $userinfo[0]);
            }

            $this->_render(self::Err_Failure);
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    
    }
    public function changeUserPsd() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            if (!isset( $getPost['token'] ) || empty( $getPost['token'] ) ||!isset( $getPost['user_id'] ) || empty( $getPost['user_id'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] ) || !isset( $getPost['password'] ) || empty( $getPost['password']) || !isset( $getPost['re_password'] ) || empty( $getPost['re_password']) ) {
                $this->_render(self::Err_No_Data);
            }
            $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id']);
            $user = D("BaseUser");
            $res = $user->field('id,username')->where(" type in (0) and  email = '%s' and id = %d and userpsd = '%s'", array($getPost['email'],$getPost['user_id'],$getPost['password']))->find();
            if ( empty($res) ) {
                $this->_render(self::Err_Psd_Wrong);
            }
            if ($getPost['password'] == $getPost['re_password']) {
                $this->_render(self::Err_Psd_Same);
            }
            $data['userpsd'] = $getPost['re_password'];
            $res1 = $user->where(" email = '%s' ", array($getPost['email']))->filter('strip_tags')->save($data);
            if ( $res1 !== false) {
                
                $userinfo = $user->getUserInfo('email = "%s" and id = %d', array($getPost['email'], $getPost['user_id']));
                $url =  "https://mandrillapp.com/api/1.0/templates/info.json?key=rawgdIlhVJf5ph4hGg5tag&name=Change%20Password%20-%20Final" ;
                $res3 = request_get($url);
                $res2 = json_decode($res3, true);
                $subject = $res2['name'];
                $body = $res2['code'];
                $body = str_replace("#Customer Name", $userinfo[0]['username'], $body);
                SendMail( $getPost['email'], $subject, $body );
                $this->_render(self::Err_Suc, L("成功") );
            }
            $this->_render(self::Err_Failure);
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    
    }


    //忘记密码
    public function checkEmail() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            if ( !isset( $getPost['email'] ) || empty( $getPost['email'] ) || !isset( $getPost['verify'] ) || empty( $getPost['verify'] )   ) {
                $this->_render(self::Err_No_Data);
            }

            //判断验证码
            /**
                留着处理 wamp 不行 
            */
            // echo session_id();
            // echo "<hr>";
            // print_r($_SERVER);exit();
            // $arr =  explode("=", $_SERVER['HTTP_COOKIE'] );
            session_id(session_id());
            $captcha = new  Verify();
            
            if ( !$captcha->check( $getPost["verify"] ) ) {
                $this->_render(self::Err_Verify_False);
            }
            $user = D("BaseUser");
            $userinfo = $user->where("type  in (0) and email = '".$getPost['email']."'")->find();
            if ( empty($userinfo) ) {
                $this->_render(self::Err_Failure,'',"The Email is not registered");
            }
            $forget_url = C("FORGETPASSWORD");
            $res = file_get_contents($forget_url."?email=".$getPost['email']."&flag=1");
            $res = json_decode($res, true);
            if ( $res['status'] == 0 ) {
                $this->_render(self::Err_Suc,L('成功'), L("去邮箱修改密码") );
            }
            $this->_render(self::Err_Failure,'',$res['mag']);
            // //判断填写的邮箱是否已注册或者正确
            // $user = D("BaseUser");
            // $eVerify = D("BaseEmailVerify");
            // $where = " email = '".$getPost['email']."' ";
            // $res = $user->field('id, email')->where($where)->find();
            // if ( count( $res ) < 1 ) {
            //     $this->_render( self::Err_No_User );
            // }
            // $e_v_res = $eVerify->field("id, email, verify_expire_time")->where($where)->find();
            // if ( $e_v_res && strtotime( $e_v_res['verify_expire_time'] )  >  time()  ) {
            //     $this->_render(self::Err_Token_Expire);
            // }
            // //找回密码发送邮箱并记录token验证码的值以及过期时间
            // $token =  substr(uniqid(rand()), -6);
            // $data['email'] = $getPost['email'];
            // $data['verify_code'] = $token;
            // $data['verify_expire_time'] = date( "Y-m-d H:i:s", time()+C('CKPSDTIME') );
            // $data['verify_status'] = 0;
            // if ( $e_v_res ) {
            //     unset($data['email']);
            //     unset($data['verify_status']);
            //     $eVerify->where(" email = '".$getPost['email']."'")->filter('strip_tags')->save($data);
            // } else {
            //     $eVerify->data($data)->filter('strip_tags')->add();
            // }
            // $subject = "Artbean Retrieve password authentication code";
            // $body = "Verification code is<br/><b>".$token."</b><br>The authentication code is valid for 30 minutes<br/>If the request you I did not send the password back, please ignore this E-mail.<br/><p style='text-align:right'> Artbean </p>"; //邮件内容
            // if( SendMail( $getPost['email'], $subject, $body ) ) {
            //     $data = $res;
            //     $this->_render(self::Err_Suc, $data);
            // }
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    
    }

    /**
     * 更换注册邮箱
     */

    public function changeEmail() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            if ( !isset( $getPost['email'] ) || empty( $getPost['email'] ) || !isset( $getPost['token'] ) || empty( $getPost['token'] ) || !isset( $getPost['user_id'] ) || empty( $getPost['user_id'] ) || !isset( $getPost['re_email'] ) || empty( $getPost['re_email'] ) ) {
                $this->_render(self::Err_No_Data);
            }
            $user = D("BaseUser");
            $user->startTrans();
            $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id']);

            if ( !ckEmail($getPost['re_email'] ) ) {
                $this->_render(self::Err_Email_Wrong);
            }
            
            $userinfo = $user->field("id,username")->where('email = "%s"', $getPost['re_email'])->find();
            if ( !empty($userinfo) ) {
                $this->_render(self::Err_Email_Exist);
            }
            $userData['email'] = $getPost['re_email'] ;
            $user->startTrans();
            $res1 = $user->where(" id = %d", $getPost['user_id'])->filter('strip_tags')->save($userData);
            $sessionModel = D('BaseSession');
            $res2 = $sessionModel->where(" email = '%s'", $getPost['email'])->filter('strip_tags')->save($userData);
            if ( $res1 && $res2 ) {
                $user->commit();
                $this->_render(self::Err_Suc, null);
            } else {
                $user->rollback();
                $this->_render(self::Err_Failure);
            }

            // $res = file_get_contents(AB_FRONTED."account/sendEmailphp?username=".$userinfo['username']."&uid=".$getPost['user_id']."&email=".$getPost['re_email']);
            // $res = json_decode($res, true);
            // if ( $res['state'] === 0 ) {
            //     $this->_render(self::Err_Suc, null);
            // }
            $this->_render(self::Err_Failure);
        } catch (\Think\Exception $e) {
            $user->rollback();
            $this->_render(self::Err_Failure);
        }
    
    }

    // public function checkToken() {
    //     try{
    //         if ( !IS_POST ) {
    //             $this->_render(self::Err_Not_Legal);
    //         }
    //         $post_data = file_get_contents('php://input','r');
    //         if ( !$post_data ) {
    //             $this->_render(self::Err_No_Data);
    //         }
    //         $getPost = json_decode($post_data, 1);
    //         if ( !isset( $getPost['token'] ) || empty( $getPost['token'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] ) ) {
    //             $this->_render(self::Err_No_Data);
    //         }
    //         $verify = D("BaseEmailVerify");
    //         $res = $verify->field("verify_expire_time, email")->where(" email = '".$getPost["email"]."' and verify_code = '".$getPost["token"]."'")->find();
    //         if ( !$res ) {
    //             $this->_render(self::Err_Verify_False);
    //         }
    //         if (   strtotime( $res['verify_expire_time'] )  <  time()  ) {
    //             $this->_render(self::Err_Token_Expire2);
    //         }
    //         $data['ckpsd_expire_time'] =  date( "Y-m-d H:i:s", time() + C('CKPSDTIME') );
    //         $data['verify_status'] =  1;
    //         $verify->where("email = '".$getPost["email"]."'")->filter('strip_tags')->save($data);
    //         $this->_render(self::Err_Suc, $res);
    //     } catch (\Think\Exception $e) {
    //         $this->_render(self::Err_Failure);
    //     }
    
    // }

    // public function changePsd() {
    //     try{
    //         if ( !IS_POST ) {
    //             $this->_render(self::Err_Not_Legal);
    //         }
    //         $post_data = file_get_contents('php://input','r');
    //         if ( !$post_data ) {
    //             $this->_render(self::Err_No_Data);
    //         }
    //         $getPost = json_decode($post_data, 1);
    //         if ( !isset( $getPost['password'] ) || empty( $getPost['password'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] ) ) {
    //             $this->_render(self::Err_No_Data);
    //         }
    //         $verify = D("BaseEmailVerify");
    //         $res = $verify->field("ckpsd_expire_time,  email")->where("verify_status = 1 and email = '".$getPost["email"]."'")->find();
    //         if ( !$res ) {
    //             $this->_render(self::Err_Active_Unable);
    //         }
    //         if ( $res['ckpsd_expire_time'] < date("Y-m-d H:i:s", time()) ) {
    //             $this->_render(self::Err_Psd_Expire);
    //         }
    //         $user = D("BaseUser");
    //         $data["userpsd"] = $getPost['password'];
    //         $user->where(" email = '".$getPost["email"]."'")->filter('strip_tags')->save($data);
    //         $verify->where(" email = '".$getPost["email"]."'")->delete();
    //         $this->_render(self::Err_Suc, L("密码修改成功") );
    //     } catch (\Think\Exception $e) {
    //         $this->_render(self::Err_Failure);
    //     }
    
    // }

    // /**
    //  * 个人所有积分
    //  * 积分兑换优惠券规则
    //  */
    // public function userIntegral() {
    //     try{
    //         if ( !IS_POST ) {
    //             $this->_render(self::Err_Not_Legal);
    //         }
    //         $post_data = file_get_contents('php://input','r');
    //         if ( !$post_data ) {
    //             $this->_render(self::Err_No_Data);
    //         }
    //         $getPost = json_decode($post_data, 1);
    //         if ( !isset( $getPost['token'] ) || empty( $getPost['token'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] )   || !isset( $getPost['user_id'] ) || empty( $getPost['user_id'] ) ) {
    //             $this->_render(self::Err_No_Data);
    //         }
    //         $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id']);
    //         //取用户积分
    //         $user = D("BaseUser");
    //         $userintegral = $user->getUserIntegral($getPost['user_id']);
    //         //取优惠券兑换方式
    //         $voucher = D("ShopVoucherRule");
    //         $voucherinfo = $voucher->getVoucherRule( " and is_exchange in (1) ");  //此处可能添加兑换日期时间限制
            
    //         $data['integral'] = $userintegral[0]['integral'];
    //         $data['voucher'] = $voucherinfo;
    //         $this->_render(self::Err_Suc, $data);
    //     } catch (\Think\Exception $e) {
    //         $this->_render(self::Err_Failure);
    //     }
    
    // }

    // /**
    //  * 优惠券兑换
    //  */
    // public function integralToVoucher() {
    //     try{
    //         if ( !IS_POST ) {
    //         $this->_render(self::Err_Not_Legal);
    //     }
    //     $post_data = file_get_contents('php://input','r');
    //     if ( !$post_data ) {
    //         $this->_render(self::Err_No_Data);
    //     }
    //     $getPost = json_decode($post_data, 1);
    //     if ( !isset($getPost['number']) || empty($getPost['number']) || !isset($getPost['rule_id']) || empty($getPost['rule_id']) || !isset( $getPost['token'] ) || empty( $getPost['token'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] )   || !isset( $getPost['user_id'] ) || empty( $getPost['user_id'] ) ) {
    //         $this->_render(self::Err_No_Data);
    //     }
    //     $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id']);
    //     $voucherRule = D('ShopVoucherRule');
    //     //取优惠券兑换规则
    //     $rule_res = $voucherRule->getVoucherRule(" and id = %d", $getPost['rule_id']);
    //     //取用户积分
    //     $user = D("BaseUser");
    //     $userintegral = $user->getUserIntegral($getPost['user_id']);

    //     if ( count( $rule_res ) < 1 ) {
    //         $this->_render(self::Err_Failure);
    //     }
    //     //兑换所需积分
    //     $allIntegral = (int)$getPost['number'] * (int)$rule_res[0]['integral_exchange_rate'];

    //     //兑换积分大于已有积分，提示积分不足
    //     if ( $allIntegral > $userintegral[0]['integral']) {
    //         $this->_render(self::Err_Lack_Integral);
    //     }
    //     $now_time = date('Y-m-d H:i:s', time());
    //     $voucher = D("ShopVoucher");
    //     for ($i=0; $i < (int)$getPost['number'] ; $i++) { 
    //         $data[$i]['ruleid'] = $getPost['rule_id'];
    //         $data[$i]['code'] = getRandomString(16,$i+1); //16随机大小写字母和数字
    //         $data[$i]['is_exchange'] = 1;
    //         $data[$i]['userid'] = $getPost['user_id'];
    //         $data[$i]['status'] = 0;
    //         $data[$i]['par'] = $rule_res[0]['money'];
    //         $data[$i]['integral'] = $rule_res[0]['integral_exchange_rate'];
    //         $data[$i]['delete_status'] = 0;
    //         $data[$i]['create_time'] = $now_time;
    //     }
    //     $voucher->startTrans();
    //     $res1 = $voucher->addAll($data);
    //     $user_data['integral'] = (int)$userintegral[0]['integral'] - $allIntegral;
    //     $res2 = $user->where('id = %d', $getPost['user_id'])->filter('strip_tags')->save($user_data);
    //     $integral_data['source'] = 0;
    //     $integral_data['userid'] = $getPost['user_id'];
    //     $integral_data['delete_status'] = 0;
    //     $integral_data['create_time']= $now_time;
    //     $integral_data['out_integral'] = $allIntegral;
    //     $integral_data['mode'] = 1;
    //     $integral = D("ShopIntegral");
    //     $res3 = $integral->data($integral_data)->filter('strip_tags')->add();
    //     if ( $res1 && $res2 && $res3 ) {
    //         $voucher->commit();
    //         $this->_render(self::Err_Suc, L("成功"));
    //     } else {
    //         $voucher->rollback();
    //     }
    //     $this->_render(self::Err_Failure);
    //     } catch (\Think\Exception $e) {
    //         $voucher->rollback();
    //         $this->_render(self::Err_Failure);
    //     }

    // }
    // /**
    //  * 个人积分记录包括使用和获得
    //  */
    // public function integralRecord() {
    //     try{
    //         if ( !IS_POST ) {
    //             $this->_render(self::Err_Not_Legal);
    //         }
    //         $post_data = file_get_contents('php://input','r');
    //         if ( !$post_data ) {
    //             $this->_render(self::Err_No_Data);
    //         }
    //         $getPost = json_decode($post_data, 1);
    //         if ( !isset( $getPost['token'] ) || empty( $getPost['token'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] )   || !isset( $getPost['user_id'] ) || empty( $getPost['user_id'] ) ) {
    //             $this->_render(self::Err_No_Data);
    //         }
    //         $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id']);
    //         $integral = D('ShopIntegral');
    //         $res = $integral->getIntegral(" and userid = %d", $getPost['user_id']);
    //         $this->_render(self::Err_Suc, $res);
    //     } catch (\Think\Exception $e) {
    //         $this->_render(self::Err_Failure);
    //     }

    // }

    // /**
    //  * 个人优惠券使用记录
    //  */
    // public function voucherRecord() {
    //     try{
    //         if ( !IS_POST ) {
    //             $this->_render(self::Err_Not_Legal);
    //         }
    //         $post_data = file_get_contents('php://input','r');
    //         if ( !$post_data ) {
    //             $this->_render(self::Err_No_Data);
    //         }
    //         $getPost = json_decode($post_data, 1);
    //         if ( !isset( $getPost['token'] ) || empty( $getPost['token'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] )   || !isset( $getPost['user_id'] ) || empty( $getPost['user_id'] ) ) {
    //             $this->_render(self::Err_No_Data);
    //         }
    //         $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id']);
    //         $voucher = D("ShopVoucher");
    //         $data['voucher']['used'] = $voucher->getUsedVoucher($getPost['user_id']);
    //         $data['voucher']['not_used'] = $voucher->getNotUsedVoucher($getPost['user_id']);
    //         $data['voucher']['expired'] = $voucher->getExpiredVoucher($getPost['user_id']);
    //         $this->_render(self::Err_Suc, $data);
    //     } catch (\Think\Exception $e) {
    //         $this->_render(self::Err_Failure);
    //     }
    
    // }
}