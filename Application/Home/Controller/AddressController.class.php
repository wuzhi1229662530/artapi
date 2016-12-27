<?php
namespace Home\Controller;
use Home\Controller\MyController;

class AddressController extends MyController {
    public function _empty() {
        $this->errRender();
    }
    

    /**
     * 收货地址列表
     */
    public function addressList() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            
            if (!isset( $getPost['user_id'] ) || empty( $getPost['user_id'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] ) || !isset( $getPost['token'] ) || empty( $getPost['token'] ) ){
                $this->_render(self::Err_No_Data);
            }
            $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id']);
            $add = D("ShopAddress");
            $where = " and ab_shop_address.user_id = ".$getPost['user_id'];
            $res = $add->getAddress($where);
            $this->_render(self::Err_Suc, $res);
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }

    }

    /**
     * 下拉城市信息
     */
    public function addressState() {
        if ( !IS_POST ) {
            $this->_render(self::Err_Not_Legal);
        }
        $add = D("BaseArea");
        $address = $add->getTreeAdd();
        $this->_render(self::Err_Suc, $address);
    }

    /**
     * 添加地址
     */
    public function addAddress() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }

            $getPost = json_decode($post_data, 1);
            if (!isset( $getPost['ext'] )  ||  !isset( $getPost['addr_default'] ) || !isset( $getPost['addr'] ) || empty( $getPost['addr'] ) || !isset( $getPost['address'] )  || !isset( $getPost['province'] ) || empty( $getPost['province'] ) || !isset( $getPost['area'] ) || empty( $getPost['area'] ) || !isset( $getPost['postcode'] ) || empty( $getPost['postcode'] ) || !isset( $getPost['phone'] ) || !isset( $getPost['mobile'] ) || empty($getPost['mobile'] ) || !isset( $getPost['lastname'] ) || empty( $getPost['lastname'] ) || !isset( $getPost['firstname'] ) || empty( $getPost['firstname'] ) || !isset( $getPost['user_id'] ) || empty( $getPost['user_id'] ) || !isset( $getPost['token'] ) || empty( $getPost['token'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] )  ) {
                $this->_render(self::Err_No_Data);
            }
            if ( !ckMobile( (int)$getPost['mobile'] ) ) {
                $this->_render(self::Err_Phone_Wrong);
            }
            if ( !empty($getPost['ext']) && !preg_match("/^\d{1,4}$/", $getPost['ext'] ) ) {
                $this->_render(self::Err_Failure, "", L("ext格式不正确") );
            }
            $add = D("ShopAddress");
            $postcode = $this->ckAddress($add,  $getPost);
            $user_info = $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id'],true);

            if ( 1 == $getPost['addr_default'] ) {
                $where = " and is_default = 1 and  user_id = ".$getPost['user_id'];
                $default_res = $add->getAddress($where);
                if ( $default_res ) {
                    $dat['is_default'] = 0;
                    $add->where("id = %d", array($default_res[0]['id']))->filter('strip_tags')->save($dat);
                }
            }

            $now_time = date("Y-m-d H:i:s", time());
            $data['user_id'] = $getPost['user_id'];
            if (   strlen($getPost['firstname'] ) > 14 ) {
                $this->_render(self::Err_Username_Wrong);
            }
            $data['firstname'] = $getPost['firstname'];
            if (   strlen($getPost['lastname'] ) > 14 ) {
                $this->_render(self::Err_Username_Wrong);
            }
            $data['lastname'] = $getPost['lastname'];
            $data['accept_name'] = $getPost['firstname']." ".$getPost['lastname'];
            $data['mobile'] = $getPost['mobile'];
            //$data['phone'] = $getPost['phone'];
            $data['postcode'] = $postcode;
            $data['province'] = $getPost['province'];
            $data['city'] = $getPost['area'];
            $data['addr'] = $getPost['addr'];
            $data['address'] = $getPost['address'];
            if (!empty($getPost['ext'])) {
                $data['ext'] = $getPost['ext'];
            }
            //$data['email'] = $getPost['email_address'];
            $data['is_default'] = $getPost['addr_default'];
            $data['adr_type'] = 0;
            $data['create_time'] = $now_time;
            $data['delete_status'] = 0;
            if ( $add->data($data)->filter('strip_tags')->add() ) {
                $res = $add->field("id")->where("user_id = %d and firstname = '%s' and lastname = '%s' and addr = '%s' and create_time = '".$now_time."'", array($getPost['user_id'], $getPost['firstname'], $getPost['lastname'], $getPost['addr'])  )->find();
                $url =  "https://mandrillapp.com/api/1.0/templates/info.json?key=rawgdIlhVJf5ph4hGg5tag&name=Change%20Shipping%20Address%20-%20Final" ;
                $res1 = request_get($url);
                $res2 = json_decode($res1, true);
                $subject = $res2['name'];
                $body = $res2['code'];
                $body = str_replace("[Customer Name]", $user_info['username'], $body);
                SendMail( $getPost['email'], $subject, $body );
                $this->_render(self::Err_Suc, $res );
            }
            $this->_render(self::Err_Failure);
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }

    }

    /**
     * 地址邮编是合法，中间方法，不做接口调用
     */
    protected function ckAddress($add, $getPost) {
        $province = $add->query("select a.simple_code from ab_base_area as a where a.id = ".$getPost['province']);
        $post_data1 = array(
          'PostalCode' => $getPost['postcode'],
          'address'=> $getPost['addr'].' '.$getPost['address'],
          "province"=> $province[0]['simple_code'],
          "city" => $getPost['area']
        );

        $pass = false;
        for ($i=0; $i < 5 ; $i++) { 

            $res = json_decode(request_post("http://www.artbean.net/ups/validationAddress", $post_data1), true);
            if ( 1 == $res[0]['responseStatusCode'] && count($res[0]['addressList']) == 1 ) {
                $pass = true;
                break;
            }
        }
        
        if ( !$pass ||  strtoupper($res[0]['addressList'][0]['politicaldivision2']) != strtoupper($getPost['area']) ) {
            $this->_render(self::Err_Invalid_Zip);
        }
        return $res[0]['addressList'][0]['postcodePrimaryLow'];
    }

    /**
     * 删除地址
     */
    public function deleteAddress() {

        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            
            if (!isset( $getPost['user_id'] ) || empty( $getPost['user_id'] ) ||!isset( $getPost['address_id'] ) || empty( $getPost['address_id'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] ) || !isset( $getPost['token'] ) || empty( $getPost['token'] ) ) {
                $this->_render(self::Err_No_Data);
            }
            $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id']);
            $add = D("ShopAddress");
            if ( $add->where("user_id = %d and id = %d", array($getPost['user_id'], $getPost['address_id']))->delete() ) {
                $this->_render(self::Err_Suc, L('删除成功') );
            }
            $this->_render(self::Err_Failure);
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    
    }

    /**
     * 地址详情
     */
    public function addressDetail() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            
            $getPost = json_decode($post_data, 1);
            
            if (!isset( $getPost['address_id'] ) || empty( $getPost['address_id'] ) || !isset( $getPost['token'] ) || empty( $getPost['token'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] ) || !isset( $getPost['user_id'] ) || empty( $getPost['user_id'] ) ) {
                $this->_render(self::Err_No_Data);
            }
            $add = D("ShopAddress");
            $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id']);
            $where = " and ab_shop_address.user_id = ".$getPost['user_id']." and ab_shop_address.id = ".$getPost['address_id'];
            $res = $add->getAddress($where);
            //print_r( $res[0] );exit();
            if ( empty($res) ) {
                $this->_render(self::Err_No_Data);
            }
            $aa = array(
                "id"=>"58",
                "is_default"=>"1",
                "phone"=>"6",
                "postcode"=>"5",
                "addr"=>"2",
                "address"=>"4",
                "firstname"=>"1",
                "lastname"=>"2",
                "area"=>"187",
                "province"=>"239",
                "user_id"=>"112",
                "addr_username"=>"1 2",
                "mobile"=>"7",
                "city"=>"Autauga ",
                "state"=>"Alabama",
                "real_address"=>"AlabamaAutauga 24"
            );
            $this->_render(self::Err_Suc, $res[0]);
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    
    }

    /**
     * 修改地址
     */
    public function editAddress() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
     
     
            if ( !isset( $getPost['address_id'] ) || empty($getPost['address_id']) || !isset( $getPost['ext'] )  ||  !isset( $getPost['addr_default'] ) || !isset( $getPost['addr'] ) || empty( $getPost['addr'] ) || !isset( $getPost['address'] )  || !isset( $getPost['province'] ) || empty( $getPost['province'] ) || !isset( $getPost['area'] ) || empty( $getPost['area'] ) || !isset( $getPost['postcode'] ) || empty( $getPost['postcode'] ) || !isset( $getPost['phone'] ) || !isset( $getPost['mobile'] ) || empty($getPost['mobile'] ) || !isset( $getPost['lastname'] ) || empty( $getPost['lastname'] ) || !isset( $getPost['firstname'] ) || empty( $getPost['firstname'] ) || !isset( $getPost['user_id'] ) || empty( $getPost['user_id'] ) || !isset( $getPost['token'] ) || empty( $getPost['token'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] )  ) {
                $this->_render(self::Err_No_Data);
            } 
            if ( !empty($getPost['ext']) &&  !preg_match("/^\d{1,4}$/", $getPost['ext'] ) ) {
                $this->_render(self::Err_Failure, "", L("ext格式不正确") );
            }
            $add = D("ShopAddress");
            $postcode = $this->ckAddress($add, $getPost);
            $user_info = $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id'], true);
            $where = " and ab_shop_address.user_id = ".$getPost['user_id']." and ab_shop_address.id = ".$getPost['address_id'];
           
            $addr_id_res = $add->getAddress($where);
            if ( empty($addr_id_res) ) {
                $this->_render(self::Err_No_Data);
            }
            if ( !empty( $getPost['mobile'] ) &&  !ckMobile( $getPost['mobile'] ) ) {
                $this->_render(self::Err_Phone_Wrong);
            }
            
            //取当前id 收获地址 是否 为默认
            if ( 1 != $addr_id_res[0]['is_default'] && 1 == $getPost['addr_default']) {
                $where = " and ab_shop_address.is_default = 1 and  ab_shop_address.user_id = ".$getPost['user_id'];
                $default_res = $add->getAddress($where);
                if ( $default_res ) {
                    $dat['is_default'] = 0;
                    $add->where("id = %d", array($default_res[0]['id']))->filter('strip_tags')->save($dat);
                }
            }
            if ( strlen($getPost['firstname'] ) > 14 ) {
                $this->_render(self::Err_Username_Wrong);
            }
            $data['firstname'] = $getPost['firstname'];
            if ( strlen($getPost['lastname'] ) > 14 ) {
                $this->_render(self::Err_Username_Wrong);
            }
            $data['lastname'] = $getPost['lastname'];
            $data['accept_name'] = $getPost['firstname']." ".$getPost['lastname'];
            $data['mobile'] = $getPost['mobile']; 
            $data['postcode'] = $postcode;
            $data['province'] = $getPost['province'];
            $data['city'] = $getPost['area'];
            $data['addr'] = $getPost['addr'];
            $data['ext'] = $getPost['ext'];
            $data['email_address'] = $getPost['email_address'];
            $data['address'] = $getPost['address'];
            $data['is_default'] = $getPost['addr_default'];
            $data['update_time'] = date("Y-m-d H:i:s", time());
            if ( $add->where("id = %d and user_id = %d", array($getPost['address_id'], $getPost['user_id']))->filter('strip_tags')->save($data) ) {
                $url =  "https://mandrillapp.com/api/1.0/templates/info.json?key=rawgdIlhVJf5ph4hGg5tag&name=Change%20Shipping%20Address%20-%20Final" ;
                $res = request_get($url);
                $res = json_decode($res, true);
                $subject = $res['name'];
                $body = $res['code'];
                $body = str_replace("[Customer Name]", $user_info['username'], $body);
                SendMail( $getPost['email'], $subject, $body );
                $this->_render(self::Err_Suc, L('修改成功') );
            }
            $this->_render(self::Err_Failure);
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }

    }
}