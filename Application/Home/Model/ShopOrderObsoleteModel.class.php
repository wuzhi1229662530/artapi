<?php
namespace Home\Model;
use Home\Model\MyModel;
class ShopOrderObsoleteModel extends MyModel
{
    protected $tableName = 'shop_order_obsolete';

    public function getCount($user_id, $w_str) {
        $res = $this->field("count(order_id) as count")
                 ->where('user_id = %d and obsolete_time > date_sub(NOW(), INTERVAL '.$w_str.')', array($user_id) )
                 ->select();
        return $res;
    }
}