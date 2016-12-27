<?php
namespace Home\Model;
use Home\Model\MyModel;
class DictionModel extends MyModel
{
    protected $tableName = 'diction';
    protected $tablePrefix = 'sys_';

    public function getFilterTion($w_str = "", $w_arr = array()) {
        $dic_n = $this->getTableName();
        $res = $this->field($dic_n.".id, ".$dic_n.".dictname, ".$dic_n.".typeid ")
                    ->where($dic_n.".isdisabled in (0) and ".$dic_n.".delete_status in (0) ".$w_str, $w_arr)
                    ->select();
        return $res;
    }

    public function getTopCate(){
        $sql = "SELECT ucase(m.dictname) as theme_name,
                   m.id as theme,
                   ".self::getImgUrl('m.pic_path', 'img_url')."
                FROM sys_diction m where
                m.delete_status = 0
                and m.typeid = 63
                limit 0,12";
        return $this->query($sql);
    }
}
