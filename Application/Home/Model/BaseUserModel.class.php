<?php
namespace Home\Model;
use Home\Model\MyModel;
use Home\Model\BaseSessionModel;
class BaseUserModel extends MyModel
{
    protected $tableName = 'base_user';

    //返回用户信息
    public function getUserInfo($where_str, $where_arr) {
        $res = $this->field("ab_base_user.id as user_id, ab_base_user.*, (select count(f.id) from ab_user_favourit f where f.user_id = ab_base_user.id) as countfavorite, (select count(e.id) from ab_shop_enshrine e where e.user = ab_base_user.id) as countshopcart")->where("ab_base_user.delete_status in (0) and ab_base_user.type in (0) and ".$where_str, $where_arr)->select();
        return $res;
    }

    public function getLoginedMember($param){
        return $this->field(true)->alias('u')
                    ->join(BaseSessionModel::getTableName().' s on s.user_id = u.id ')
                    ->where($param)
                    ->find();
    }



    public function _getUserInfo($email) {
        $sql  = "select n.ext, n.id, n.username,n.email,n.mobile,n.integral,n.user_status,n.userpsd,n.is_able ,n.id as user_id,
                (select count(id) from ab_user_favourit where user_id  = n.id and goods_id in (select id from ab_base_goods g where g.is_online in (1) and g.delete_status in (0) and g.inventory in (1)) ) countfavorite
                ,(select count(id) from ab_shop_enshrine where user  = n.id  and goods in (select id from ab_base_goods g where g.is_online in (1) and g.delete_status in (0) )) countshopcart
                
                from ab_base_user n
                where n.delete_status in (0) and n.type in (0) and  n.email = '%s'";
        $res  = $this->query($sql,$email);
        return $res;
    }

    //主要用于侧滑信息获取，因为获取频繁，数据取的比较少，后期再想办法维护
    public function updateUserInfo($email, $user_id) {
        $sql  = "select n.username,
                (select count(id) from ab_user_favourit where user_id =n.id and goods_id in (select id from ab_base_goods g where g.is_online in (1) and g.delete_status in (0) and g.inventory in (1)) ) countfavorite
                ,(select count(id) from ab_shop_enshrine where user = n.id  and goods in (select id from ab_base_goods g where g.is_online in (1) and g.delete_status in (0)  )) countshopcart
                from ab_base_user n
                where  n.delete_status in (0) and n.type in (0) and n.user_status in (1) and is_able in (0) and n.email = '%s' and n.id = %d";
        $res  = $this->query($sql, $email, $user_id);
        return $res;
    }

    /**
     * 取用户所有积分
     */
    public function getUserIntegral($user_id) {
        $sql  = "select n.integral
                from ab_base_user n
                where  n.delete_status in (0) and n.type in (0) and n.id = %d";
        $res  = $this->query($sql,$user_id);
        return $res;
    }
}