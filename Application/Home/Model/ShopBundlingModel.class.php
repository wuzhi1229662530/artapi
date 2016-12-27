<?php
namespace Home\Model;
use Home\Model\MyModel;
class ShopBundlingModel extends MyModel
{
    protected $tableName = 'shop_bundling';
    public function getRahmen($where) {
        $t_n = $this->getTableName();
        $rahmen = D("ShopRahmen");
        $rah_t_n = $rahmen->getTableName();
        $res = $this->field($rah_t_n.".id, ".$rah_t_n.".name as name,".$rah_t_n.".r_price as sell_price, ".$this->getImgUrl($rah_t_n.'.r_img_path').", ".$rah_t_n.".r_size as size, ".$rah_t_n.".r_width as width, ".$rah_t_n.".r_thickness as thickness, ".$rah_t_n.".r_material as material, ".$rah_t_n.".r_style as style, ".$this->getImgUrl('top_left_img',"top_left_img" ).", ".$this->getImgUrl('top_right_img','top_right_img').", ".$this->getImgUrl('bottom_left_img','bottom_left_img').", ".$this->getImgUrl('bottom_right_img','bottom_right_img').", ".$this->getImgUrl('border_top_img','border_top_img').", ".$this->getImgUrl('border_bottom_img','border_bottom_img').", ".$this->getImgUrl('border_right_img','border_right_img').", ".$this->getImgUrl('border_left_img','border_left_img')) 
                    ->join($rah_t_n." ON ".$t_n.".rahmen_id = ".$rah_t_n.".id")
                    ->where($t_n.".delete_status in (0) and ".$rah_t_n.".delete_status in (0) ".$where)
                    ->select();
        return $res;
    }

    public function getInline($code = "") {
        $code = empty($code) ? C("INLINE_SIZE_L").",".C("INLINE_SIZE_S").','.C("INLINE_SIZE_M") : $code;
        $sql = "SELECT
                    r.id,
                    r.code,
                    r.inventory,
                    r.unit_price,
                    r.length,
                    r.auxiliary_price
                FROM
                    ab_shop_rahmen r
                WHERE r.delete_status in (0)
                    AND r.type = 2
                    AND r.code in (".$code.")";
        $res = $this->query($sql);
        return $res;
    }
    /**
     * color 判断该商品是否有村办，如果没有村办这需要计算内线条价格
     */
    public function _getRahmen($goods_id,$param) {
 
             $sql = "SELECT
                        r.id,
                        r.name AS name,
                        r.r_price AS sell_price,
                        r.r_size AS size,
                        r.r_thickness AS thickness,
                        r.r_style AS style,
                        r.r_material AS material,
                        r.r_width AS width,
                        r.inventory,
                        r.unit_price,
                        r.length,
                        r.auxiliary_price,
                        r.operation_price,
                        r.auxiliary_price2,
                        ".$this->getImgUrl('r.r_img_path').",".$this->getImgUrl('r.top_left_img',"top_left_img" ).", ".$this->getImgUrl('r.top_right_img','top_right_img').", ".$this->getImgUrl('r.bottom_left_img','bottom_left_img').", ".$this->getImgUrl('r.bottom_right_img','bottom_right_img').", ".$this->getImgUrl('r.border_top_img','border_top_img').", ".$this->getImgUrl('r.border_bottom_img','border_bottom_img').", ".$this->getImgUrl('r.border_right_img','border_right_img').", ".$this->getImgUrl('r.border_left_img','border_left_img')."
                    FROM
                        ab_shop_bundling b
                    inner JOIN ab_shop_rahmen r ON b.rahmen_id = r.id AND r.delete_status in (0) AND r.type in (1)
                    WHERE b.delete_status in (0)
                        AND b.goods_id = %d";
                $res = $this->query($sql, $goods_id);
        return $res;
    }
    /**
     * 如果商品强制购买相框，但是却没有相关联的相框默认取最新3条相框
     * color 判断该商品是否有村办，如果没有村办这需要计算内线条价格
     */
    public function _getRahmenByTime() {

             $sql = "SELECT
                        r.id,
                        r.r_color AS name,
                        r.r_price AS sell_price,
                        r.r_size AS size,
                        r.r_thickness AS thickness,
                        r.r_style AS style,
                        r.r_material AS material,
                        r.r_width AS width,
                        r.inventory,
                        r.unit_price,
                        r.length,
                        r.auxiliary_price,
                        r.operation_price,
                        r.auxiliary_price2,
                        ".$this->getImgUrl('r.r_img_path').",".$this->getImgUrl('r.top_left_img',"top_left_img" ).", ".$this->getImgUrl('r.top_right_img','top_right_img').", ".$this->getImgUrl('r.bottom_left_img','bottom_left_img').", ".$this->getImgUrl('r.bottom_right_img','bottom_right_img').", ".$this->getImgUrl('r.border_top_img','border_top_img').", ".$this->getImgUrl('r.border_bottom_img','border_bottom_img').", ".$this->getImgUrl('r.border_right_img','border_right_img').", ".$this->getImgUrl('r.border_left_img','border_left_img')."
                    FROM
                            ab_shop_rahmen r
                    WHERE r.delete_status in (0)
                    AND r.type IN (1)
                ORDER BY r.create_time desc
                LIMIT 0,3";

        $res = $this->query($sql);
        return $res;
    }
    public function getRahmenById($rahmen_id) {

             $sql = "SELECT  
                        r.r_width as width,
                        r.inventory,
                        r.unit_price,
                        r.length,
                        r.auxiliary_price,
                        r.operation_price,
                        r.auxiliary_price2
                    FROM
                        ab_shop_rahmen r
                    WHERE r.id = %d AND r.delete_status IN (0)";

        $res = $this->query($sql, $rahmen_id);
        return $res[0];
    }
}
