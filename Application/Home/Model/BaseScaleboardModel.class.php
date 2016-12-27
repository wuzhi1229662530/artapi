<?php
namespace Home\Model;
use Home\Model\MyModel;
class BaseScaleboardModel extends MyModel
{
    protected $tableName = 'base_scaleboard';
    public function getColorBoard($str,$goods_ids) {
        $color_b = D("BaseColorboard");
        $res = $this->field('cb.id,cb.colorName,cb.colorCode')
                       ->join(" left join ab_base_colorboard cb on cb.id = ab_base_scaleboard.colorboard and cb.delete_status in (0)")
                       ->where(" ab_base_scaleboard.delete_status in (0) ".$str, $goods_ids)
                       ->select();
        return $res;
    }
}