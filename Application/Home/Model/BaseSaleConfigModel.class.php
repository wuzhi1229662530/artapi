<?php
namespace Home\Model;
use Home\Model\MyModel;
class BaseSaleConfigModel extends MyModel
{
    protected $tableName = 'base_sale_config';
    const config_code = "tjgn";
    public function getSaleCfg(){
        $sql = 'SELECT `config_flag` FROM `ab_base_sale_config` WHERE ( config_code = "'.self::config_code.'" ) LIMIT 1';
        $res = $this->query($sql);
        return $res[0];
    }
}