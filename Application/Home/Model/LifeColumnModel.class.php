<?php
namespace Home\Model;
use Home\Model\MyModel;
class LifeColumnModel extends MyModel
{
    protected $tableName = 'life_column';

    //life站侧滑目录 取到二级目录
        /******
    *   @sort
        1 => HOME
        2 => TRENDING NEWS
        3 => EVENTS AND EXHIBITIONS
        4 => KNOWLEDGE
        5 => LIFESTYLE
    *******/
    public function getLifeColumn($pid = 0, $lev = 0) {


        static $res = array();
        if ( empty($res) ) {
           $res = $this->field($this->getTableName().'.column_parent, '.$this->getTableName().'.sort,'.$this->getTableName().'.id,'.$this->getTableName().'.column_name as help_name,REPLACE(`'.$this->getTableName().'`.`column_url`, "/", "_") as column_url ' )->where(' is_Enable in (0) and delete_status in (0)')->order('sort asc')->select();
        }
        $tree = array();
        if ( $lev < 1) {
            foreach ( $res as $k => $v ) {
                if ( $v['column_parent'] == $pid ) {
                    $v['lev'] = $lev;
                    $v['son'] = $this->getLifeColumn($v['id'], $lev+1);
                    $tree[] = $v;
                }
            }
        }
        return $tree;
    }

}

