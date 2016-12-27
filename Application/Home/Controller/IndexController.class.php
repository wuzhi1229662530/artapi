<?php
namespace Home\Controller;
use Home\Controller\MyController;
use Think\Verify;
use Vendor\PHPMailer\PHPMailerAutoload;
class IndexController extends MyController {

    const INDEX = 'index';
    const SHOP  = 'shop';
    const LIFE  = "life";
    public function _empty() {
        $this->errRender();
    }

public function aa(){

    Vendor('Authorize.autoload');
    define("AUTHORIZENET_LOG_FILE", "phplog");

 // Common setup for API credentials
      $merchantAuthentication = new \net\authorize\api\contract\v1\MerchantAuthenticationType();
      $merchantAuthentication->setName('28uQm9n2V8');
      $merchantAuthentication->setTransactionKey('7c66vP83eM8amtQw');
      $refId = 'ref' . time();

      // Create the payment data for a credit card
      $creditCard = new \net\authorize\api\contract\v1\CreditCardType();
      $creditCard->setCardNumber("4111111111111111");
      $creditCard->setExpirationDate("1226");
      $creditCard->setCardCode("1234");
      $paymentOne = new \net\authorize\api\contract\v1\PaymentType();
      $paymentOne->setCreditCard($creditCard);

      $order = new \net\authorize\api\contract\v1\OrderType();
      $order->setDescription("New Item");

      $tax = new \net\authorize\api\contract\v1\ExtendedAmountType();
      $tax->setAmount('30.00');
      $tax->setName('tax');

      $shipping = new \net\authorize\api\contract\v1\ExtendedAmountType();
      $shipping->setAmount('50.00');
      $shipping->setName('shipping charges');

      $customer = new \net\authorize\api\contract\v1\CustomerDataType();
      $customer->setId('90');
      $customer->setEmail('501368569@qq.com');

      $shipTo = new \net\authorize\api\contract\v1\NameAndAddressType();
      $shipTo->setFirstName('wu');
      $shipTo->setLastName('zhi');


      //create a transaction
      $transactionRequestType = new \net\authorize\api\contract\v1\TransactionRequestType();
      $transactionRequestType->setTransactionType( "authCaptureTransaction"); 
      $transactionRequestType->setAmount('230.00');
      $transactionRequestType->setOrder($order);
      $transactionRequestType->setTax($tax);
      $transactionRequestType->setShipping($shipping);
      $transactionRequestType->setCustomer($customer);
      $transactionRequestType->setShipTo($shipTo);
      $transactionRequestType->setPayment($paymentOne);
      

      $request = new \net\authorize\api\contract\v1\CreateTransactionRequest();
      $request->setMerchantAuthentication($merchantAuthentication);
      $request->setRefId( $refId);
      $request->setTransactionRequest( $transactionRequestType);
 
      $controller = new \net\authorize\api\controller\CreateTransactionController($request);
 
      $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::SANDBOX);
echo "<pre>";
print_r($response);exit();
      return $response;
}

    public function bb(){
        $response = $this->aa();
        echo "<pre>";

    if ($response != null)  {
      $tresponse = $response->getTransactionResponse();
      if (($tresponse != null) && ($tresponse->getResponseCode() ==  1 )) {
        print_r($response->getMessages()->getResultCode());
        echo "Charge Credit Card AUTH CODE : " . $tresponse->getAuthCode() . "\n";
        echo "Charge Credit Card TRANS ID  : " . $tresponse->getTransId() . "\n";
      } else {
        print_r($response->getMessages()->getResultCode());
      }
    } else   {
      echo  "Charge Credit Card Null response returned";
    }
    }

    /**
     * 大首页数据
     */

    public function index()
    {
        try{
            if(IS_GET) {
                //缓存初始化
                abCache();
                if ( S('index_banner') ) {
                    //读缓存
                    $res = S('index_banner');
                } else {
                    //缓存过期或者没有读取数据库
                    $featured = D("BaseFeatured");
                    $life = $featured->getFeaturedById( $featured::INDEX_LIFE_TUIJIAN_ID);
                    $shop = $featured->getFeaturedById($featured::INDEX_SHOP_TUIJIAN_ID);
                    $res['life'] = $life[0];
                    $res['shop'] = $shop[0];
                    S('index_banner',$res);
                }
                $res1 = [];
                $res1['banner']['life'] = $res['life'];
                $res1['banner']['shop'] = $res['shop'];
                //大首页侧滑目录
                if ( S("cehua") ) {
                    $res2 = S('cehua');
                } else {
                    $help_cate = D('BaseHelpCategory');
                    $res2 = $help_cate->getTree();
                    S('cehua', $res2);
                }
                $home_arr = array("id"=>"", "help_url"=>"", "help_name"=>"HOME", "parent_id"=> "","url"=>"", "lev"=>0, "son"=>array());
                array_unshift($res2, $home_arr);
                $res1['help_cate'] = $res2;
                $this->_render(self::Err_Suc, $res1);
            } else {
                $this->_render(self::Err_Not_Legal);
            }
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    
    }

    /**
     * 大首页搜索
     * @param keyword 搜索的关键词
     * @param searchtype 搜索类型 有 index shop life
     * @param page 第几页
     * @param count 一页的数量
     */

    public function search() {
        // try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            
            if (  !isset( $getPost['searchType'] ) || empty( $getPost['searchType'] ) ||!isset( $getPost['page'] ) || empty( $getPost['page'] ) ||  !isset( $getPost['count'] ) || empty( $getPost['count'] ) ) {
                $this->_render(self::Err_No_Data);
            }
            $life_news = D("LifeNews");
            $goods = D("BaseGoods");
            $author_goods = D("BaseAuthorGoods");
            $author_gs_t_n = $author_goods->getTableName();
            $life_news_t_n = $life_news->getTableName();
            $goods_t_n = $goods->getTableName();
            $page = ( $getPost['page'] - 1) * $getPost['count'];
            if ( !isset( $getPost['keyword'] )) {
                $getPost['keyword'] = "";
            }
            $where = isset( $getPost['keyword'] ) && !empty( $getPost['keyword'] ) ? "" : " and (".$goods_t_n.".description like '%".$getPost['keyword']."%' or ".$goods_t_n.".title like '%".$getPost['keyword']."%' or ".$goods_t_n.".name like '%".$getPost['keyword']."%' or ".$author_gs_t_n.".authorname like '%".$getPost['keyword']."%' or (SELECT GROUP_CONCAT(a.news_label_name) FROM ab_life_news_type a WHERE a.b_id = ab_base_goods.id AND a.type IN (3) ) LIKE '%".$getPost['keyword']."%')";
                
            if ( $getPost['searchType'] == self::INDEX ) {
                //新闻搜索
                $life_k_news['length'] = array("length" => $life_news->searchNews( $page, $getPost['count'], $getPost['keyword'], 0) );
                $life_k_news['subdata'] = $life_news->searchNews($page, $getPost['count'], $getPost['keyword'], 1);

                //商品搜索
                $shop_k_goods['length'] = $goods->countShopGoodsBySearch($getPost['keyword']);
                $shop_k_goods['subdata'] = $goods->getShopGoodsBySearch($page, $getPost['count'], $getPost['keyword']);

                $data['life'] =  $life_k_news;
                $data['shop'] = $shop_k_goods;
            }
            if ( $getPost['searchType'] == self::LIFE ) {
                //新闻搜索
                $life_k_news['length'] = array( "length" => $life_news->searchNews( $page, $getPost['count'], $getPost['keyword'], 0) );
                $life_k_news['subdata'] = $life_news->searchNews($page, $getPost['count'], $getPost['keyword'], 1);
                $data = $life_k_news;
            }
            if ( $getPost['searchType'] == self::SHOP ) {
                $shop_k_goods['length'] = $goods->countShopGoodsBySearch($getPost['keyword']);
                $shop_k_goods['subdata'] = $goods->getShopGoodsBySearch($page, $getPost['count'], $getPost['keyword']);

                abCache();
                $data['goods'] = $shop_k_goods;
                if ( S("shop_filter") ) {
                    $filter_res = S("shop_filter");
                } else {
                    $filter = D("Dictype");
                    $filter_res = $filter->getFilter();
                    $filter_res = json_encode($filter_res);
                    $filter_res = json_decode($filter_res);
                }
                $data['filters'] = $filter_res;
            }
            $this->_render(self::Err_Suc, $data);
        // } catch (\Think\Exception $e) {
        //     $this->_render(self::Err_Failure);
        // }

    }

    public function captcha() {
        $captcha = new Verify();
        $captcha->length = 4;
        $captcha->entry(); 
    }

    /**
     * 服务帮助详情
     */


    public function helpViewId() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            
            if ( !isset( $getPost['HelpCateID'] ) || !isset( $getPost['HelpCateID'] )  ) {
                $this->_render(self::Err_No_Data);
            }
            $id = (int)$getPost['HelpCateID'];
            $help = D("BaseHelp");
            $help_res = $help->field("content, title")->where("caregory_id in (%d) and status in (1) and delete_status in (0) ", $id)->order("create_time desc")->limit(0, 1)->select();
            if ( count($help_res) < 1 ) {
                $this->_render(self::Err_No_Data);
            }
            $this->_render(self::Err_Suc, $help_res[0]);
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    
    }

    /**
     * 服务与帮助内的 公司地址
     */

    public function companyLocation(){
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $htmlStr['content'] = '
                Company Locations<br>
                Address:<br>
                2110 5th Avenue <br>
                Ronkonkoma, New York 11779 <br>
                United States<br>
                <iframe
                        width="100%"
                        height="100%"
                        frameborder="0" style="border:0"
                        src="https://www.google.com/maps/embed/v1/place?key=AIzaSyBfx1wJQlEMhgPfLRFj2ceG7Ooaz3u1hYo
                        &q=Artbean,Ronkonkoma+NY" allowfullscreen>
                    </iframe>';
            $this->_render(self::Err_Suc, $htmlStr);
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }

    }

    /**
     * 邮件订阅
     */

    public function subscribe() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            
            if ( !isset( $getPost['send_mail'] ) || empty( $getPost['send_mail'] )  ) {
                $this->_render(self::Err_No_Data);
            }
            if ( !ckEmail($getPost['send_mail'] ) ) {
                $this->_render(self::Err_Email_Wrong);
            }
            $model = M();
            $res = $model->query("select  email from  ab_base_sendMail where email = '%s'",$getPost['send_mail']);

            if ( $res ) {
                $this->_render(self::Err_Suc);
            }

            $res = $model->execute("insert into ab_base_sendMail (email) values ('".$getPost['send_mail']."')");

            if ( $res ) {
                $this->_render(self::Err_Suc);
            }

            $this->_render(self::Err_Failure);
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    
    }

    /**
     * app启动时用的接口
     * 主要是获取app 设备ID 
     * 用于推送服务，推送对象是所有用app登陆过的用户
     * @param app_id app 设备ID 
     */
    public function getAppId() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            if ( !isset( $getPost['email'] ) || !isset( $getPost['user_id'] ) ||  !isset( $getPost['app_id'] ) || empty( $getPost['app_id'] )  ) {
                $this->_render(self::Err_No_Data);
            }

            $user_res = $res = null;
            $now_time = date("Y-m-d H:i:s");
            $str = "";
            $arr = $app_info = array();
            if (  2 == $_SERVER['HTTP_TYPE']   ) {
                $type =  2;
            } else if ( 1 == $_SERVER['HTTP_TYPE'] ) {
                $type = 1;
            } else {
                $this->_render(self::Err_No_Data);
            }

            $push = D("Push");
            // if ( !empty($getPost['email']) && !empty($getPost['user_id'])) {
            //     $user = D("BaseUser");
            //     $user_res = $user->field('id')->where( 'user_status in (1) and delete_status in (0) and email= "%s" and id = %d', array($getPost['email'],$getPost['user_id']) )->find();
            //     if ( $user_res ) {
            //         $app_info = $push->field("id, app_id")->where(" type = '%s' and user_id = %d ", array($type, $getPost['user_id']))->select();
            //         $app_info = $app_info[0];
            //     }
            // } else {
            $user_ = false;
            $user = D("BaseUser");
            if ( !empty($getPost['email']) && !empty($getPost['user_id'])) {
                $user_res = $user->field('id')->where( 'user_status in (1) and delete_status in (0) and email= "%s" and id = %d', array($getPost['email'],$getPost['user_id']) )->find();
                if ( !$user_res ) {
                    
                    $this->_render(self::Err_No_Data);
                }
                $user_ = true;
                // $app_info = $push->field("id,app_id, user_id")->where(" user_id = %d and type = '%s'", array($user_res['id'], $type) )->select();
            } 
            // if ( count($app_info) < 1) {
            //     $app_info = $push->field("id,app_id, user_id")->where(" app_id = '%s' and type = '%s' ", array($getPost['app_id'], $type) )->select();
       
            // }
            $app_info = $push->field("id,app_id, user_id")->where(" app_id = '%s' and type = '%s' ", array($getPost['app_id'], $type) )->select();
