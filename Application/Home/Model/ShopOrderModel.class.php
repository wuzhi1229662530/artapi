<?php
namespace Home\Model;
use Home\Model\MyModel;
class ShopOrderModel extends MyModel
{
    protected $tableName = 'shop_order';

    //订单列表 接口 接受参数 跟 返回参数 判断订单 状态
    
    const ORDER_STATUS_NO_PAY           = 1;            //未支付    Non-Payment
    const ORDER_STATUS_CHECKING         = 2;            //已支付审核中   Processing
    const ORDER_STATUS_SHIPPING         = 3;            //发货中   In Transit  TRACK PACKAGE
    const ORDER_STATUS_COMPLETE         = 4;            //已签收   Delivered
    const ORDER_STATUS_CANCLE           = 5;            //订单取消    Order Canceled
    const ORDER_STATUS_CKFAILURE        = 6;            //审核不通过   Checked Failure
    const ORDER_STATUS_PAY_CKFAILURE    = 7;            //支付后 被取消订单Order Canceled
    const ORDER_STATUS_RETURNING        = 8;            //退货中Returns Processing
    const ORDER_STATUS_RETURNFAILURE    = 9;            //退货成功 Returns Completed
    const ORDER_STATUS_NOSHIPPING       = 10;            //未发货 Preparing
    const ORDER_STATUS_CURRENT          = 100;          //当前订单 包括(1,2,3)
    const ORDER_STATUS_ALL              = 101;          //所有订单


    //支付方式
    const PAY_YUE = 1;    // 余额支付
    const PAY_CREDIT_CARD   = 2;   //信用卡支付

    //订单来源 web 手机 安卓 IOS
    const ORDER_WEB = 1;   //web
    const ORDER_ANDROID = 2;    //android
    const ORDER_IOS  = 3;       //IOS
    const ORDER_SHOUJI = 4;     //手机端

    //数据库判断订单状态 表参数  
    //外围状态记录(0：审核通过；1：未审核；2：当前订单已取消;3:审核未通过；4：已签收完成)等
    const W_PAY = 0; // 未支付
    const A_PAY = 1; // 已支付审核中
    // const A_SAVE = 40; // 订单完成
    // const A_RETURN = 50; // 退货中
    // const S_RETURN = 60; // 退货成功
    // const F_RETURN = 70; // 退货失败
    const A_CANCEL = 2; // 订单已取消
    const ORDER_NOT_PASS = 3;   //审核未通过

