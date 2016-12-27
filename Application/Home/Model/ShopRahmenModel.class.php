<?php
namespace Home\Model;
use Home\Model\MyModel;
class ShopRahmenModel extends MyModel
{
    protected $tableName = 'shop_rahmen';

    public function getRahmenById($rahmen_id) {
        if ( empty($rahmen_id) ){
            return null;
        }
        $res = $this->field("id,r_weight as weight, r_size as rahmen_size,  r_width as rahmen_width,inventory,   name, r_price as price, ".$this->getImgUrl('r_img_path').", ".$this->getImgUrl('top_left_img', "top_left_img").", ".$this->getImgUrl('top_right_img', "top_right_img").", ".$this->getImgUrl('bottom_left_img','bottom_left_img').", ".$this->getImgUrl('bottom_right_img','bottom_right_img').", ".$this->getImgUrl('border_top_img','border_top_img').", ".$this->getImgUrl('border_bottom_img','border_bottom_img').", ".$this->getImgUrl('border_right_img','border_right_img').", ".$this->getImgUrl('border_left_img','border_left_img'))
                    ->where(" delete_status in (0) and id = %d", array($rahmen_id))
                    ->find();
        return $res;
        
    }

    public function getRahmenByIds($rahmen_id) {
        if ( empty($rahmen_id) ){
            return null;
        }
        $res = $this->field("id,r_weight as weight,  r_size as rahmen_size,  r_width as rahmen_width, inventory,     name,r_price as price,    ".$this->getImgUrl('r_img_path').", ".$this->getImgUrl('top_left_img', "top_left_img").", ".$this->getImgUrl('top_right_img', "top_right_img").", ".$this->getImgUrl('bottom_left_img','bottom_left_img').", ".$this->getImgUrl('bottom_right_img','bottom_right_img').", ".$this->getImgUrl('border_top_img','border_top_img').", ".$this->getImgUrl('border_bottom_img','border_bottom_img').", ".$this->getImgUrl('border_right_img','border_right_img').", ".$this->getImgUrl('border_left_img','border_left_img'))
                    ->where(" delete_status in (0) and id in (%s)", $rahmen_id)
                    ->select();
        return $res;
        
    }
}
