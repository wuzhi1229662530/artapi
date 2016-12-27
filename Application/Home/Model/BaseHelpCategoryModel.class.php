<?php
namespace Home\Model;
use Home\Model\MyModel;
class BaseHelpCategoryModel extends MyModel
{
    protected $tableName = 'base_help_category';

    //大首页 shop站首页   侧滑目录


    public function getTree( $pid = '', $lev = 0){
        static $data = array();
        if( empty( $data ) ) {
            $data = $this->field('id, CONCAT("HelpCateID_",id) as help_url,  name  as help_name, parent_id, url')->where('delete_status in (0)')->select();
        }
        $tree = array();
        if ( $lev < 2 ) {
            foreach ( $data as $k => $v ) {
                if ( $v['parent_id'] == $pid ) {
                    $v['lev'] = $lev;
                    $v['son'] = $this->getTree($v['id'], $lev+1);
                    $tree[] = $v;
                }
            }
        }
        return $tree;
    }
}


