<?php
namespace Home\Model;
use Home\Model\MyModel;
class ShopFreeShippingModel extends MyModel
{
    protected $tableName = 'shop_free_shipping';

    /**
     * 获取包邮活动
     */
    public function getFreeShipping( $goods_id ) {
        $sql = "SELECT
                    a.setting freeshipmoney
                FROM
                    ab_shop_free_shipping a
                WHERE
                    a.delete_status in (0)
                    AND a.startTime < now()
                    AND a.endTime > now()
                    AND (a.type in (0) OR (select id from ab_shop_free_shipping_goods b where b.freeId=a.id and b.goodsId= %d))
                ORDER BY a.id ASC
                limit 1";
        $res = $this->query($sql, $goods_id);
        if ( empty($res) ) {
            return null;
        }
        return $res[0];
    }
}
