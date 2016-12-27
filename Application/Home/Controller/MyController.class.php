<?php

namespace Home\Controller;
use Think\Controller;
use Home\Model\ApiConstModel;
use Home\Model\BaseUserModel;
class MyController extends Controller {

    protected $getPostData = array();

    public function _initialize(){
        //$this->checkParam();

    }

    protected function checkParam() {
        if(!IS_POST) {
            $this->myResponse(ApiConstModel::ERROR);
        }
        $raw_post_data = file_get_contents('php://input','r');
        if ( !$raw_post_data || empty($raw_post_data)) {
            $this->myResponse(ApiConstModel::ERROR);
        }
        $getPostData = json_decode($raw_post_data,1);
        $token = null;
        $filterActionArr = ['shop/index'];
        if ( !in_array(strtolower($_SERVER["PATH_INFO"]), $filterActionArr )  ) {
            if ( !isset($_SERVER['HTTP_TOKENS']) || empty($_SERVER['HTTP_TOKENS']) ) {
                $this->myResponse(ApiConst::ERROR);
            }
            $token = $_SERVER['HTTP_TOKENS'];
            if ( !isset($getPostData['user_id']) || empty($getPostData['user_id']) ) {
                $this->myResponse(ApiConst::ERROR);
            }
            $memberModel = new BaseUserModel();
            $memberTokenInfo = $memberModel->getLoginedMember(['user_id'=>$getPostData['user_id'], 'token'=>$token]);

        }

    }

    protected function noParam() {
        if(!IS_POST) {
            $this->myResponse(ApiConst::ERROR);
        }
    }

    protected function myResponse( $errNum, $data="", $desc="", $err_data = false ) {
        $arr = array();
        if ( $errNum == 0 || $err_data == true ) {
            $arr['data'] = $data;
        }
        $arr['error'] = $errNum;
        $arr['desc'] = empty( $desc ) ?  ApiConstModel::$errArr[$errNum]  : $desc;
        echo json_encode($arr);exit();
    }
}