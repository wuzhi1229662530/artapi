<?php
namespace Home\Model;
use Home\Model\MyModel;
class ShopVoucherRuleModel extends MyModel
{
    protected $tableName = 'shop_voucher_rule';

    /**
     * 获取个人积分
     */
    public function getVoucherRule($where_str, $where_arr ) {
        $res = $this->field("id, title, random_num, start_time, end_time, (select t.money from ab_shop_voucher_template t where t.id = ab_shop_voucher_rule.templateid) as money, create_time, redemption_time, full_use_of, integral_exchange_rate, duration_day")
                    ->where("delete_status in (0)  ".$where_str, $where_arr)
                    ->select();
        //echo $this->getLastSql();exit();
        return $res;
    }
}
