<?php
namespace Home\Model;
use Home\Model\MyModel;
class ShopIntegralModel extends MyModel
{
    protected $tableName = 'shop_integral';

    /**
     * 获取个人积分
     */
    public function getIntegral($where_str , $where_arr ) {
        $tn = $this->getTableName();
        //CASE WHEN `in_integral` is not null and `mode` is null THEN 1 WHEN `in_integral` is null and `mode` is not null THEN 2 END inttegral_type, 
        $res = $this->field("id, source, (select o.order_no from ab_shop_order o where id = ".$tn.".orderid) as order_no, in_integral, out_integral, mode,DATE_FORMAT(ab_shop_integral.create_time,'%m/%d/%Y %H:%i:%s') as create_time ")
                    ->where("delete_status in (0)".$where_str, $where_arr)
                    ->order("create_time desc")
                    ->select();
        return $res;
    }
}
