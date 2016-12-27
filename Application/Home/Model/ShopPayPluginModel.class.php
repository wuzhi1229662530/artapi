<?php
namespace Home\Model;
use Home\Model\MyModel;
class ShopPayPluginModel extends MyModel
{
    protected $tableName = 'shop_pay_plugin';

    public function getPlugin() {
        $res = $this->field("id, ".$this->getCardLogo('logo', 'bank_logo'))
             ->where("id in (1,2,3,4)")
             ->select();
        return $res;
    }
}