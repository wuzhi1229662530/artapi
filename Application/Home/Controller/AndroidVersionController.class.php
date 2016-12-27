<?php
namespace Home\Controller;
use Home\Controller\MyController;

class AndroidVersionController extends MyController {


    public function _empty() {
        $this->errRender();
    }

    public function update() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            
            if ( !isset($getPost['status']) || empty($getPost['status']) ) {
                $this->_render(self::Err_No_Data);
            }

            $andriod = D("AndroidVersion");
            $res = $andriod->query(' SELECT `id`, `version`, `url`, `desc` FROM ab_android_version WHERE `delete_status` in (0) and `status` in ('.$getPost['status'].')  order by id desc limit 0,1');
            $this->_render(self::Err_Suc, $res);
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    
    }

    // public function add() {
    //     try{
    //         if ( !IS_POST ) {
    //             $this->_render(self::Err_Not_Legal);
    //         }
    //         $post_data = file_get_contents('php://input','r');
    //         if ( !$post_data ) {
    //             $this->_render(self::Err_No_Data);
    //         }
    //         $getPost = json_decode($post_data, 1);
            
    //         if ( !isset($getPost['status']) || empty($getPost['status']) || !isset( $getPost['desc'] ) || empty($getPost['desc']) || !isset( $getPost['version'] ) || empty($getPost['version']) || !isset( $getPost['url'] ) || empty( $getPost['url'] ) ) {
    //             $this->_render(self::Err_No_Data);
    //         }

    //         $andriod = D("AndroidVersion");
    //         $data['version'] = $getPost['version'];
    //         $data['url'] = $getPost['url'];
    //         $data['desc'] = $getPost['desc'];
    //         $data['delete_status'] = 0;
    //         $data['create_time'] = date("Y-m-d H:i:s");
    //         $data['status'] = $getPost['status'];
    //         $andriod->data($data)->filter('strip_tags')->add();
    //         $this->_render(self::Err_Suc, "");

    //     } catch (\Think\Exception $e) {
    //         $this->_render(self::Err_Failure);
    //     }
    // }

}