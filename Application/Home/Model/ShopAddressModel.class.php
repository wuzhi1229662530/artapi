<?php
namespace Home\Model;
use Home\Model\MyModel;
class ShopAddressModel extends MyModel
{
    protected $tableName = 'shop_address';

    public function getAddress($where) {
        $t_n = $this->getTableName();
        $res = $this->field("ab_shop_address.id,ab_shop_address.ext,ab_shop_address.email,ab_shop_address.is_default,ab_shop_address.phone,ab_shop_address.postcode,ab_shop_address.addr, ab_shop_address.address, ab_shop_address.firstname,ab_shop_address.lastname, ab_shop_address.area,ab_shop_address.city, ab_shop_address.province, ab_shop_address.user_id, ab_shop_address.accept_name as addr_username, (select a.name from ab_base_area as a where a.id = ab_shop_address.province) as state_name,ab_shop_address.mobile,  (select a.simple_code from ab_base_area as a where a.id = ab_shop_address.province) as state,CONCAT(addr,' ', address,' ', city,' ', (select a.name from ab_base_area as a where a.id = ab_shop_address.province) ) as real_address ")
                    ->where(" ab_shop_address.delete_status in (0) and ab_shop_address.adr_type in (0) ".$where)
                    ->select();
        return $res;
    }


    //生成订单时候的收货地址
    //取默认收货地址
    //没有默认取第一条
    public function getAddrOrder($where) {

        $default_res = $this->getAddrOne($where." and is_default in (1)");

        if ( $default_res ) {
            return $default_res;
        }
        $res = $this->getAddrOne($where);
        return $res;
    }

    public function getAddrOne($where){
        $t_n = $this->getTableName();
        $res = $this->field("ab_shop_address.id,ab_shop_address.ext,ab_shop_address.email,ab_shop_address.is_default,ab_shop_address.phone,ab_shop_address.postcode,ab_shop_address.addr, ab_shop_address.address, ab_shop_address.firstname,ab_shop_address.lastname, ab_shop_address.area,ab_shop_address.city, ab_shop_address.province, ab_shop_address.user_id, ab_shop_address.accept_name as addr_username, (select a.name from ab_base_area as a where a.id = ab_shop_address.province) as state_name,ab_shop_address.mobile,  (select a.simple_code from ab_base_area as a where a.id = ab_shop_address.province) as state,CONCAT(addr,' ', address,' ', city,' ', (select a.name from ab_base_area as a where a.id = ab_shop_address.province) ) as real_address ")
                    ->where(" ab_shop_address.delete_status in (0) and ab_shop_address.adr_type in (0) ".$where)
                    ->find();
        return $res;
    }
}