    const A_COMPLETE = 4;  //订单签收完成
    public function getOrder($where_str, $where_arr) {
        $t_n = $this->getTableName();
        $order_goods = D("ShopOrderGoods");
        $goods = D("BaseGoods");
        $rahmen = D("ShopRahmen");
        $r_n = $rahmen->getTableName();
        $g_n = $goods->getTableName();
        $o_n = $order_goods->getTableName();
        $res = $this->field($t_n.".id as order_id,og.isfree,ab_shop_order.update_time,ab_shop_order.voucher,CONCAT( FROM_UNIXTIME( UNIX_TIMESTAMP(DATE_ADD(ab_shop_order.create_time, INTERVAL 5 day)), '%M %e, %Y'), ' - ' ,FROM_UNIXTIME( UNIX_TIMESTAMP(DATE_ADD(ab_shop_order.create_time,INTERVAL 10 day)), '%M %e, %Y')) as estimated_delivery,ab_shop_order.use_account_amount,ab_shop_order.ups_service_code,ab_shop_order.order_source,og.inline_id,".$g_n.".pro_no, og.colorName , og.shipNumber, e.ext,".$g_n.".longth as goods_longth,".$g_n.".g_integral, ".$g_n.".width as goods_width,og.backColor,og.rahmen_price,og.backSize,og.frame_count,og.frame_in_count,og.inline_id, r.r_size as rahmen_size, r.r_width as rahmen_width, (select ".$this->getCardLogo('p.logo', 'bank_logo')." from ab_shop_pay_plugin p where p.id = ab_shop_order.payment) as peyment_name, ab_shop_order.payable_freight ,e.billing_id,".$t_n.".create_time,CONCAT(round(ab_base_goods.longth),'×', round(ab_base_goods.width) ) as size,  e.addr,   e.address,   e.city,    (select ar.name from ab_base_area as ar where ar.id = e.province) as state_name,(select ar.simple_code from ab_base_area as ar where ar.id = e.province) as state, CONCAT( e.addr, ' ', e.address, ' ', e.city, ' ', (select ar.name from ab_base_area as ar where ar.id = e.province) ) as real_address ,e.zip,e.mobile, e.accept_name, e.phone,  ".$t_n.".status,".$t_n.".pay_status,".$t_n.".order_amount,".$t_n.".order_no, e.address_id, e.ups_no,".$t_n.".payable_amount, ".$t_n.".taxes, ab_base_author.name as authorname,".$this->getImgUrl("r.r_img_path", "rahmen_img_url").", ".$this->getImgUrl($g_n.".cover_path", "goods_img_url").", ".$g_n.".weight as goods_weight, ".$g_n.".sell_price as goods_price,(select d.dictname from sys_diction d where d.id = ab_base_goods.medium) as medium,".$g_n.".title as goods_name,r.name as rahmen_name, r.r_weight as rahmen_weight, og.id as order_goods_id, og.goods_id, og.rahmen_id, ".$this->getImgUrl('r.top_left_img', "top_left_img").", ".$this->getImgUrl('r.top_right_img', "top_right_img").", ".$this->getImgUrl('r.bottom_left_img','bottom_left_img').", ".$this->getImgUrl('r.bottom_right_img','bottom_right_img').", ".$this->getImgUrl('r.border_top_img','border_top_img').", ".$this->getImgUrl('r.border_bottom_img','border_bottom_img').", ".$this->getImgUrl('r.border_right_img','border_right_img').", ".$this->getImgUrl('r.border_left_img','border_left_img') )
                    ->join($o_n." og on ".$t_n.".id = og.order_id")
                    ->join($g_n."  on  ".$g_n.".id = og.goods_id")
                    ->join(" left join ".$r_n." r on r.id = og.rahmen_id and r.delete_status in (0)")
                    ->join(" ab_base_author_goods  on ".$g_n.".id = ab_base_author_goods.goods and ab_base_author_goods.delete_status in (0) and ab_base_author_goods.type in (1) ")
                    ->join(" left join ab_base_author on ab_base_author_goods.author = ab_base_author.id and ab_base_author.delete_status in (0)")
                    ->join(" left join ab_shop_order_express e on ".$t_n.".id = e.order_id and e.delete_status in (0)")
                    ->where($t_n.".delete_status in (0) ".$where_str, $where_arr)
                    ->order("ab_shop_order.update_time desc")
                    ->select();
        return $res;
    }

    public function getOrderByPay($where_str, $where_arr) {
        $t_n = $this->getTableName();
        $order_goods = D("ShopOrderGoods");
        $goods = D("BaseGoods");
        $rahmen = D("ShopRahmen");
        $r_n = $rahmen->getTableName();
        $g_n = $goods->getTableName();
        $o_n = $order_goods->getTableName();
        $res = $this->field($t_n.".id as order_id,ab_shop_order.update_time,og.id as ogid,(SELECT fs.setting FROM ab_shop_free_shipping fs  WHERE fs.delete_status in (0)  AND fs.startTime < now() AND fs.endTime > now() AND (fs.type in (0) OR (select id from ab_shop_free_shipping_goods b where b.freeId=fs.id and b.goodsId= ".$g_n.".id )) ORDER BY fs.id ASC limit 1 ) as freeshipmoney,og.isfree,ab_shop_order.voucher,CONCAT( FROM_UNIXTIME( UNIX_TIMESTAMP(DATE_ADD(ab_shop_order.create_time, INTERVAL 5 day)), '%M %e, %Y'), ' - ' ,FROM_UNIXTIME( UNIX_TIMESTAMP(DATE_ADD(ab_shop_order.create_time,INTERVAL 10 day)), '%M %e, %Y')) as estimated_delivery,ab_shop_order.use_account_amount,ab_shop_order.ups_service_code,ab_shop_order.order_source,og.inline_id,".$g_n.".pro_no, og.colorName , og.shipNumber, e.ext,".$g_n.".longth as goods_longth,".$g_n.".g_integral, ".$g_n.".width as goods_width,og.backColor,og.rahmen_price,og.backSize,og.frame_count,og.frame_in_count,og.inline_id, r.r_size as rahmen_size, r.r_width as rahmen_width, (select ".$this->getCardLogo('p.logo', 'bank_logo')." from ab_shop_pay_plugin p where p.id = ab_shop_order.payment) as peyment_name, ab_shop_order.payable_freight ,e.billing_id,".$t_n.".create_time,CONCAT(round(ab_base_goods.longth),'×', round(ab_base_goods.width) ) as size,  e.addr,   e.address,   e.city,    (select ar.name from ab_base_area as ar where ar.id = e.province) as state_name,(select ar.simple_code from ab_base_area as ar where ar.id = e.province) as state, CONCAT( e.addr, ' ', e.address, ' ', e.city, ' ', (select ar.name from ab_base_area as ar where ar.id = e.province) ) as real_address ,e.zip,e.mobile, e.accept_name, e.phone,  ".$t_n.".status,".$t_n.".pay_status,".$t_n.".order_amount,".$t_n.".order_no, e.address_id, e.ups_no,".$t_n.".payable_amount, ".$t_n.".taxes, ab_base_author.name as authorname,".$this->getImgUrl("r.r_img_path", "rahmen_img_url").", ".$this->getImgUrl($g_n.".cover_path", "goods_img_url").", ".$g_n.".weight as goods_weight, ".$g_n.".sell_price as goods_price,(select d.dictname from sys_diction d where d.id = ab_base_goods.medium) as medium,".$g_n.".title as goods_name,r.name as rahmen_name, r.r_weight as rahmen_weight, og.id as order_goods_id, og.goods_id, og.rahmen_id, ".$this->getImgUrl('r.top_left_img', "top_left_img").", ".$this->getImgUrl('r.top_right_img', "top_right_img").", ".$this->getImgUrl('r.bottom_left_img','bottom_left_img').", ".$this->getImgUrl('r.bottom_right_img','bottom_right_img').", ".$this->getImgUrl('r.border_top_img','border_top_img').", ".$this->getImgUrl('r.border_bottom_img','border_bottom_img').", ".$this->getImgUrl('r.border_right_img','border_right_img').", ".$this->getImgUrl('r.border_left_img','border_left_img') )
                    ->join($o_n." og on ".$t_n.".id = og.order_id")
                    ->join($g_n."  on  ".$g_n.".id = og.goods_id")
                    ->join(" left join ".$r_n." r on r.id = og.rahmen_id and r.delete_status in (0)")
                    ->join(" ab_base_author_goods  on ".$g_n.".id = ab_base_author_goods.goods and ab_base_author_goods.delete_status in (0) and ab_base_author_goods.type in (1) ")
                    ->join(" left join ab_base_author on ab_base_author_goods.author = ab_base_author.id and ab_base_author.delete_status in (0)")
                    ->join(" left join ab_shop_order_express e on ".$t_n.".id = e.order_id and e.delete_status in (0)")
                    ->where($t_n.".delete_status in (0) ".$where_str, $where_arr)
                    ->order("ab_shop_order.update_time desc")
                    ->select();
        return $res;
    }