// print_r($app_info);exit();
// print_r($user_res);exit();
            if ( count($app_info) < 1 ) {
                $data['app_id'] = $getPost['app_id'];
                $data['type'] = $type;
                $data['oper_date'] = $now_time;
                if ( $user_  ) {
                    $data['user_id'] = $user_res['id'];
                }
                $res = $push->data($data)->filter('strip_tags')->add();
            } else {
                $app_info = $app_info[0];
                //if ( empty($app_info['user_id']) ) {
                    $app_info2 = $push->field("id, app_id")->where(" type = '%s' and user_id = %d ", array($type, $getPost['user_id']))->select();
                    // print_r($app_info2);exit();
                    if ( count($app_info2) < 1 ) {
                        $data1['user_id'] = $user_res['id'];
                        $data1['oper_date'] = $now_time;
                        $res = $push->data($data1)->where("id = %d", $app_info['id'])->filter("strip_tags")->save();
                    } else {
                        //print_r($app_info);exit();
                        $app_info2 = $app_info2[0];
                        $data3['user_id'] = 0;
                        $data3['oper_date'] = $now_time;
                        $push->data($data3)->where('id = %d', $app_info2['id'])->filter("strip_tags")->save();
                        $data4['user_id'] = $user_res['id'];
                        $data4['oper_date'] = $now_time;
                        $res = $push->data($data4)->where("id = %d", $app_info['id'])->filter("strip_tags")->save();
                    }
                // } else {

                //     $data2['user_id'] = $user_res['id'];
                //     $data2['oper_date'] = $now_time;
                //     $res = $push->data($data2)->where("id = %d", $app_info['id'])->filter("strip_tags")->save();
                // }
            
                
            }

            if ( $res ) {
                $this->_render(self::Err_Suc);
            }
            $this->_render(self::Err_Failure);
            // }














            // if ( $app_info ) {
            //     if (  2 == $_SERVER['HTTP_TYPE']   ) {
            //         if ( $app_info['ios_id'] != $getPost['app_id'] ) {
            //             $sql = " update tb_push set ios_id = %d where id = %d ";
            //         }
            //     } else if (  1 == $_SERVER['HTTP_TYPE'] ) {
            //         if ( $app_info['android_id'] != $getPost['app_id'] ) {
            //             $sql = " update tb_push set android_id = %d where id = %d "
            //         }
            //     }
            // }
            // if (  2 == $_SERVER['HTTP_TYPE']   ) {
            //     $data['ios_id'] = $getPost['app_id'];
            //     $str .= " and ios_id = %d ";
            //     $arr[] = $getPost['app_id'];
            // } else if ( 1 == $_SERVER['HTTP_TYPE'] ){
            //     $data['android_id'] = $getPost['app_id'];
            //     $str .= " and android_id = %d ";
            //     $arr[] = $getPost['app_id'];
            // } else {
            //     $this->_render(self::Err_No_Data);
            // }
            // $push = D("Push");
            // $app_info = $push->field("id")->where($str, $arr)->select();
            // $data['oper_date'] = $now_time;
            // if ( empty($app_info) ) {
            //     $res = $push->data($data)->filter('strip_tags')->add();
            // } else {
            //     $data2['oper_date'] = $now_time;
            //     $res = $push->where("id = ".$app_info[0]['id'])->filter('strip_tags')->save($data2);
            // }
            // if ( $res ) {
            //     $this->_render(self::Err_Suc);
            // }
            // $this->_render(self::Err_Failure);
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    

    }
    /**
     * 联系我们
     */
    public function contactUs() {
        $info = array("Shipping and Returns","Advertising","Partnerships","Framing","Press","General lnquiries");

        if ( IS_GET ) {
             $this->_render(self::Err_Suc, $info);
        }
        if ( IS_POST ) {
            try{
                $post_data = file_get_contents('php://input','r');
                if ( !$post_data ) {
                    $this->_render(self::Err_No_Data);
                }
                $getPost = json_decode($post_data, 1);
                if ( !isset( $getPost['first_name'] ) || empty( $getPost['first_name'] ) || !isset( $getPost['last_name'] ) || empty( $getPost['last_name'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] ) || !isset( $getPost['department'] ) || empty( $getPost['department'] ) || !in_array($getPost['department'],$info) || !isset( $getPost['comments'] ) || empty( $getPost['comments'] )  ) {
                    $this->_render(self::Err_No_Data);
                }
                if ( !ckEmail($getPost['email']) ) {
                    $this->_render(self::Err_Email_Wrong);
                }
                $data['first_name'] = $getPost['first_name'];
                $data['last_name'] = $getPost['last_name'];
                $data['email'] = $getPost['email'];
                $data['department'] = $getPost['department'];
                $data['comments'] = $getPost['comments'];
                $data['delete_status'] = 0;
                $data['create_time'] = date("Y-m-d H:i:s");
                $data['status'] = 0;
                $contact = M('BaseContact');
                if ($contact->data($data)->filter('strip_tags')->add()){
                    $this->_render(self::Err_Suc, null);
                }
                $this->_render(self::Err_Failure);
            } catch (\Think\Exception $e) {
                $this->_render(self::Err_Failure);
            }
    
        }
        $this->_render(self::Err_Not_Legal);
    }


    /**
     * 艺术家申请
     */
    public function aristSubmission() {

        $area = D("BaseArea");
        $country = $area->getCountry();
        $dic = D("Diction");
        $medium = $dic->getFilterTion("and sys_diction.typeid in (%d) ", array(65));
        if ( IS_GET ) {
            $this->_render(self::Err_Suc, array("country"=>$country, "medium"=>$medium));
        }

        if ( IS_POST ) {
            try{
                $firstname = I("post.first_name");
                $lastname = I("post.last_name");
                $email = I("post.email");
                // $verify_code = I("post.verify_code");
                $_medium = I("post.medium");
                if ( empty($firstname) || empty($lastname) || empty($email) || !isset($_FILES['photo1']) || empty($_FILES['photo1']['name']) || empty( $_medium ) ) {
                    $this->_render(self::Err_No_Data);
                }
                $_country = I("post.country");
                $phone = I("post.phone");
                $website = I("post.website");
                $pass = 1;
                if ( !empty($_country) ) {
                    foreach ( $country as $k1 => $v1 ) {
                        if ($v1['id'] == $_country) {
                            $pass = 2;
                            break;
                        }
                        $pass = 3;
                    }
                }
                if ( !empty($_medium) ) {
                    foreach ( $medium as $k2 => $v2 ) {
                        if ($v2['id'] == $_medium) {
                            $pass = 2;
                            break;
                        }
                        $pass = 3;
                    }
                }
                if ( !ckEmail($email) ) {
                    $this->_render(self::Err_Email_Wrong);
                }
                if ( !empty($phone) && !ckMobile($phone) ) {
                    $this->_render(self::Err_Phone_Wrong);
                }
                if ( !empty($website) && !ckWebsite($website)) {
                    $this->_render(self::Err_Website_Wrong);
                }
                if ( 3 ==  $pass ) {
                    $this->_render(self::Err_Failure);
                }
                //session_id(session_id());
                $captcha = new  Verify();
                // if ( !$captcha->check( $verify_code ) ) {
                //     $this->_render(self::Err_Verify_False);
                // }
                $upload = new \Think\Upload();// 实例化上传类
                $upload->maxSize   =     10485760 ;// 设置附件上传大小  10M 后期再改
                $upload->rootPath  =     './Uploads/'; // 设置附件上传根目录
                // $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
                $upload->savePath  =     ''; // 设置附件上传（子）目录
                // 上传文件 
                $info   =   $upload->upload();
                if(!$info) {// 上传错误提示错误信息
                    $error = $upload->getError();
                    $this->_render(self::Err_Failure,"",L($error));
                }
                $i = 0;
                $imgUrl = C('IMG_URL'); 
                foreach ( $info as $k => $v) {
                    if ( $i  > 2 ) {
                        break;
                    }
                    $file = array($v['key']=>curl_file_create(realpath("./Uploads/".$v['savepath'].$v['savename'])) );
                    $imgInfo[$v['key']] =  explode("||", request_img_post($imgUrl, $file) ) ;
                    $i++;
                
                }

                if ( !$imgInfo ) {
                    $this->_render(self::Err_Failure);
                } 
                //删除本地图片
                deldir("./Uploads/".date("Y-m-d"));

                $data['firstname'] = I("post.first_name");
                $data['lastname'] = I("post.last_name");
                $data['email'] = $email;
                $data['phone1'] = $phone;
                $data['address1'] = I("post.address1");
                $data['address2'] = I("post.address2");
                $data['city'] = I("post.city");
                $data['state'] = I("post.state");
                $data['country'] = $_country;
                $data['medium'] = $_medium;
                $data['website'] = $website;
                $data['documentation'] = I("post.documentation");
                $data['aritistbio'] = I("post.aritistbio");
                $data['remark'] = I("post.remark");
                $data['file1name'] = $info["photo1"]['name'];
                $data['file1path'] = $imgInfo['photo1'][1];
                if ( count($info) > 1 ) {
                    $data['file2name'] = $info["photo2"]['name'];
                    $data['file2path'] = $imgInfo['photo2'][1];
                }
                if ( count($info) > 2 ) {
                    $data['file3name'] = $info["photo3"]['name'];
                    $data['file3path'] = $imgInfo['photo3'][1];
                }
                $data['createtime'] = date("Y-m-d H:i:s");
                $data['status'] = 0;
                $sumission = D("BaseSubmitwork");
                $res = $sumission->data($data)->filter('strip_tags')->add();
                if ( $res ) {
                    $url =  "https://mandrillapp.com/api/1.0/templates/info.json?key=rawgdIlhVJf5ph4hGg5tag&name=Artist%20Submission%20Received" ;
                    $res = request_get($url);
                    $res = json_decode($res, true);
                    $subject = $res['name'];
                    $body = $res['code'];
                    $body = str_replace("[First Name]", $data['firstname'], $body);
                    $body = str_replace("[Last Name]", $data['lastname'], $body);
                    $body = str_replace("[Artist's Email]", $email, $body);
                    $body = str_replace("[Address Line 1]", $data['address1'], $body);
                    $body = str_replace("[Address Line 2]", $data['address2'], $body);
                    $body = str_replace("[City]", $data['city'], $body);
                    $body = str_replace("[State]", $data['state'], $body);
                    $body = str_replace("[Country]", $_country, $body);
                    $body = str_replace("[website address]", $website, $body);
                    $body = str_replace("[Medium]", $_medium, $body);
                    $body = str_replace("[Artist Bio]", $data['aritistbio'], $body);
                    $body = str_replace("[Additional Info]", $data['remark'], $body);
                    $body = str_replace("[Documentation]", $data['documentation'], $body);

                    SendMail( "emily.rapuano@artbean.com", $subject, $body );
                    SendMail( "caitlin.newman@artbean.com", $subject, $body );
                    SendMail( "jlipp@artbean.com", $subject, $body );

                    $this->_render(self::Err_Suc, null);
                }

            } catch (\Think\Exception $e) {
                $this->_render(self::Err_Failure);
            }
         }
    }
    /**
     * 获取Ip地址
     * 后期添加ip地址库 判断国外网络还是国内网络
     */
    public function getClientIp() {
        if ( !IS_POST ) {
            $this->_render(self::Err_Not_Legal);
        }
        if ( APPVERSION == 1) {
            $this->_render(self::Err_Suc, array("v"=>"C0021112016"));
        } else {
            $this->_render(self::Err_Suc, array("v"=>"A0021102016"));
        }
        
        //$type       =  $type ? 1 : 0;
        // static $ip  =   NULL;
        // if ($ip !== NULL) return $ip[$type];
        // if($_SERVER['HTTP_X_REAL_IP']){//nginx 代理模式下，获取客户端真实IP
        //     $ip=$_SERVER['HTTP_X_REAL_IP'];     
        // }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {//客户端的ip
        //     $ip     =   $_SERVER['HTTP_CLIENT_IP'];
        // }elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {//浏览当前页面的用户计算机的网关
        //     $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        //     $pos    =   array_search('unknown',$arr);
        //     if(false !== $pos) unset($arr[$pos]);
        //     $ip     =   trim($arr[0]);
        // }elseif (isset($_SERVER['REMOTE_ADDR'])) {
        //     $ip     =   $_SERVER['REMOTE_ADDR'];//浏览当前页面的用户计算机的ip地址
        // }else{
        //     $ip=$_SERVER['REMOTE_ADDR'];
        // }
        // // IP地址合法验证
        // $long = sprintf("%u",ip2long($ip));
        // $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        // $content = file_get_contents('./ipdata.db');
        // eval("\$ipdata = $content;");
        // $ip2 = sprintf('%u',ip2long($ip));
        // $tag = reset(explode('.',$ip));
        // if( !$ip || '192'== $tag ||'127'== $tag || !isset($ipdata[$tag]) ) {
        //     $this->_render(self::Err_Suc, array("v"=>"C0029042016"));
        // }
        // foreach($ipdata[$tag] as $k =>$v) {
        //     if($v[0] <= $ip2 &&$v[1] >= $ip2) {
        //         $this->_render(self::Err_Suc, array("v"=>"C0029042016"));
        //     }
        // }
        
    }

    public function getIndexBackImg() {
        try{

            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }

            abCache();
            if ( S('index_back_img') ) {
                //读缓存
                $res = S('index_back_img');
            } else {
                $featured = D("BaseFeatured");
                $res = $featured->getFeaturedById($featured::INDEX_TUIJIAN_ID);
                $res = $res[0];
                S('index_back_img',$res);
            }
            $this->_render(self::Err_Suc, $res);
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    }


}