<?php
namespace Home\Controller;
use Home\Controller\MyController;
class ConlistController extends MyController {
    public function _empty() {
        $this->errRender();
    }
    /**
     * 接口所有地址列表
     */
    public function index(){
           $modules = array('Home');  //模块名称
           $i = 0;
           foreach ($modules as $module) {
               $all_controller = $this->getController($module);
               foreach ($all_controller as $controller) {
                   $controller_name = $module.'/'.$controller;
                   $all_action = $this->getAction($controller_name);
                   foreach ($all_action as $action) {
                      $data[$controller.'_'.$action] = 'http://'.$_SERVER['HTTP_HOST'].__ROOT__.'/'.$module.'/'.$controller.'/'.$action;
                      $i++;
                  }
              }
          }
          if (IS_GET) {
            $this->data = $data;
            $this->error = self::Err_Suc;
            $this->desc = self::Desc_Suc;
          }
          $this->render();
      }
      //获取所有控制器名称
      protected function getController($module){
          if(empty($module)) return null;
          $module_path = APP_PATH . '/' . $module . '/Controller/';  //控制器路径
          if(!is_dir($module_path)) return null;
          $module_path .= '/*.class.php';
          $ary_files = glob($module_path);
          foreach ($ary_files as $file) {
              if (is_dir($file)) {
                  continue;
              }else {
                  $files[] = basename($file, C('DEFAULT_C_LAYER').'.class.php');
              }
          }
          return $files;
      }
      //获取所有方法名称
      protected function getAction($controller){
          if(empty($controller)) return null;
          $con = A($controller);
          $functions = get_class_methods($con);
          //排除部分方法
          $inherents_functions = array('getTaxAvc', 'getShipMoney','ckUser','ckSessionToken', 'deleteSessionToken', '_initialize','_render', 'emailVerify', 'errRender', 'getController', '__construct','getAction','isAjax','display','show','fetch','buildHtml','assign','__set','get','__get','__isset','__call','error','success','ajaxReturn','redirect','__destruct', '_empty', 'theme','render');
          foreach ($functions as $func){
              if(!in_array($func, $inherents_functions)){
                  $customer_functions[] = $func;
              }
          }
          return $customer_functions;
      }
  }