    public function getOrderLimit($where_str, $where_arr, $page, $count) {
        $t_n = $this->getTableName();
        $order_goods = D("ShopOrderGoods");
        $goods = D("BaseGoods");
        $rahmen = D("ShopRahmen");
        $r_n = $rahmen->getTableName();
        $g_n = $goods->getTableName();
        $o_n = $order_goods->getTableName();
        $offset = ($page - 1) * $count;
        $res = $this->field($t_n.".id as order_id,ab_shop_order.update_time,e.delivery_status,ab_shop_order.voucher,CONCAT( FROM_UNIXTIME( UNIX_TIMESTAMP(DATE_ADD(ab_shop_order.create_time, INTERVAL 5 day)), '%M %e, %Y'), ' - ' ,FROM_UNIXTIME( UNIX_TIMESTAMP(DATE_ADD(ab_shop_order.create_time,INTERVAL 10 day)), '%M %e, %Y')) as estimated_delivery,ab_shop_order.use_account_amount,ab_shop_order.ups_service_code,ab_shop_order.order_source,og.inline_id,".$g_n.".pro_no, og.colorName , og.shipNumber, e.ext,".$g_n.".longth as goods_longth,".$g_n.".g_integral, ".$g_n.".width as goods_width,og.backColor,og.rahmen_price,og.backSize,og.frame_count,og.frame_in_count,og.inline_id, r.r_size as rahmen_size, r.r_width as rahmen_width, (select ".$this->getCardLogo('p.logo', 'bank_logo')." from ab_shop_pay_plugin p where p.id = ab_shop_order.payment) as peyment_name, ab_shop_order.payable_freight ,e.billing_id,".$t_n.".create_time,CONCAT(round(ab_base_goods.longth),'×', round(ab_base_goods.width) ) as size,  e.addr,   e.address,   e.city,    (select ar.name from ab_base_area as ar where ar.id = e.province) as state_name,(select ar.simple_code from ab_base_area as ar where ar.id = e.province) as state, CONCAT( e.addr, ' ', e.address, ' ', e.city, ' ', (select ar.name from ab_base_area as ar where ar.id = e.province) ) as real_address ,e.zip,e.mobile, e.accept_name, e.phone,  ".$t_n.".status,".$t_n.".pay_status,".$t_n.".order_amount,".$t_n.".order_no, e.address_id, e.ups_no,".$t_n.".payable_amount, ".$t_n.".taxes, ab_base_author.name as authorname,".$this->getImgUrl("r.r_img_path", "rahmen_img_url").", ".$this->getImgUrl($g_n.".cover_path", "goods_img_url").", ".$g_n.".weight as goods_weight, ".$g_n.".sell_price as goods_price,(select d.dictname from sys_diction d where d.id = ab_base_goods.medium) as medium,".$g_n.".title as goods_name,r.name as rahmen_name, r.r_weight as rahmen_weight, og.id as order_goods_id, og.goods_id, og.rahmen_id, ".$this->getImgUrl('r.top_left_img', "top_left_img").", ".$this->getImgUrl('r.top_right_img', "top_right_img").", ".$this->getImgUrl('r.bottom_left_img','bottom_left_img').", ".$this->getImgUrl('r.bottom_right_img','bottom_right_img').", ".$this->getImgUrl('r.border_top_img','border_top_img').", ".$this->getImgUrl('r.border_bottom_img','border_bottom_img').", ".$this->getImgUrl('r.border_right_img','border_right_img').", ".$this->getImgUrl('r.border_left_img','border_left_img') )
                    ->join($o_n." og on ".$t_n.".id = og.order_id")
                    ->join($g_n."  on  ".$g_n.".id = og.goods_id")
                    ->join(" left join ".$r_n." r on r.id = og.rahmen_id and r.delete_status in (0)")
                    ->join(" ab_base_author_goods  on ".$g_n.".id = ab_base_author_goods.goods and ab_base_author_goods.delete_status in (0) and ab_base_author_goods.type in (1) ")
                    ->join(" left join ab_base_author on ab_base_author_goods.author = ab_base_author.id and ab_base_author.delete_status in (0)")
                    ->join(" left join ab_shop_order_express e on ".$t_n.".id = e.order_id and e.delete_status in (0)")
                    ->where($t_n.".delete_status in (0) ".$where_str, $where_arr)
                    ->order("ab_shop_order.update_time desc")
                    ->limit((int)$offset.','.(int)$count)
                    ->select();
        return $res;
    }


