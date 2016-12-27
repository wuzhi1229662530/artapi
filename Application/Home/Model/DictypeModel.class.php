<?php
namespace Home\Model;
use Home\Model\MyModel;

class DictypeModel extends MyModel
{
    protected $tableName = 'dictype';
    protected $tablePrefix = 'sys_';


    public function getFilter($medium = []) {
        $dic = new DictionModel();
        $tion = $dic->getFilterTion();
        $type = [['id'=>63,"typename"=>"THEME"],['id'=>64,"typename"=>"FORMAT"],['id'=>65,"typename"=>"MEDIUM"]];
        foreach ( $type as $k1 => $v1) {
            foreach ( $tion as $k2 => $v2 ) {
                if ( $v2['typeid'] == $v1['id']) {
                    $type[$k1]['subdata'][] = $tion[$k2];
                }
            }
        }

        $goods = new BaseGoodsModel();
        $where_str = "";
        if ( $medium ) {
            $where_str = " and medium in (".implode(",", $medium).")";
        }
        $price = $goods->field("CEILING(max(sell_price)) as max_sell_price, FLOOR(min(sell_price)) as min_sell_price")->where("is_online in (1) and delete_status in (0) and inventory in (1)   ".$where_str)->find();

        $type[] = array('id'=>"", "typename"=>"PRICE", "subdata"=>array( array( "typeid"=> $price['min_sell_price']), array( "typeid"=>$price['max_sell_price'] ) ) );
        return $type;
    }
}
