<?php
namespace Home\Model;
use Home\Model\MyModel;
class BaseAreaModel extends MyModel
{
    protected $tableName = 'base_area';
 /**
  * 默认去美国ID 
  */
    public function getTreeAdd( $pid = 1, $lev = 0){
        // static $data = array();
        // if( empty( $data ) ) {
        //     $data = $this->field('id, city_code, name, simple_code, parent_id')
        //                 ->where('delete_status = 0')
        //                 ->select();
        // }
        // $tree = array();
        // foreach ( $data as $k => $v ) {
        //     if ( $v['parent_id'] == $pid ) {
        //         $v['lev'] = $lev;
        //         $v['son'] = $this->getTreeAdd($v['id'], $lev+1);
        //         $tree[] = $v;
        //     }
        // }
        $data = $this->field('id, city_code, name, simple_code')->where("parent_id in (1) and delete_status in (0)")->order("simple_code asc")->select();
        return $data;
    }

    /**
     * 取所有国家
     */
    public function getCountry() {
        $res = $this->field('id,name')->where("parent_id in (0) and delete_status in (0)")->order("name asc")->select();
        return $res;
    }
}