    public static function getOrderStatus() {
        return array(self::ORDER_STATUS_NO_PAY, self::ORDER_STATUS_CHECKING, self::ORDER_STATUS_SHIPPING, self::ORDER_STATUS_COMPLETE, self::ORDER_STATUS_CANCLE, self::ORDER_STATUS_CKFAILURE, self::ORDER_STATUS_PAY_CKFAILURE, self::ORDER_STATUS_RETURNING, self::ORDER_STATUS_RETURNFAILURE,self::ORDER_STATUS_CURRENT, self::ORDER_STATUS_ALL);
    }

    public function getCardType($card_num) {
        $plugin = D("ShopPayPlugin");

        if ( preg_match("/^4[0-9]{12}(?:[0-9]{3})?$/", $card_num) ) {
            $res = $plugin->field(" id as payment ,name as bankname")->where('name = "Visa"')->limit(0,1)->select();
        } else if ( preg_match("/^5[1-5][0-9]{14}$/", $card_num) ) {
            $res = $plugin->field(" id as payment ,name as bankname")->where('name = "Master Card"')->limit(0,1)->select();
        } else if ( preg_match("/^3[47][0-9]{13}$/", $card_num) ) {
            $res = $plugin->field(" id as payment ,name as bankname")->where('name = "American Express"')->limit(0,1)->select();
        } else if ( preg_match("/^6(?:011|5[0-9]{2})[0-9]{12}$/", $card_num) ) {
            $res = $plugin->field(" id as payment ,name as bankname")->where('name = "Discover"')->limit(0,1)->select();
        }else if ( preg_match("/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/", $card_num) ) {
            return null;
            $res = $plugin->field(" id as payment ,name as bankname")->where('name = "Diners Club"')->limit(0,1)->select();
        }else if ( preg_match("/^(?:2131|1800|35\d{3})\d{11}$/", $card_num) ) {
            return null;
            $res = $plugin->field(" id as payment ,name as bankname")->where('name = "JCB"')->limit(0,1)->select();
        } else {
            return null;
            $res = $plugin->field(" id as payment ,name as bankname")->where('name = "Other"')->limit(0,1)->select();
        }
        return $res;
    }
 
    public function getOrderInfo( $user_id, $order_id){
        $sql = "SELECT * from ab_shop_order
                    WHERE  user_id = %d and id = %d";
        return $this->query($sql,[$user_id,$order_id] );
    }

}