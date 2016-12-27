<?php
namespace Home\Model;
use Home\Model\MyModel;
class ShopEnshrineModel extends MyModel
{
    protected $tableName = 'shop_enshrine';
    //通过user_id取购物车列表
    /**
     * 参数 user_id, $ens_id(购物车id)
     */
    public function getShopCartList($user_id, $ens_id) {
        if ( $ens_id && is_array($ens_id) ) {
            $where['id'] = array("in", implode(',', $ens_id));
        }
        $where['user'] = $user_id;
        $res = $this->field("id, goods, frame")->where($where)->select();
        $arr = array();
        if ( $res ) {
            $goods = D("BaseGoods");
            $rahmen = D("ShopRahmen");
            foreach ( $res as $k => $v ) {
                $goods_res = $goods->getShopGoods(0, 1, " and ab_base_goods.id = ".$v['goods'], 0);
                $rahmen_res = $rahmen->getRahmenById($v['frame']);
                $arr[$k]['id'] = $v['id'];
                $arr[$k]['goods_id'] = $v['goods'];
                $arr[$k]['rahmen_id']  = $v['frame'];
                $arr[$k]['goods'] = $goods_res[0];
                $arr[$k]['rahmen'] = $rahmen_res;
            }
        }
        return $arr;
    }
    public function _getShopCartList($user_id, $ens_id) {
        $a   = $ens_id && is_array($ens_id) ? "e.id in (".implode(',', $ens_id).") and " : "";
        $sql = "select
                    ".$this->getImgUrl('g.cover_path', "img_url").",
                    ".$this->getImgUrl('r.r_img_path', "rahmen_img_url").",
                    ".$this->getImgUrl('r.top_left_img', "top_left_img").", ".$this->getImgUrl('r.top_right_img', "top_right_img").", ".$this->getImgUrl('r.bottom_left_img','bottom_left_img').", ".$this->getImgUrl('r.bottom_right_img','bottom_right_img').", ".$this->getImgUrl('r.border_top_img','border_top_img').", ".$this->getImgUrl('r.border_bottom_img','border_bottom_img').", ".$this->getImgUrl('r.border_right_img','border_right_img').", ".$this->getImgUrl('r.border_left_img','border_left_img').",
                    r.r_weight as rahmen_weight,
                    r.inventory as rahmen_inventory,
                    e.id,
                    r.name as rahmen_name,
                    (SELECT
                        fs.setting
                    FROM
                        ab_shop_free_shipping fs
                    WHERE
                        fs.delete_status in (0)
                        AND fs.startTime < now()
                        AND fs.endTime > now()
                        AND (fs.type in (0) OR (select id from ab_shop_free_shipping_goods b where b.freeId=fs.id and b.goodsId=e.goods )) ORDER BY fs.id ASC limit 1
                    ) as freeshipmoney,
                    r.r_size as rahmen_size,
                    r.r_width as rahmen_width,
                    e.goods, 
                    e.backColor,
                     e.colorName   ,
                    e.backSize,
                    e.frame as rahmen_id, 
                    e.frame_price as price,
                    e.frame_count,
                    e.frame_in_count,
                    e.inline_id,
                    g.title as name, 
                    g.id as goods_id,
                    g.inventory,
                    g.weight,
                    ag.type,
                    g.recommended,
                    CONCAT('ShopGoodsID_',  g.id) as jump_url,
                    g.sell_price,
                    g.longth as goods_longth,
                    g.width as goods_width,
                    CONCAT(round(g.longth),'×', round(g.width) ) as size,
                    a.name as authorname,
                    (select r.inventory from ab_shop_rahmen r where e.inline_id = r.id ) inline_inventory,
                    (select d.dictname from sys_diction d where d.id = g.medium) as medium
                from ab_shop_enshrine e 
                    left join ab_base_goods g on e.goods = g.id
                    left join ab_base_author_goods ag on g.id = ag.goods
                    left join ab_base_author a on ag.author = a.id
                    left join ab_shop_rahmen r on e.frame = r.id and r.delete_status in (0)
                where ".$a." 
                  user = %d 
                and g.is_online in (1) 
                and g.delete_status in (0)
                and ag.delete_status in (0)
                and a.name is not null
                and ag.type in (1)
                order  by e.create_time
                ";

        return $this->query($sql, $user_id);
    
 
    }

    //返回用户购物车的数量        
    // 目前逻辑 每个用户不能超过40条
    public function countShopCart($user_id) {
        $res = $this->field("count(id) as count")->where("user = %d", $user_id)->select();
        return $res[0]['count'];
    }

    public function _countShopCart($user_id) {
        $sql = "select 
                count(e.id) as countshopcart 
                from ab_shop_enshrine e
                    inner join ab_base_goods g  on g.id = e.goods
                where 
                g.delete_status in (0)
                and g.is_online in (1)
                and g.inventory in (1)
                and e.user = ".$user_id;
        $res = $this->query($sql);
        return $res[0]['countshopcart'];
    }

}
