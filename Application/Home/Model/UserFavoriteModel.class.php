<?php
namespace Home\Model;
use Home\Model\MyModel;
class UserFavoriteModel extends MyModel
{
    protected $tableName = 'user_favourit';
/**
* 取用户收藏
*/
    public function getFavoriteList($id) {
        $res = $this->field("id, goods_id, frame_id")
                    ->where('user_id = '.$id)
                    ->select();

        $goods = D("BaseGoods");
        $rahmen = D("ShopRahmen");


        // $goods_res = $goods->getShopGoods(0,0, " and ab_base_goods.id in (select f.goods_id from ab_user_favourit f where f.user_id = ".$id.")");
        // $rahmen_res = $rahmen->getRahmenByIds("select f.frame_id from ab_user_favourit f where f.user_id = ".$id);
        
        $arr = array();
        foreach ( $res as $k => $v ) {
            $goods_res = $goods->getShopGoods(0,1, " and ab_base_goods.id = ".$v['goods_id']);
            if ( empty($goods_res) ){
                continue;
            }
            $arr[$k]['id'] = $res[$k]['id'];
            $arr[$k]['goods'] = $goods_res[0];
            $arr[$k]['rahmen'] = $rahmen->getRahmenById($v['frame_id']);
        }
        return array_values($arr);
    }

    public function _getFavoriteList($user_id) {
        $sql = "select
                    ".$this->getImgUrl('g.cover_path', "img_url").",
                    ".$this->getImgUrl('r.r_img_path', "rahmen_img_url").",
                    ".$this->getImgUrl('r.top_left_img', "top_left_img").", ".$this->getImgUrl('r.top_right_img', "top_right_img").", ".$this->getImgUrl('r.bottom_left_img','bottom_left_img').", ".$this->getImgUrl('r.bottom_right_img','bottom_right_img').", ".$this->getImgUrl('r.border_top_img','border_top_img').", ".$this->getImgUrl('r.border_bottom_img','border_bottom_img').", ".$this->getImgUrl('r.border_right_img','border_right_img').", ".$this->getImgUrl('r.border_left_img','border_left_img').",
                    r.r_weight as rahmen_weight,
                    r.inventory as rahmen_inventory,
                    e.id,
                    r.name as rahmen_name,
                    r.r_price as price,
                    r.r_width as rahmen_width,
                    e.goods_id, 
                    e.frame_id as rahmen_id,
                    e.backColor,
                    e.frame_price as rahmen_price,
                    e.colorName,
                    (SELECT
                        fs.setting
                    FROM
                        ab_shop_free_shipping fs
                    WHERE
                        fs.delete_status in (0)
                        AND fs.startTime < now()
                        AND fs.endTime > now()
                        AND (fs.type in (0) OR (select id from ab_shop_free_shipping_goods b where b.freeId=fs.id and b.goodsId=e.goods_id )) ORDER BY fs.id ASC limit 1
                    ) as freeshipmoney,
                    g.title as name, 
                    g.id as goods_id,
                    g.inventory,
                    g.weight,
                    ag.type,
                    g.recommended,
                    CONCAT('ShopGoodsID_',  g.id) as jump_url,
                    g.sell_price,
                    CONCAT(round(g.longth),'×', round(g.width) ) as size,
                    a.name as authorname,
                    (select d.dictname from sys_diction d where d.id = g.medium) as medium
                from ab_user_favourit e 
                    left join ab_base_goods g on e.goods_id = g.id
                    left join ab_base_author_goods ag on g.id = ag.goods
                    left join ab_base_author a on ag.author = a.id
                    left join ab_shop_rahmen r on e.frame_id = r.id and r.delete_status in (0)
                where e.user_id = %d 
                and g.is_online in (1)
                and g.inventory in (1)
                and g.delete_status in (0)
                and ag.delete_status in (0)
                and a.name is not null
                and ag.type in (1)
                
                ";

        $res = $this->query($sql, $user_id);
        foreach ( $res as $k => $v ) {

            $ens_arr[$k]['id'] = $v['id'];
            $ens_arr[$k]['goods_id'] = $v['goods_id'];
            $ens_arr[$k]['rahmen_id'] = $v['rahmen_id'];
            $ens_arr[$k]['goods']['img_url'] = $v['img_url'];
            $ens_arr[$k]['goods']['id'] = $v['goods_id'];
            $ens_arr[$k]['goods']['inventory'] = $v['inventory'];
            $ens_arr[$k]['goods']['weight'] = $v['weight'];
            $ens_arr[$k]['goods']['recommended'] = $v['recommended'];
            $ens_arr[$k]['goods']['jump_url'] = $v['jump_url'];
            $ens_arr[$k]['goods']['name'] = $v['name'];
            $ens_arr[$k]['goods']['sell_price'] = sprintf("%.2f",$v['sell_price']);
            $ens_arr[$k]['goods']['size'] = $v['size'];
            $ens_arr[$k]['goods']['authorname'] = $v['authorname'];
            $ens_arr[$k]['goods']['medium'] = $v['medium'];
            $ens_arr[$k]['rahmen'] = null;
            if ( !empty($v['rahmen_id']) ) {
                $ens_arr[$k]['rahmen']['id'] = $v['rahmen_id'];
                $ens_arr[$k]['rahmen']['weight'] = $v['rahmen_weight'];
                $ens_arr[$k]['rahmen']['inventory'] = $v['rahmen_inventory'];
                $colorname = empty($v['colorname']) ? '' : ';'.$v['colorname'].' Matboard';
                $ens_arr[$k]['rahmen']['name'] = $v['rahmen_name'].$colorname;
                $ens_arr[$k]['rahmen']['backcolor'] = $v['backcolor'];
                $ens_arr[$k]['rahmen']['colorname'] = $v['colorname'];
                $ens_arr[$k]['rahmen']['rahmen_price'] = $v['rahmen_price'];
                $ens_arr[$k]['rahmen']['width'] = $v['rahmen_width'];
                $ens_arr[$k]['rahmen']['price'] = sprintf("%.2f",$v['price']);
                $ens_arr[$k]['rahmen']['img_url'] = $v['rahmen_img_url'];
                $ens_arr[$k]['rahmen']['top_left_img'] = $v['top_left_img'];
                $ens_arr[$k]['rahmen']['top_right_img'] = $v['top_right_img'];
                $ens_arr[$k]['rahmen']['bottom_left_img'] = $v['bottom_left_img'];
                $ens_arr[$k]['rahmen']['bottom_right_img'] = $v['bottom_right_img'];
                $ens_arr[$k]['rahmen']['border_top_img'] = $v['border_top_img'];
                $ens_arr[$k]['rahmen']['border_bottom_img'] = $v['border_bottom_img'];
                $ens_arr[$k]['rahmen']['border_right_img'] = $v['border_right_img'];
                $ens_arr[$k]['rahmen']['border_left_img'] = $v['border_left_img'];

            }
            $ens_arr[$k]['goods']['isfree'] = null;
            if ( !empty($v['freeshipmoney']) && ($v['sell_price'] + $rahmen_price) >= $v['freeshipmoney'] ) {
                $ens_arr[$k]['goods']['isfree'] = 1;
            }
        }
         

        return $ens_arr;
    }
}