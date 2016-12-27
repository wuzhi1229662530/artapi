<?php
namespace Home\Model;
use Home\Model\MyModel;
class ShopOrderGoodsModel extends MyModel
{
    protected $tableName = 'shop_order_goods';


    public function getOrderGoods( $order_id, $user_id){

        return $this->field(    (int)$user_id." user,   
                                rahmen_id frame,
                                goods_id goods,
                                backColor backColor,
                                backSize backSize,
                                colorName colorName,
                                rahmen_price frame_price,
                                frame_count frame_count,
                                frame_in_count frame_in_count,
                                inline_id inline_id,
                                DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s') create_time,
                                isfree isfree")
                    ->where( 'order_id = '.(int)$order_id)
                    ->select();
 
    }
}