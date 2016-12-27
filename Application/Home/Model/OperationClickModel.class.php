<?php
namespace Home\Model;
use Home\Model\MyModel;
class OperationClickModel extends MyModel
{
    protected $tableName = 'operation_click';
    protected $tablePrefix = '';

    public function getInfo($ip, $user_id) {
        $where1 = empty($ip) ? " " : " oc.ip = '".$ip."' ";
        $where2 = empty($user_id) || empty($ip) ? "" : " or ";
        $where3 = empty($user_id) ? "" : " oc.oper_user = ".$user_id ;
        $where4 = empty($user_id) ? "" :  " and auf.user_id = ".$user_id ;
        $where5 = empty($user_id) ? "" :  " and aso.user_id = ".$user_id ;
        $sql = "select sd.id,IFNULL(sum(a.themeNum),0) as themeNum from (
        select sd.id,count(sd.id) as themeNum from operation_click oc 
                    left join ab_base_goods abg on oc.oper=abg.id left join sys_diction sd on sd.id=abg.theme 
                    where sd.id is not null and(  
                    ".$where1.$where2.$where3." 
                      )
                    group by sd.id
        union all
        select sd.id,count(sd.id) as themeNum from ab_user_favourit auf 
                left join ab_base_goods abg on auf.goods_id=abg.id left join sys_diction sd on sd.id=abg.theme 
                where sd.id is not null 
                ".$where4."  
                group by sd.id 
        union all
        select sd.id,count(sd.id) as themeNum from ab_shop_order_goods asog 
            left join ab_shop_order aso on aso.id=asog.order_id 
            left join ab_base_goods abg on asog.goods_id=abg.id left join sys_diction sd on sd.id=abg.theme 
            where sd.id is not null 
            ".$where5."
            group by sd.id ) a right join sys_diction sd on a.id=sd.id group by a.id";
        $res = $this->query($sql);
        return $res;
    }

    public function getGoods($theme,$limit, $lastResIds) {
        $where1 = empty($theme) ? "" : " and t.theme = '".$theme."' ";
        $where2 = empty($lastResIds) ? "" : " and t.id not in (".implode(',',  $lastResIds)." ) "; 
        $sql = " SELECT t.id,
            ".$this->getImgUrl('t.cover_path').",
            concat('ShopGoodsID_',t.id) jump_url,
            concat(round(t.longth), '×', round(t.width)) size,
            t.sell_price,
            d.name authorname,
            t.recommended,
            t.title name
            FROM ab_base_goods t left join ab_base_author_goods a on t.id=a.goods and a.delete_status in (0)
        LEFT JOIN ab_base_author d ON a.author = d.id AND d.delete_status in (0)
        where t.is_online in (1) and   t.inventory in (1) and a.type in (1) and a.author is not null and t.delete_status  in (0)
            ".$where1.$where2."
        and t.id >= (SELECT floor(RAND() * (SELECT MAX(t.id) FROM ab_base_goods t 
        left join ab_base_author_goods a on t.id=a.goods and a.delete_status in (0) 
                LEFT JOIN ab_base_author d ON a.author = d.id AND d.delete_status in (0) 
                where t.is_online in (1) and  t.inventory in (1) and a.type in (1)  and a.author is not null and t.delete_status in (0)
            ".$where1.$where2."
        )))  
        ORDER BY t.id LIMIT 
       ".$limit;
       $res = $this->query($sql);
       return $res;
    }

}
