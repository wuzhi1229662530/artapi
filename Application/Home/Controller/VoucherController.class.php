<?php
namespace Home\Controller;
use Home\Controller\MyController;

class VoucherController extends MyController {
    public function _empty() {
        $this->errRender();
    }
    

    // /**
    //  * 获取优惠活动
    //  */
    // public function getVoucherActivity() {
    //     try{
    //         if ( !IS_POST ) {
    //             $this->_render(self::Err_Not_Legal);
    //         }
    //         $voucher = D("ShopVoucher");
    //         $res = $voucher->getRandomVoucherActivity(0);
    //         $data['activity'] = $res[0];
    //         $this->_render(self::Err_Suc, $data);
    //     } catch (\Think\Exception $e) {
    //         $this->_render(self::Err_Failure);
    //     }

    // }

    // /**
    //  * 获取优惠活动未领取的优惠券
    //  */

    // public function getRandomVoucherRule() {
    //     try{
    //         if ( !IS_POST ) {
    //             $this->_render(self::Err_Not_Legal);
    //         }
    //         $post_data = file_get_contents('php://input','r');
    //         if ( !$post_data ) {
    //             $this->_render(self::Err_No_Data);
    //         }
    //         $getPost = json_decode($post_data, 1);
            
    //         if ( !isset( $getPost['activity_id'] ) || empty( $getPost['activity_id'] )   ) {
    //             $this->_render(self::Err_No_Data);
    //         }
    //         $voucher = D("ShopVoucher");
    //         $data['voucher'] = $voucher->getRandomVoucherRule($getPost['activity_id']);
    //         $this->_render(self::Err_Suc, $data);
    //     } catch (\Think\Exception $e) {
    //         $this->_render(self::Err_Failure);
    //     }
    
    // }

    // /**
    //  * 用户领取活动未领取的优惠券
    //  */
    // public function getRandomVoucher() {
    //     try{
    //         if ( !IS_POST ) {
    //             $this->_render(self::Err_Not_Legal);
    //         }
    //         $post_data = file_get_contents('php://input','r');
    //         if ( !$post_data ) {
    //             $this->_render(self::Err_No_Data);
    //         }
    //         $getPost = json_decode($post_data, 1);
            
    //         if ( !isset( $getPost['money'] ) || empty( $getPost['money'] ) || !isset( $getPost['rule_id'] ) || empty( $getPost['rule_id'] ) || !isset( $getPost['activity_id'] ) || empty( $getPost['activity_id'] ) || !isset( $getPost['user_id'] ) || empty( $getPost['user_id'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] )  || !isset( $getPost['token'] ) || empty( $getPost['token'] )   ) {
    //             $this->_render(self::Err_No_Data);
    //         }
    //         $voucher = D("ShopVoucher");
    //         $res = $voucher->getRandomVoucher(array($getPost['rule_id'], $getPost['activity_id'],$getPost['money']));
    //         if ( count($res) < 1 ) {
    //             $this->_render(self::Err_Voucher_Over);
    //         }
    //         $voucher_id = $res[0]['voucher_id'];
    //         $data['userid'] = $getPost['user_id'];
    //         $voucher = D("ShopVoucher");
    //         if ( $voucher->data($order_data)->filter('strip_tags')->save() ) {
    //             $this->_render(self::Err_Suc,null);
    //         }
    //         $this->_render(self::Err_Failure);
    //     } catch (\Think\Exception $e) {
    //         $this->_render(self::Err_Failure);
    //     }
    
    // }
 
}