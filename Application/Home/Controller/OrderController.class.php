<?php
namespace Home\Controller;
use Home\Controller\MyController;
use Home\Model\ShopOrderModel;
class OrderController extends MyController {
    public function _empty() {
        $this->errRender();
    }

    /**
     *创建购物车
     *@param  token,email, user_id
     *@param enshrine_id (购物车id集合[17,18,19])
     *@param  order_source  订单来源 1web  2android 3ios 4shoujiweb
     */
    public function createOrder() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            if (count($getPost) != 5 || !isset($getPost['order_source']) || empty($getPost['order_source']) || !in_array( $getPost['order_source'], array(2,3) ) ||  !isset( $getPost['token'] ) || empty( $getPost['token'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] ) || !is_array( $getPost['enshrine_id'] ) || !isset( $getPost['enshrine_id'] ) || empty( $getPost['enshrine_id'] ) || !isset( $getPost['user_id'] ) || empty( $getPost['user_id'] ) ) {
                $this->_render(self::Err_No_Data);
            }
 
            $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id']);

 
            $addr = D("ShopAddress");
            $addr_where = " and  user_id = ".$getPost['user_id'];
            $addr_list =$addr->getAddrOrder($addr_where);
            //没填收货地址，或者根据id取收货地址为空
            //现在 更改为  没有收货地址 也能创建订单
            //再次改为每有货地址不让创建订单 
            if ( empty($addr_list) ) {
                $this->_render(self::Err_No_Shipping_Address);
            }
            $enshrine = D("ShopEnshrine");
            //取订单对应的商品

            $ens_list = $enshrine->_getShopCartList($getPost['user_id'],$getPost['enshrine_id']);

            // 商品为空
            if ( empty($ens_list) ) {
                $this->_render(self::Err_Goods_No_online,'','Shopping Cart Add Failed,This product may have been purchased others.');
            }
            // //判断订单中中是否有已卖出，已下架的商品或者相框
            $error1["enshrine_id"] = [];
            $error1["enshrine_id_rahmen"] = [];
            $goods_amount = $rahmen_amount = $order_amount = $goods_weight = $rahmen_weight = $order_weight = 0;
            $ship_method = C('UPSMETHOD');
            $ship_service_code = C("UPSCODE");
            $ship_max_method = C('UPSMAXMETHOD');
            $ship_max_service_code = C("UPSMAXCODE");
            $feightship = false;
            $isfree = true;
            $rahmen_ids = [];
            $_sql = "";
            $arr2 = $ids = [];
            $rahmen_price = 0;
            $order_goods = D("ShopOrderGoods");
            foreach ( $ens_list as $k => $v ) {
                //需要处理  json 返回形式
                if (  $v['inventory'] == 0 ) {
                    $error1["enshrine_id"][] = $v['id'];
                } else if ( ( !empty($v['rahmen_id']) &&  $v['rahmen_inventory'] < $v['frame_count']  ) || ( !empty($v['inline_id']) &&  $v['inline_inventory'] < $v['frame_in_count']  ) ) {
                    $error2['enshrine_id_rahmen'][] = $v['id'];
                } else {
                    $order_amount =  $v['sell_price']  +  $v['price']  + $order_amount ;
                    $order_weight = $v['weight'] + $v['rahmen_weight'];
                    $order_weight = empty($order_weight) ? 1 : $order_weight;
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
                    $ens_arr[$k]['goods']['backsize'] = $v['backsize'];
                    $ens_arr[$k]['goods']['backcolor'] = $v['backcolor'];
                    $ens_arr[$k]['goods']['colorname'] = $v['colorname'];
                    $g_width =   $v['goods_width'] ;
                    $g_longth =  $v['goods_longth'] ;
                    $ens_arr[$k]['rahmen'] = "";
                    if ( !empty($v['rahmen_id']) ) {
                        $ens_arr[$k]['rahmen']['id'] = $v['rahmen_id'];
                        $ens_arr[$k]['rahmen']['weight'] = $v['rahmen_weight'];
                        $ens_arr[$k]['rahmen']['inventory'] = $v['rahmen_inventory'];
                        $colorname = empty($v['colorname']) ? '' : ';'.$v['colorname'].' Matboard';
                        $ens_arr[$k]['rahmen']['name'] = $v['rahmen_name'].$colorname;
                        $ens_arr[$k]['rahmen']['price'] = sprintf("%.2f",$v['price']);
                        $ens_arr[$k]['rahmen']['img_url'] = $v['rahmen_img_url'];
                        $ens_arr[$k]['rahmen']['rahmen_size']   = $v['rahmen_size'];
                        $ens_arr[$k]['rahmen']['rahmen_width'] = $v['rahmen_width'];
                        $ens_arr[$k]['rahmen']['top_left_img'] = $v['top_left_img'];
                        $ens_arr[$k]['rahmen']['top_right_img'] = $v['top_right_img'];
                        $ens_arr[$k]['rahmen']['bottom_left_img'] = $v['bottom_left_img'];
                        $ens_arr[$k]['rahmen']['bottom_right_img'] = $v['bottom_right_img'];
                        $ens_arr[$k]['rahmen']['border_top_img'] = $v['border_top_img'];
                        $ens_arr[$k]['rahmen']['border_bottom_img'] = $v['border_bottom_img'];
                        $ens_arr[$k]['rahmen']['border_right_img'] = $v['border_right_img'];
                        $ens_arr[$k]['rahmen']['border_left_img'] = $v['border_left_img'];
                        $ens_arr[$k]['rahmen']['frame_count'] = $v['frame_count'];
                        $ens_arr[$k]['rahmen']['frame_in_count'] = $v['frame_in_count'];
                        $ens_arr[$k]['rahmen']['inline_id'] = $v['inline_id'];
                        if ( isset( $rahmen_ids[$v['rahmen_id']] ) ) {
                            $rahmen_ids[$v['rahmen_id']]['frame_count'] = $rahmen_ids[$v['rahmen_id']]['frame_count'] + $v['frame_count'];
                        } else {
                            $rahmen_ids[$v['rahmen_id']]['rahmen_inventory'] = $v['rahmen_inventory'];
                            $rahmen_ids[$v['rahmen_id']]['frame_count'] = $v['frame_count'];
                            $ids[] = $v['rahmen_id'];
                        }
                        $r_width = getInt($v["rahmen_width"]);
                        if ( !empty($v['inline_id']) ) {
                            if ( isset( $rahmen_ids[$v['inline_id']] ) ) {
                                $rahmen_ids[$v['inline_id']]['frame_count'] = $rahmen_ids[$v['inline_id']]['frame_count'] + $v['frame_in_count'];
                            } else {
                                $rahmen_ids[$v['inline_id']]['rahmen_inventory'] = $v['inline_inventory'];
                                $rahmen_ids[$v['inline_id']]['frame_count'] = $v['frame_in_count'];
                                $ids[] = $v['inline_id'];
                            }
                            if ( $g_width + $g_longth <= 36 ) {
                                $j = 2;
                            } else if ( 36 < ($g_width + $g_longth) && ($g_width + $g_longth) < 60 ) {
                                $j = 3;
                            } else if ( $g_width + $g_longth >= 60 ) {
                                $j = 4;
                            }
                        }
                        $g_width += $r_width * 2 + $j * 2;
                        $g_longth += $r_width * 2 + $j * 2;
                        $goods_size = getGoodsShipSizeWithRahmen($g_longth, $g_width);
                    } else {
                        $goods_size = getGoodsShipSize($g_longth, $g_width);
                    }
                    $ens_arr[$k]["goods"]['weight'] = $order_weight;
                    $ens_arr[$k]["goods"]['width'] = $goods_size['goods_width'];
                    $ens_arr[$k]["goods"]['length'] = $goods_size['goods_longth'];
                    $ens_arr[$k]["goods"]['height'] = 5.12;
                    $ens_arr[$k]["goods"]['box'] = $goods_size['box'];
                    $bag =  getGoodsShipBags($v['goods_longth'],$v['goods_width']);
                    $ens_arr[$k]['goods']['bag'] = $bag['bag'];
                    //判断商品尺幅是否 
                    
                    if ( !empty($v['freeshipmoney']) && ($v['sell_price'] + $rahmen_price) >= $v['freeshipmoney'] ) {
                        $ens_arr[$k]["goods"]['isfree'] = 1;
                        
                    } else {
                        $isfree = false;
                        $ens_arr[$k]["goods"]['isfree'] = 0;
                        if ( $goods_size['goods_longth'] + 2 * ($goods_size['goods_width'] + 5.12) >= C("UPSMAXSIZE") ) {
                            $money4 += $this->getFeightShipMoney($addr_list, 1, $goods_size['goods_longth'],$goods_size['goods_width'], $v['sell_price']+$v['price']);
                            $feightship = true;
                        } else {
                            $money1 +=  $this->getShipMoney($addr_list['postcode'], 1, $ship_service_code[0], $goods_size['goods_longth'],$goods_size['goods_width']);
                            $money2 +=  $this->getShipMoney($addr_list['postcode'], 1, $ship_service_code[1], $goods_size['goods_longth'],$goods_size['goods_width']);
                            $money3 +=  $this->getShipMoney($addr_list['postcode'], 1, $ship_service_code[2], $goods_size['goods_longth'],$goods_size['goods_width']);
                        }
                    }
                }
            }
            foreach ( $rahmen_ids as $key1 => $val1 ) {
                if ( $val1['rahmen_inventory'] < (int)$val1['frame_count'] ) {
                    $this->_render(self::Err_Rahmen_Over);
                }
                //$_sql .= " when ".$key1." and inventory - ".(int)$val1['frame_count']." >= 0 then inventory - ".(int)$val1['frame_count'];
            }
            if ( $money1 )  {
                $money[0]['ship_method'] = $ship_method[0];
                $money[0]['service_code'] = $ship_service_code[0];
                $money[0]['money'] =  sprintf("%.2f", $money1);
            }  

            if ( $money2 ) {
                $money[1]['ship_method'] = $ship_method[1];
                $money[1]['service_code'] = $ship_service_code[1];
                $money[1]['money'] =  sprintf("%.2f", $money2);
            }

            if ( $money3 ) {
                $money[2]['ship_method'] = $ship_method[2];
                $money[2]['service_code'] = $ship_service_code[2];
                $money[2]['money'] = sprintf("%.2f", $money3);
            }

            if ( $money4 ) {
                $feightmoney[0]['ship_method'] = $ship_max_method[0];
                $feightmoney[0]['service_code'] = $ship_max_service_code[0];
                $feightmoney[0]['money'] =  sprintf("%.2f", $money4);
            }

            if ( $isfree == true ) {
                $money[0]['ship_method'] = "3-5 business days";
                $money[0]['service_code'] = "03";
                $money[0]['money'] =  "free";
            }

            $ens_list = $ens_arr;
            //如果有已卖出，已下架的商品或者相框，放回对应的购物车id 
            if ( count( $error1['enshrine_id'] ) > 0  ) {
                $this->_render(self::Err_ShopCart_Wrong,$error1,"", true);
            }
            if ( count( $error2['enshrine_id_rahmen'] ) > 0  ) {
                $this->_render(self::Err_Rahmen_Over,$error2,"", true);
            }
            //商品重量为0 不允许下订单
            //if ( count( $error2['enshrine_id'] ) > 0  ) {
                //$this->_render(self::Err_Goods_No_Weight,$error2,"", true);
            //}
            $res['address']  = null;
             
          
            //返回邮费
            $res['shipmoney'] = array_values($money);
            $res['feightshipmoney'] = $feightmoney;
            $res['goods'] = $ens_list;
  
             $taxes = $this->getTaxAvc($addr_list['postcode']);
 
 
 
            $res['address']  = $addr_list;
            $res['billing'] = $addr_list;
            $res['taxex'] = sprintf("%.2f",$order_amount * $taxes );

            $mic_time = explode ( " ", microtime () ); 
            $mic_time = $mic_time [1] . ($mic_time [0] * 1000); 
            $mic_time = explode ( ".", $mic_time );
            $order_no = date("YmdHis").substr($mic_time[0], strlen($mic_time[0])-3,3);
            $t_nowtime = time();
            $now_time = date("Y-m-d H:i:s", $t_nowtime);
            $order_data["user_id"] = $getPost['user_id'];
            $order_data["order_no"] = $order_no;    //订单号
            $order_data["payment"] = 0;                          //付款方式  -1余额
            $order_data["pay_status"] = 0;                        //支付状态（0：未支付；1：已支付）
            $order_data["payable_amount"] = sprintf("%.2f",$order_amount);                //应付金额(订单中商品总额) 
            $order_data["adjust_taxes"] = $taxes ;  //商品税率
            $order_data["taxes"] = $res['taxex'];  //商品税费

            $order_data["is_invoice"] = 0;                       //是否需要发票（0：不需要；1：需要）
            $order_data["order_amount"] = sprintf("%.2f",$order_amount  + $res['taxex'] );
            $order_data["status"] = 1;                           //外围状态记录(0：审核通过；1：未审核；2：当前订单已取消;3:审核未通过；4：已签收完成)等
            $order_data["delete_status"] = 0;
            $order_data["create_time"] = $now_time;
            $order_data["update_time"] = $now_time;
            $order_data["rahmen_price"] = 0;                     //画框总价格
            $order_data['order_source'] = $getPost['order_source'];   //订单来源 安卓 还是IOS
            
            $order = D("ShopOrder");
            
            $order_goods->startTrans();
            $res1 = $order->data($order_data)->filter('strip_tags')->add();
            $order_res['id'] = $res1;
            $order_res['order_no'] = $order_no;

            //0 可订单可延时 1不能延时 
            $order_res['create_time'] = $t_nowtime;
            $order_res['update_time'] = $t_nowtime;
            $order_res['now_time'] = $t_nowtime;
            $order_res['expire_time'] = strtotime("+30minutes", $t_nowtime);

            foreach ( $ens_list as $k => $v ) {
                $goods_data[$k]["order_goods_no"] = $order_no;
                $goods_data[$k]["order_id"] = $order_res['id'];
                $goods_data[$k]["goods_id"] = $v['goods_id'];

                
                $goods_data[$k]["rahmen_id"] = $v['rahmen_id'];
                $goods_data[$k]["rahmen_price"] = sprintf("%.2f",$v['rahmen']['price']);
                $goods_data[$k]['frame_count'] = $v['rahmen']['frame_count'];
                $goods_data[$k]['frame_in_count'] = $v['rahmen']['frame_in_count'];
                $goods_data[$k]['inline_id'] = $v['rahmen']['inline_id'];
                

                $goods_data[$k]['backSize'] = $v['goods']['backsize'];
                $goods_data[$k]['weight'] = $v["goods"]['weight'];
                $goods_data[$k]['width'] = $v["goods"]['width'];
                $goods_data[$k]['length'] = $v["goods"]['length'];
                $goods_data[$k]['height'] = $v["goods"]['height'];
                $goods_data[$k]['box'] = $v["goods"]['box'];
                $goods_data[$k]['bags'] = "Bag".$v["goods"]['bag'];
                $goods_data[$k]['backColor'] = $v['goods']['backcolor'];
                $goods_data[$k]['colorName'] = $v['goods']['colorname'];
             

                $goods_data[$k]["goods_price"] = sprintf("%.2f",$v['goods']["sell_price"]);
                $goods_data[$k]['is_postage']  = 0;
                $goods_data[$k]["delete_status"] = 0;
                $goods_data[$k]["create_time"] = $now_time;
                if ( $feightship ) {
                    $goods_data[$k]["ups_service_code"] = "308";
                } else if ( $v['goods']['isfree'] == 1 ) {
                    $goods_data[$k]["ups_service_code"] = "03";
                }
                
                $goods_data[$k]["isfree"] = $v['goods']['isfree'];
                $goodsids[] = $v['goods_id'];
               
            }

            $res6 = true;
            if ( $addr_list ) {
                $order_express = D("ShopOrderExpress");
                $order_express_data["order_id"] = $order_res['id'];
                $order_express_data["accept_name"] = $addr_list['addr_username'];
                $order_express_data["phone"] = $addr_list['phone'];
                $order_express_data["mobile"] = $addr_list['mobile'];
                $order_express_data["province"] = $addr_list['province'];
                $order_express_data["city"] = $addr_list['city'];
                $order_express_data["billing_id"] = $addr_list['id'];
                $order_express_data["address"] = $addr_list['address'];
                $order_express_data["addr"] = $addr_list['addr'];
                $order_express_data["zip"] = $addr_list['postcode'];
                $order_express_data["address_id"] = $addr_list['id'];
                $order_express_data["goods_invoice"] = 0;
                $order_express_data["delete_status"] = 0;
                $order_express_data["create_time"] = $now_time;
                //插入 order_express
                $res6 = $order_express->data($order_express_data)->filter('strip_tags')->add();
            }
            //在插入到order_goods表
            $res2 = $order_goods->addAll($goods_data);
            $where['id'] = array("in", implode(',', $getPost['enshrine_id']) );
            $where['user'] = $getPost['user_id'];
            //订单成功删除对应购物车
            $res3 = $enshrine->where($where)->delete();
            $goods = D("BaseGoods");
            $rahmen = D("ShopRahmen");
            //商品order_goods  插入成功后 对应商品库存改为0  和相框 库存 
 
            $res4 = $goods->execute("update ab_base_goods set inventory = 0 where inventory > 0 and  id in (".implode(",", $goodsids).")");
            $res5 = true;
            if ( $ids ) {
                foreach ( $rahmen_ids as $k2 => $v2 ) {
                    $update_num = $rahmen->execute("update ab_shop_rahmen set inventory = inventory - ".$v2['frame_count']." where inventory - ".$v2['frame_count']." >= 0  and id in (".$k2.") ");
                    //echo $rahmen->getLastSql();
                    if ( $update_num === false || $update_num == 0) {
                        $res5 = false;
                        break;
                    }
                    
                }
               //$res5 = $rahmen->execute("update ab_shop_rahmen set inventory = case id ".$_sql." end where id in (".implode(",",$ids).") ");
                
            }
            if ($res1 && $res2 && $res3  ) {
                if (   $res4 === false  || $res4 < count($goodsids) ) {
                    $order_goods->rollback();
                    $this->_render(self::Err_ShopCart_Wrong, array('enshrine_id'=>null),"",true );
                } else if ( $res5 === false ) {
                    $order_goods->rollback();
                    $this->_render(self::Err_Rahmen_Over);
                    
                } else {
                    $order_goods->commit();
                    $res['order'] = $order_res;
                    $this->_render(self::Err_Suc, $res);
                }

            } else {
                $order_goods->rollback();
                $this->_render(self::Err_Ordering_Failure);
            }
        } catch(\Think\Exception $e) {
            $order_goods->rollback();
            $this->_render(self::Err_Ordering_Failure);
        }
        
    }

    /**
     * 支付前判断 收货地址是否为空
     */
    public  function ckOrderAddress() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            if ( !isset( $getPost['address_id'] ) || empty( $getPost['address_id'] ) || !isset( $getPost['order_id'] ) || empty( $getPost['order_id'] ) || !isset( $getPost['token'] ) || empty( $getPost['token'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] )   || !isset( $getPost['user_id'] ) || empty( $getPost['user_id'] ) ) {
                $this->_render(self::Err_No_Data);
            }
            $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id']);
            $addr_e = D("ShopOrderExpress");
            $addr_where = " and id = ".$getPost['address_id']." and order_id = ".$getPost['order_id'];
            $addr_list =$addr_e->field("id")->where("delete_status in (0) and address_id = %d and order_id = %d", array($getPost['address_id'], $getPost['order_id']))->find();

            if ( count($addr_list) < 1 ) {
                $this->_render(self::Err_No_Shipping_Address);
            }
            $plugin = D('ShopPayPlugin');
            $res['bank_name'] = $plugin->getPlugin();
            //优惠券
            // $voucher = D("ShopVoucher");
            // $res['voucher']['not_used'] = $voucher->getNotUsedVoucher($getPost['user_id']);

            $this->_render(self::Err_Suc, $res);
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    
    }
    /**
     *订单支付
     *@param order_id 订单id 
     *@param address_id 收货地址id
     *@param ship_method 发货方式 
     *@param pay_way  支付方式 1 为余额 2为信用卡 当为pay_way == 1的时候参数要有 
     *@param   pay_way == 2         card_num 信用卡号
     *@param                        security_code  信用卡 安全码
     *@param                        expiration_date  信用卡过期时间 格式 为 mmYY 4位数字
     */
    public function payOrder() {
        try {
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);

            $ship_service_code = C("UPSCODE");
            if ( !isset($getPost['pay_way']) || empty($getPost['pay_way'])  || !isset( $getPost['ship_method'] ) || !isset( $getPost['address_id'] ) || empty( $getPost['address_id'] ) ||  !isset( $getPost['token'] ) || empty( $getPost['token'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] ) || !isset( $getPost['order_id'] ) || empty( $getPost['order_id'] ) || !isset( $getPost['user_id'] ) || empty( $getPost['user_id'] ) ) {
                $this->_render(self::Err_No_Data);
            }
            //目前去掉余额支付，指定为信用卡支付
            $getPost['pay_way'] = ShopOrderModel::PAY_CREDIT_CARD;
            $user_info = $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id'], true);
            $order = D("ShopOrder");
            $addr = D("ShopAddress");
            $t_n = $order->getTableName();
            
            $where_str = "  and ab_base_author_goods.type = 1  and ".$t_n.".user_id = %d and ".$t_n.".id = %d ";
            $where_arr = array($getPost['user_id'], $getPost['order_id']);
            $order_info = $order->getOrderByPay($where_str, $where_arr);

            if ( empty( $order_info ) ) {
                $this->_render(self::Err_No_Data);
            }

          
            if ( $order_info[0]['status']  == 2 ) {
                $this->_render(self::Err_More_15_Minutes);
            }
            if ( $order_info[0]['pay_status']  == 1 ) {
                $this->_render(self::Err_Order_Paid);
            }
            $addr = D("ShopAddress");
            $order_status_model = D("ShopOrderStatus");
            $order_status = $order_status_model->field("id,order_id,status")->where("order_id = %d", $getPost['order_id'])->find();
            
            if ( $order_status ) {
                if ( $order_status['status'] > -1 ) {
                    $this->_render(self::Err_Order_Paid);
                } else {
                    $order_status_data2['status'] = 0;
                    $order_status_model->where('order_id = %d', $getPost['order_id'])->filter('strip_tags')->save($order_status_data2);
                }
            } else {
                $order_status_data['order_id'] = $getPost['order_id'];
                $order_status_data['status'] = 0;
                $order_status_data['time'] = date("Y-m-d H:i:s");
                $order_status_model->data($order_status_data)->filter('strip_tags')->add();
            }

            if ( empty( $order_info[0]['address_id'] ) ) {
                $order_status_model->where('order_id = %d', $getPost['order_id'])->delete();
                $this->_render(self::Err_No_Shipping_Address);
            }
            $order_weight = $g_integral = 0;
            //取商品加相框的重量
            $num = count($order_info);
            $str2 = "";
            $i = 0;


            $shipAddress['id'] = $order_info[0]['address_id'];
            $shipAddress['real_address'] = $order_info[0]['real_address'];
            $shipAddress['ext'] = $order_info[0]['ext'];
            $shipAddress['mobile'] = $order_info[0]['mobile'];
            $shipAddress['addr_username'] = $order_info[0]['accept_name'];
            $shipAddress['addr'] = $order_info[0]['addr'];
            $shipAddress['address'] = $order_info[0]['address'];
            $shipAddress['city'] = $order_info[0]['city'];
            $shipAddress['state'] = $order_info[0]['state_name'];
            $shipAddress['postcode'] = $order_info[0]['zip'];
            $shipAddress['state_name'] = $order_info[0]['state_name'];


            $ship_max_method = C('UPSMAXMETHOD');
            $ship_max_service_code = C("UPSMAXCODE");
            $feight_ship_money  = $ship_money = $GuaranteedDaysToDelivery = 0;
            $m = M();
            $isfree = 1;
            foreach ( $order_info as $k => $v ) {
                $i++;
                $order_weight =  empty($v['goods_weight']+$v["rahmen_weight"]) ? 1 : $v['goods_weight']+$v["rahmen_weight"];
               
                
                $g_width = getInt($v['goods_width']);
                $g_longth = getInt($v['goods_longth']);
                if ( !empty($v['rahmen_id']) ) {
                    $r_width = getInt($v["rahmen_width"]);
                    $j = 0;
                    if ( !empty($v['inline_id']) ) {
                        if ( $g_width + $g_longth <= 36 ) {
                            $j = 2;
                        } else if ( 36 < ($g_width + $g_longth) && ($g_width + $g_longth) < 60 ) {
                            $j = 3;
                        } else if ( $g_width + $g_longth >= 60 ) {
                            $j = 4;
                        }
                    }
                    $g_width += $r_width * 2 + $j * 2;
                    $g_longth += $r_width * 2 + $j * 2;
                    $goods_size = getGoodsShipSizeWithRahmen($g_longth, $g_width);
                } else {
                    $goods_size = getGoodsShipSize($g_longth, $g_width);
                }

                //商品所得积分
                //$g_integral = $v['g_integral'] + $g_integral;
                $now_money = 0;
                if ( !empty($v['freeshipmoney']) && ($v['goods_price'] + $v['rahmen_price']) >= $v['freeshipmoney'] ) {
                    
                    if ( $goods_size['goods_longth'] + 2 * ($goods_size['goods_width'] + 5.12) >= C("UPSMAXSIZE") ) {
                        $getPost['method'] = '308';
                    }
                    $m->execute("update ab_shop_order_goods set isfree = 1, ups_service_code = '%s', shipfeight = ".$now_money.' where id = '.$v['ogid'], $getPost['method']);
                } else {
                    $isfree = 0;
                    if ( $goods_size['goods_longth'] + 2 * ($goods_size['goods_width'] + 5.12) >= C("UPSMAXSIZE") ) {
                        $now_money = $this->getFeightShipMoney($shipAddress, 1, $goods_size['goods_longth'],$goods_size['goods_width'], $v['goods_price']+$v['rahmen_price'], true);
                        if ( $now_money == 0 ) {
                            $order_status_model->where('order_id = %d', $getPost['order_id'])->delete();
                            $this->_render(self::Err_Pay_Failure);
                        }
                        $feight_ship_money = $feight_ship_money + $now_money;
                        $feightship = true;
                    } else {
                        if ( empty($getPost['ship_method']) || !in_array($getPost['ship_method'], $ship_service_code  ) ) {
                            $order_status_model->where('order_id = %d', $getPost['order_id'])->delete();
                            $this->_render(self::Err_Pay_Failure);
                        }
                        $now_money_arr = $this->getShipMoney($order_info[0]['zip'], 1, $getPost['ship_method'],$goods_size['goods_longth'],$goods_size['goods_width'], true);
                        $now_money = $now_money_arr['money'];
                        $ship_money  = $ship_money + $now_money;
                        $GuaranteedDaysToDelivery = $now_money_arr['GuaranteedDaysToDelivery'];
                        if ($now_money == 0 ) {
                            $order_status_model->where('order_id = %d', $getPost['order_id'])->delete();
                            $this->_render(self::Err_Pay_Failure);
                        }
                    }
                }
                
                $str2 .= '<tr><td style="text-align: center;">'.$i.' of '.$num.'</td>
                            <td style="max-width: 100%; white-space: nowrap;text-align: center;">'.$v['goods_name'].'<br> Framing Option: '.$v['rahmen_name'].'</td>
                            <td style="text-align: center;">'.$v['authorname'].'</td>
                            <td style="text-align: center;">'.$v['medium'].'</td>
                            <td  style="max-width: 100%; white-space: nowrap;">'.$v['pro_no'].'</td>
                            <td style="text-align: center;">'.$v['goods_price'].'</td> </tr>';
            }
            $ship_money = $ship_money + $feight_ship_money;
            
            $addr_list = $shipAddress;
            if ( $order_info[0]['address_id'] == $order_info[0]['billing_id']) {
                $billing_list = $addr_list;
            } else {
                $addr_where2 = " and ab_shop_address.id = ".$order_info[0]['billing_id']." and  ab_shop_address.user_id = ".$getPost['user_id'];
                $billing_list = $addr->getAddress($addr_where2);
                $billing_list = $billing_list[0];
            }
            $taxes = $order_info[0]["taxes"];
            $order_amount = $ship_money + $order_info[0]["payable_amount"] + $order_info[0]["taxes"];
            // 付款
            //开启事务
            

            $res4 = true;
            $res1 = false;

            $ups = C("UPS");
            
            $now_time = date("Y-m-d H:i:s");
            $payment = array('payment'=>"-1","bankname"=>"");
            
        
            //使用优惠券
            // if  ( isset($getPost['voucher_code']) && empty($getPost['voucher_code']) )  {
            //     $voucher = D("ShopVoucher");
            //     $voucher_res = $voucher->getNotUsedVoucherByCode($getPost['user_id'], $getPost['voucher_code']);
            //     if ( count($voucher_res) < 1 ) {
            //         $this->_render(self::Err_Failure);
            //     }
            //     $order_amount = $order_amount - $voucher_res[0]['money'];
            //     $voucher_data['status'] = 1;
            //     $res4 = $voucher->data($voucher_data)->filter("strip_tags")->save();
            //     $order_data['voucher_id'] = $voucher_res[0]['voucher_id'];
            //     $order_data['voucher'] = $voucher_res[0]['voucher'];
            // }
            if ( ShopOrderModel::PAY_YUE == $getPost["pay_way"] ) {  
                $user_money = D("UMoney");
                //查用户余额
                $umoney = $user_money->where(" user = %d ", $getPost["user_id"])->find();
                if ( $umoney['money'] < $order_amount ) {
                    $order_status_model->where('order_id = %d', $getPost['order_id'])->delete();
                    $this->_render(self::Err_Balance_Lack);
                }

                //支付
                $addr->startTrans();
                $res1 = $user_money->where(" user = %d ", $getPost["user_id"])->filter('strip_tags')->save( array("money"=> ($umoney['money'] - $order_amount) ) );
            
            } else if ( ShopOrderModel::PAY_CREDIT_CARD == $getPost["pay_way"] ) {   
                //信用卡付款
                if (  !isset($getPost['expiration_date']) || empty($getPost['expiration_date']) || !isset($getPost['security_code']) || empty($getPost['security_code']) || !isset($getPost['card_num']) || empty($getPost['card_num']) ) {
                    $order_status_model->where('order_id = %d', $getPost['order_id'])->delete();
                    $this->_render(self::Err_No_Data);
                }
                $payment = $order->getCardType( (string)$getPost['card_num']);
                if ( count($payment) < 1) {
                    $order_status_model->where('order_id = %d', $getPost['order_id'])->delete();
                    $this->_render(self::Err_Pay_Failure, "", L("不支持该银行卡") );
                }
                $payment = $payment[0];
                // $post_data1['operate'] = "ChargeCreditCard";
                // $post_data1['amount'] = $order_amount;
                // $post_data1['cardNumber'] = $getPost['card_num'];
                // $post_data1['expirationDate'] = $getPost['expiration_date'];
                // $post_data1['securityCode'] = $getPost['security_code'];
                // $post_data1['email'] = $getPost['email'];
                // $post_data1['token'] = $getPost['token'];
                // $post_data1['taxAmount'] = $taxes;
                // $post_data1['shippingAmount'] = $ship_money;
                //调取接口
                //支付
                // $trans_res = request_post(C('PAYCREDIT'), $post_data1);
                // $trans_res = json_decode($trans_res , true) ;
                $response = $this->chargeCreditCard($order_amount,$getPost['card_num'],$getPost['expiration_date'],$getPost['security_code'],$taxes, $ship_money,$getPost['user_id'],$getPost['email'], $shipAddress);
                
                if ( $response == null ) {
                    $order_status_model->where('order_id = %d', $getPost['order_id'])->delete();
                    $this->_render(self::Err_Pay_Failure);
                }
                $tresponse = $response->getTransactionResponse();

                if ( ($tresponse != null) && ($tresponse->getResponseCode() ==  1 )) {
                    //插入 ab_trans_info
                    $transinfo = D("TransInfo");
                    $trans_data['transId'] = $tresponse->getTransId();
                    $trans_data['orderId'] = $getPost['order_id'];
                    $trans_data['amount'] = $order_amount;
                    $trans_data['result'] = $response->getMessages()->getResultCode();
                    $trans_data['create_time'] = $now_time;
                    $trans_data['tax_amount'] = $taxes;
                    $trans_data['shipping_amount'] = $ship_money;
                    $trans_data['cardNum'] = substr($getPost['card_num'],-4);
                    // $trans_data['cardExpress'] = $getPost['expiration_date'];
                    // $trans_data['secretNum'] = $getPost['security_code'];
                    $trans_data['source'] = $order_info[0]['order_source'];
                    $addr->startTrans();
                    $res1 = $transinfo->data($trans_data)->filter('strip_tags')->add();
                    
                } else {
                    $order_status_model->where('order_id = %d', $getPost['order_id'])->delete();

                    $errorResponse = $tresponse->getErrors();
                    $errorResponse = $errorResponse[0];
                    $this->_render(self::Err_Pay_Failure, '' , $errorResponse->getErrorText());
                }

            } else {
                $order_status_model->where('order_id = %d', $getPost['order_id'])->delete();
                $this->_render(self::Err_Pay_Failure);
            }
            
            //插入 order_express
            //$res2 = $order_express->add($order_express_data);
            //更新 order 状态 为已支付 
            $order_data["pay_status"] = 1;
            $order_data['cardNum'] =   substr($getPost['card_num'],-4);
            $order_data['payment'] = $payment['payment'];
            $order_data['bank_name'] = $payment['bankname'];
            $order_data['ups_service_code'] = $getPost['ship_method'];
            $order_data["pay_time"] = $now_time;
            $order_data["real_amount"] = $order_info[0]["payable_amount"];
            $order_data["is_fast"] = $getPost['ship_method'] != "03" && $getPost['ship_method'] != "" ? 1 : 0;
            $order_data["payable_freight"] = $ship_money;
            $order_data["real_freight"] = $ship_money;
            $order_data["order_amount"] = $order_amount;
            if ( $GuaranteedDaysToDelivery != 0 ) {
                $GuaranteedDaysToDelivery = $GuaranteedDaysToDelivery + 1;
                $order_data['estimated_delivery'] = date("M d,Y", strtotime("+".$GuaranteedDaysToDelivery."days", time()) ) ;
            }
            $order_data["update_time"] = $now_time;
            //所得积分
            //$order_data['g_integral'] = $g_integral;
            $res3 = $order->where(" id = %d and user_id = %d", array($getPost['order_id'], $getPost['user_id']))->filter('strip_tags')->save($order_data);

            // $m = M();
            // $res2 = $m->execute( "update ab_base_user set integral = integral + ".$g_integral." where id = %d ", $getPost['user_id']);
            
            if ( $res1 && $res2 !== false  && $res3 &&  $res4 ) {
                $addr->commit();
                $url =  "https://mandrillapp.com/api/1.0/templates/info.json?key=rawgdIlhVJf5ph4hGg5tag&name=Order%20Confirmation%20-%20Final" ;
                $res = request_get($url);
                $res = json_decode($res, true);
                $subject = $res['name'];
                $body = $res['code'];
                $_body = split("Framing Option", $body);
                $str1 = substr($_body[0], 0 ,strripos($_body[0],"<tr>")) ;
                $str3 = substr($_body[1], strpos($_body[1], "</tr>") + 5, strlen($_body[1]));
                $body = $str1.$str2.$str3;
                $body = str_replace("#Customer Name",$user_info["username"], $body);
                $body = str_replace("[OrderNumber]",$order_info[0]["order_no"], $body);
                $body = str_replace("[Order Number]",$order_info[0]["order_no"], $body);
                $body = str_replace("[Billing Name]",$billing_list['firstname'].$billing_list['lastname'], $body);
                $body = str_replace("[Card num]",substr($getPost['card_num'],-4), $body);
                $body = str_replace("[Card type]",$payment["bankname"], $body);
                $body = str_replace('[Billing Address]', $billing_list['addr'].'<br/>'.$billing_list['address'].'<br/>'.$billing_list['city'].' '.$billing_list['state_name'].' '.$billing_list['postcode'] , $body);
                // $body = preg_replace("/(^.*?)\[Billing Address\]/", $billing_list['city'], $body);
                // $body = preg_replace("/(^.*?)\[Billing Address\]/", $billing_list['state'], $body);
                $body = str_replace("[Shipping Name]",$order_info[0]['accept_name'], $body);
                $body = str_replace("[Shipping Address]",  $addr_list['addr'].'<br/>'.$addr_list['address'].'<br/>'.$addr_list['city'].' '.$addr_list['state_name'].' '.$addr_list['postcode'], $body);
                // $body = preg_replace("/(^.*?)\[Shipping Address\]/", $addr_list['city'], $body);
                // $body = preg_replace("/(^.*?)\[Shipping Address\]/", $addr_list['state'], $body);
                $body = str_replace("[telphone number]",$order_info[0]['mobile'], $body);
                if (  $isfree == 1  ) {
                    $body = str_replace("UPS Ground",C('UPSISFREE'), $body);
                } else  {
                    $body = str_replace("UPS Ground",$ups[$getPost['ship_method']], $body);
                }
                // $body = str_replace("[Artwork Title]",$order_info[0]["goods_name"], $body);
                // $body = str_replace("[Frame Title and Dimensions]",$order_info[0]["rahmen_name"], $body);
                // $body = str_replace("[Artist]",$order_info[0]["payable_amount"], $body);
                // $body = str_replace("[Medium]",$order_info[0]["medium"], $body);
                // $body = str_replace("[Reference Number]",$order_info[0]["goods_id"], $body);
                // $body = str_replace("[Price]",$order_info[0]["goods_price"], $body);
                $body = str_replace("[Subtotal]",$order_info[0]["payable_amount"], $body);
                $body = str_replace("[Shipping]",sprintf("%.2f", $ship_money), $body);
                $body = str_replace("[Tax]",$order_info[0]["taxes"], $body);
                $body = str_replace("[Total]",$order_amount, $body);
                SendMail( $getPost['email'], $subject, $body );
                $order_status_model->where('order_id = %d', $getPost['order_id'])->delete();
                $this->_render(self::Err_Suc, L("支付成功") );
            } else {
                $addr->rollback();
                $order_status_model->where('order_id = %d', $getPost['order_id'])->delete();
                $this->_render(self::Err_Pay_Failure);
            }
        } catch (\Think\Exception $e) {
            $addr->rollback();
            $order_status_model->where('order_id = %d', $getPost['order_id'])->delete();
            $this->_render(self::Err_Failure);
        }

    }

    /**
     * 支付中间方法
     * 订单总价 信用卡卡号  有效期(月年格式如 1216 ) 信用卡安全码
     */
    private function chargeCreditCard($order_amount, $cardNum, $expireDate, $cardCode, $taxes, $ship_money, $user_id, $email, $shipAddress){
        Vendor('Authorize.autoload');
        define("AUTHORIZENET_LOG_FILE", "phplog");

        // Common setup for API credentials
        $merchantAuthentication = new \net\authorize\api\contract\v1\MerchantAuthenticationType();
        $merchantAuthentication->setName('28uQm9n2V8');
        $merchantAuthentication->setTransactionKey('7c66vP83eM8amtQw');
        $refId = 'ref' . time();

        // Create the payment data for a credit card
        $creditCard = new \net\authorize\api\contract\v1\CreditCardType();
        $creditCard->setCardNumber($cardNum);
        $creditCard->setExpirationDate($expireDate);
        $creditCard->setCardCode($cardCode);
        $paymentOne = new \net\authorize\api\contract\v1\PaymentType();
        $paymentOne->setCreditCard($creditCard);

        $order = new \net\authorize\api\contract\v1\OrderType();
        $order->setDescription("New Item");

        $tax = new \net\authorize\api\contract\v1\ExtendedAmountType();
        $tax->setAmount($taxes);
        $tax->setName('tax');

        $shipping = new \net\authorize\api\contract\v1\ExtendedAmountType();
        $shipping->setAmount($ship_money);
        $shipping->setName('shipping charges');

        $customer = new \net\authorize\api\contract\v1\CustomerDataType();
        $customer->setId($user_id);
        $customer->setEmail($eamil);

        $shipTo = new \net\authorize\api\contract\v1\NameAndAddressType();
        $addr_username = implode(' ',  $shipAddress['addr_username']);
        $shipTo->setFirstName($addr_username[0]);
        $shipTo->setLastName($addr_username[1]);
        $shipTo->setAddress($shipAddress['addr'].' '.$shipAddress['address']);
        $shipTo->setCity($shipAddress['city']);
        $shipTo->setState($shipAddress['state']);
        $shipTo->setZip($shipAddress['postcode']);

        //create a transaction
        $transactionRequestType = new \net\authorize\api\contract\v1\TransactionRequestType();
        $transactionRequestType->setTransactionType( "authCaptureTransaction"); 
        $transactionRequestType->setAmount($order_amount);
        $transactionRequestType->setOrder($order);
        $transactionRequestType->setTax($tax);
        $transactionRequestType->setShipping($shipping);
        $transactionRequestType->setCustomer($customer);
        $transactionRequestType->setShipTo($shipTo);
        $transactionRequestType->setPayment($paymentOne);


        $request = new \net\authorize\api\contract\v1\CreateTransactionRequest();
        $request->setMerchantAuthentication($merchantAuthentication);
        $request->setRefId( $refId);
        $request->setTransactionRequest( $transactionRequestType);

        $controller = new \net\authorize\api\controller\CreateTransactionController($request);

        $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::SANDBOX);
        return $response;
    }


    /**
     * 修改订单
     * order_id 订单id 
     */
    public function editOrder() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            if (count($getPost) != 4 || !isset( $getPost['token'] ) || empty( $getPost['token'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] ) || !isset( $getPost['order_id'] ) || empty( $getPost['order_id'] ) || !isset( $getPost['user_id'] ) || empty( $getPost['user_id'] ) ) {
                $this->_render(self::Err_No_Data);
            }
            $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id']);
            $order = D("ShopOrder");
            $t_n = $order->getTableName();
            $where_str = " and ab_base_author_goods.type = 1  and ".$t_n.".user_id = %d and ".$t_n.".id = %d and ".$t_n.".pay_status = 0";
            $where_arr = array($getPost['user_id'], $getPost['order_id']);
            $order_info = $order->getOrder($where_str, $where_arr);
            if ( empty( $order_info) ) {
                $this->_render(self::Err_No_Data);
            }
            if ( $order_info[0]['status'] == 2 )  {
                $this->_render(self::Err_More_15_Minutes);
            }
            $order_weight = 0;
            $ship_max_method = C('UPSMAXMETHOD');
            $ship_max_service_code = C("UPSMAXCODE");
            $shipAddress['id'] = $order_info[0]['address_id'];
            $shipAddress['real_address'] = $order_info[0]['real_address'];
            $shipAddress['ext'] = $order_info[0]['ext'];
            $shipAddress['mobile'] = $order_info[0]['mobile'];
            $shipAddress['addr_username'] = $order_info[0]['accept_name'];
            $shipAddress['addr'] = $order_info[0]['addr'];
            $shipAddress['address'] = $order_info[0]['address'];
            $shipAddress['city'] = $order_info[0]['city'];
            $shipAddress['state'] = $order_info[0]['state_name'];
            $shipAddress['postcode'] = $order_info[0]['zip'];
            $shipAddress['state_name'] = $order_info[0]['state_name'];


            $pack = $bill_list = null;
            if ( !empty($order_info[0]['address_id']) &&  $order_info[0]['address_id'] == $order_info[0]['billing_id'] ) {
                $bill_list = $shipAddress;
            } else if ( $order_info[0]['address_id'] != $order_info[0]['billing_id'] && !empty($order_info[0]['billing_id']) ) {
                $addr = D("ShopAddress");
                $bill_list =$addr->getAddrOrder("and  id = ".$order_info[0]['billing_id']);
            }
            $ship_method = C('UPSMETHOD');
            $ship_service_code = C("UPSCODE");
            $isfree = true;
            foreach ( $order_info as $k => $v ) {
                $order_weight = empty($v["rahmen_weight"] + $v["goods_weight"]) ? 1: $v["rahmen_weight"] + $v["goods_weight"];
                $arr[$k]['goods']['img_url'] = $v['goods_img_url'];
                $arr[$k]['goods']['id'] = $v['goods_id'];
                $arr[$k]['goods']['weight'] = $v['goods_weight'];
                $arr[$k]['goods']['name'] = $v['goods_name'];
                $arr[$k]['goods']['sell_price'] = sprintf("%.2f",$v['goods_price']);
                $arr[$k]['goods']['size'] = $v['size'];
                $arr[$k]['goods']['authorname'] = $v['authorname'];
                $arr[$k]['goods']['backsize'] = $v['backsize'];
                $arr[$k]['goods']['backcolor'] = $v['backcolor'];
                $arr[$k]['rahmen'] = null;
                $g_longth = $v['goods_longth'];
                $g_width = $v['goods_width'];
                if ( !empty($v['rahmen_id']) ) {
                    $arr[$k]['rahmen']['id'] = $v['rahmen_id'];
                    $arr[$k]['rahmen']['weight'] = $v['rahmen_weight'];
                    $colorname = empty($v['colorname']) ? '' : ';'.$v['colorname'].' Matboard';
                    $arr[$k]['rahmen']['name'] = $v['rahmen_name'].$colorname;
                   // $arr[$k]['rahmen']['name'] = $v['rahmen_name'].";".$v['colorname']." Matboard";
                    $arr[$k]['rahmen']['price'] = sprintf("%.2f",$v['rahmen_price']);
                    $arr[$k]['rahmen']['img_url'] = $v['rahmen_img_url'];
                    $arr[$k]['rahmen']['top_left_img'] = $v['top_left_img'];
                    $arr[$k]['rahmen']['rahmen_size']   = $v['rahmen_size'];
                    $arr[$k]['rahmen']['rahmen_width'] = $v['rahmen_width'];
                    $arr[$k]['rahmen']['top_left_img'] = $v['top_left_img'];
                    $arr[$k]['rahmen']['top_right_img'] = $v['top_right_img'];
                    $arr[$k]['rahmen']['bottom_left_img'] = $v['bottom_left_img'];
                    $arr[$k]['rahmen']['bottom_right_img'] = $v['bottom_right_img'];
                    $arr[$k]['rahmen']['border_top_img'] = $v['border_top_img'];
                    $arr[$k]['rahmen']['border_bottom_img'] = $v['border_bottom_img'];
                    $arr[$k]['rahmen']['border_right_img'] = $v['border_right_img'];
                    $arr[$k]['rahmen']['border_left_img'] = $v['border_left_img'];
                    $r_width = getInt($v["rahmen_width"]);

                    $j = 0;
                    if ( !empty($v['inline_id']) ) {
                        if ( $g_width + $g_longth <= 36 ) {
                            $j = 2;
                        } else if ( 36 < ($g_width + $g_longth) && ($g_width + $g_longth) < 60 ) {
                            $j = 3;
                        } else if ( $g_width + $g_longth >= 60 ) {
                            $j = 4;
                        }
                    }

                    $g_width += $r_width * 2 + $j * 2;
                    $g_longth += $r_width * 2 + $j * 2;
                    $goods_size = getGoodsShipSizeWithRahmen($g_longth, $g_width);
                } else {
                    $goods_size = getGoodsShipSize($g_longth, $g_width);
                }
                if ( $v['isfree'] == 0 ) {
                    $isfree = false;
                    if ( $goods_size['goods_longth'] + 2 * ($goods_size['goods_width'] + 5.12) >= C("UPSMAXSIZE") ) {
                        $money4 += $this->getFeightShipMoney($shipAddress, 1, $goods_size['goods_longth'],$goods_size['goods_width'], $v['goods_price']+$v['rahmen_price']);
                        $feightship = true;
                    } else {
                        $money1 +=  $this->getShipMoney($v['zip'], 1, $ship_service_code[0], $goods_size['goods_longth'],$goods_size['goods_width']);
                        $money2 +=  $this->getShipMoney($v['zip'],  1, $ship_service_code[1], $goods_size['goods_longth'],$goods_size['goods_width']);
                        $money3 +=  $this->getShipMoney($v['zip'],  1, $ship_service_code[2], $goods_size['goods_longth'],$goods_size['goods_width']);
                    }
                }
            }
            if ( $money1 )  {
                $money[0]['ship_method'] = $ship_method[0];
                $money[0]['service_code'] = $ship_service_code[0];
                $money[0]['money'] = sprintf("%.2f", $money1);
            }

            if ( $money2 ) {
                $money[1]['ship_method'] = $ship_method[1];
                $money[1]['service_code'] = $ship_service_code[1];
                $money[1]['money'] = sprintf("%.2f", $money2);
            }

            if ( $money3 ) {
                $money[2]['ship_method'] = $ship_method[2];
                $money[2]['service_code'] = $ship_service_code[2];
                $money[2]['money'] = sprintf("%.2f", $money3);
            }

            if ( $money4 ) {
                $feightmoney[0]['ship_method'] = $ship_max_method[0];
                $feightmoney[0]['service_code'] = $ship_max_service_code[0];
                $feightmoney[0]['money'] =  sprintf("%.2f", $money4);
            }

            if ( $isfree == true ) {
                $money[0]['ship_method'] = "3-5 business days";
                $money[0]['service_code'] = "03";
                $money[0]['money'] =  "free";
            }
            $now_time = date("Y-m-d H:i:s");
            $addr_where = empty($order_info[0]['address_id']) ? " and user_id = ".$getPost['user_id'] : "and  id = ".$order_info[0]['address_id'];
            $addr = D("ShopAddress");
            $taxes =  $order_info[0]['taxes'];
            // if (  empty($order_info[0]['address_id']) ) {
            //     $addr_list =$addr->getAddrOrder($addr_where);
                
            //     if ( $addr_list  ) {
            //         $order_express_data["order_id"] = $order_info[0]['order_id'];
            //         $order_express_data["accept_name"] = $addr_list['addr_username'];
            //         $order_express_data["phone"] = $addr_list['phone'];
            //         $order_express_data["mobile"] = $addr_list['mobile'];
            //         $order_express_data["province"] = $addr_list['addr_username'];
            //         $order_express_data["city"] = $addr_list['city'];
            //         $order_express_data["billing_id"] = $addr_list['id'];
            //         $order_express_data["address"] = $addr_list['address'];
            //         $order_express_data["addr"] = $addr_list['addr'];
            //         $order_express_data["zip"] = $addr_list['postcode'];
            //         $order_express_data["address_id"] = $addr_list['id'];
            //         $order_express_data["goods_invoice"] = 0;
            //         $order_express_data["delete_status"] = 0;
            //         $order_express_data["create_time"] = $now_time;
            //         //插入 order_express
            //         $order_express = D("ShopOrderExpress");
            //         $order_express->startTrans();
            //         $res1 = $order_express->date($order_express_data)->filter('strip_tags')->add();

            //         $order = D("ShopOrder");
            //         $tax = $this->getTaxAvc($addr_list['postcode']);
            //         $taxes = sprintf("%.2f", $tax * $order_info[0]["payable_amount"]);
            //         $order_data["adjust_taxes"] = $tax;  //商品税率
            //         $order_data["taxes"] = $taxes;  //商品税费
            //         $order_data["order_amount"] = sprintf("%.2f",$order_info[0]['payable_amount']  + $taxes );
            //         $res2 = $order->where("user_id = %d and id = %d", array($getPost['user_id'], $getPost['order_id']))->filter('strip_tags')->save($order_data);
            //         if ( $res1 && $res2 ) {
            //             $order_express->commit();
            //         }else {
            //              $order_express->rollback();
            //              $this->_render(self::Err_Failure);
            //         }

            //     }
            // }
            $res = [];
 
            $res['goods'] = $arr;
            $res['address']  = empty($order_info[0]['address_id']) ? $addr_list : $shipAddress;
            $res['billing'] = $bill_list;
            $res['shipmoney'] = array_values($money);
            $res['feightshipmoney'] = $feightmoney;
            $res['taxex'] = $taxes;
            //0 可订单可延时 1不能延时 
            $res['order'] = array("create_time"=>strtotime($order_info[0]['create_time']),"update_time"=>strtotime($order_info[0]['update_time']),"id"=>$order_info[0]['order_id'], "order_no"=>$order_info[0]['order_no'], "now_time"=>time(), "expire_time"=>strtotime("+30minutes", strtotime($order_info[0]['update_time']) ) );
            $this->_render(self::Err_Suc, $res);
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    

    }
    /**
     * 订单查询 不同情况下的订单 
     */
    public function ordersList() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            $order = D("ShopOrder");
            $status_arr = $order->getOrderStatus();
            if ( count($getPost) != 6  || !isset( $getPost['count'] ) || empty( $getPost['count'] ) ||  !isset( $getPost['page'] ) || empty( $getPost['page'] ) || !isset( $getPost['token'] ) || empty( $getPost['token'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] ) ||  !in_array($getPost['status'], $status_arr ) || !isset( $getPost['status'] ) || empty( $getPost['status'] ) || !isset( $getPost['user_id'] ) || empty( $getPost['user_id'] ) ) {
                $this->_render(self::Err_No_Data);
            }
            $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id']);
            
            $t_n = $order->getTableName();
            $where_str = " and ab_base_author_goods.type = 1 and ".$t_n.".user_id = %d ";
            if ( 5 == $getPost["status"] ) {
                $where_str .= " and ".$t_n.".status = 2 and ".$t_n.".pay_status = 0";
            } else if ( $getPost["status"] == 1 ) {
                $where_str .= " and ".$t_n.".status = 1 and ".$t_n.".pay_status = 0";
            } else if ( $getPost["status"] == 2 ) {
                $where_str .= " and ".$t_n.".status = 1 and ".$t_n.".pay_status = 1";
            } else if ( $getPost["status"] == 3 ) {
                $where_str .= " and ".$t_n.".status = 0 and ".$t_n.".pay_status = 1 and e.delivery_status = 0";
            } else if ( $getPost["status"] == 4 ) {
                $where_str .= " and ".$t_n.".status = 4 and ".$t_n.".pay_status = 1";
            } else if ( $getPost["status"] == 6 ) {
                $where_str .= " and ".$t_n.".status = 3 and ".$t_n.".pay_status = 1";
            } else if ( $getPost["status"] == 7 ) {
                $where_str .= " and ".$t_n.".status = 2 and ".$t_n.".pay_status = 1";
            } else if ( $getPost["status"] == 8 ) {
                $where_str .= " and ".$t_n.".status = 5 and ".$t_n.".pay_status = 1";
            } else if ( $getPost["status"] == 9 ) {
                $where_str .= " and ".$t_n.".status = 6 and ".$t_n.".pay_status = 1";
            } else if ( $getPost["status"] == 10 ) {
                $where_str .= " and ".$t_n.".status = 0 and ".$t_n.".pay_status = 1 and e.delivery_status = 1";
            } else if ( $getPost["status"] == 100 ) {
                $where_str .= "and ((".$t_n.".pay_status = 0 and ".$t_n.".status = 1) or (".$t_n.".pay_status = 1 and ".$t_n.".status != 4 )) ";
            } else if ( $getPost["status"] == 101 ) {
                $where_str .= "";
            } else {
                $where_str .= "";
            }

            $where_arr = array( $getPost['user_id'] );
            $order_info = $order->getOrderLimit($where_str, $where_arr, $getPost['page'], $getPost['count']);
            $arr = [];
            $w = 2;
            $i = 0;
            $now_time = time();
            //处理数据格式  app使用
            foreach ( $order_info as $k => $v ) {
                foreach ( $arr as $key => $val ) {
                    if ( isset($val['order_no']) && ( $val['order_no'] == $v['order_no'] ) ) {
                        $w = 1;
                        $_k = $key;
                        break;
                    }
                }
                if ( 1 == $w ) {
                    $arr[$_k]['data'][] = $v;
                    $w = 2;
                } else {
                    if (0 == $v['pay_status'] && ShopOrderModel::A_CANCEL == $v['status']) {
                        //未支付取消订单
                        
                        $arr[$i]['order_status'] = ShopOrderModel::ORDER_STATUS_CANCLE;
                    } else if (0 == $v['pay_status'] && 1 == $v['status'] ) {
                        //未支付订单
                        
                        $arr[$i]['order_status'] = ShopOrderModel::ORDER_STATUS_NO_PAY;
                        $arr[$i]['now_time'] = $now_time;
                        $arr[$i]['expire_time'] = strtotime("+30minutes",strtotime($v['update_time']) );
                    } else if (1 == $v['pay_status'] && 1 == $v['status']   ) {
                        //已支付  审核中订单  
                        
                        $arr[$i]['order_status'] = ShopOrderModel::ORDER_STATUS_CHECKING;
                    } else if (1 == $v['pay_status'] && 0 == $v['status'] && $v['delivery_status'] == 0) {
                        
                        //已支付 发货中
                        $arr[$i]['order_status'] = ShopOrderModel::ORDER_STATUS_SHIPPING;
                    } else if (1 == $v['pay_status'] && 4 == $v['status'] ) {
                        
                        // 签收完成 订单
                        $arr[$i]['order_status'] = ShopOrderModel::ORDER_STATUS_COMPLETE;
                    } else if (1 == $v['pay_status'] && 3 == $v['status'] ) {
                        
                        //已支付 审核失败
                        $arr[$i]['estimated_delivery'] = '';
                        $arr[$i]['order_status'] = ShopOrderModel::ORDER_STATUS_CKFAILURE;
                    } else if (1 == $v['pay_status'] && 2 == $v['status']) {
                        
                        //已支付 订单被取消
                        $arr[$i]['estimated_delivery'] = '';
                        $arr[$i]['order_status'] = ShopOrderModel::ORDER_STATUS_PAY_CKFAILURE;
                    } else if (1 == $v['pay_status'] && 5 == $v['status']) {
                        
                        //已支付 订单退货中
                        $arr[$i]['order_status'] = ShopOrderModel::ORDER_STATUS_RETURNING;
                    } else if (1 == $v['pay_status'] && 6 == $v['status']) {
                        
                        //已支付 订单退货成功
                        $arr[$i]['order_status'] = ShopOrderModel::ORDER_STATUS_RETURNFAILURE;
                    }
                     else if (1 == $v['pay_status'] && 0 == $v['status'] && $v['delivery_status'] == 1) {
                        
                        //已支付 订单退货成功
                        $arr[$i]['order_status'] = ShopOrderModel::ORDER_STATUS_NOSHIPPING;
                    }
                    $arr[$i]['order_no'] = $v['order_no'];
                    $arr[$i]['order_id'] = $v['order_id'];
                    $arr[$i]['data'][] = $v;
                }
                $i++;
            }
            $this->_render(self::Err_Suc, array_values($arr) );
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
            
    }

    /**
     * 获取税费中间方法
     * @param zip 邮编
     * @return 税费
     * 邮编无效，不是纽约，税费为0
     */
    protected function getTaxAvc($zip) {
        $zip = (string)$zip;
        $cnt = 0;
        for ($i=0; $i < 10 ; $i++) {
            $res = file_get_contents('https://taxrates.api.avalara.com/postal?country=usa&postal='.$zip.'&apikey=jrTOPAB9derkSteuVm2ql0GUu70FYv9W4uWvc0J8%2FfKuNvkWxEACmElfocaR98zmlInrI4Xa08LCnVeLm1Hvpw==');
            if ( $res != null ) {
                break;
            } 
            $cnt++;
        }
        $str  = !isset($res)||empty($res)?$cnt.' '.$zip.' '.date('Y-m-d H:i:s').' '.$res." faild":$cnt.' '.$zip.' '.date('Y-m-d H:i:s').' '.$res;
        file_put_contents("Logs/aa.txt", $str.PHP_EOL, FILE_APPEND);
        $taxes = 0;
        $res = json_decode($res, true);
        if (is_array($res) && $res['rates'] ) {
            foreach ($res['rates'] as $key => $value) {
                if ( $value['type'] == 'State' ){
                    if ($value['name'] == "NEW YORK" ) {
                        $taxes = $res['totalRate']/100 ;
                        break;
                    }
                }
            }
        }
        return $taxes;
     }

    /**
     *获取运费中间方法
     */
    protected function getShipMoney( $shipPostalCode, $weight, $serviceCode ,$length,$width,  $param = false, $param2 = false) {
        $post_data = array(
          'shipPostalCode'  => $shipPostalCode,        //邮编
          'weight'          => $weight,                 //商品重量 画+画框
          'serviceCode'     => $serviceCode,              //服务类型 03 代表陆运 59普通陆运2天到 14加急第二天到
          'length'          => $length,
          'width'           => $width
        );
        $money = 0;
        for ($i = 0; $i < 5 ; $i++) { 
            $ship_money = json_decode( request_post(C('GETSHIPMONEYURL'), $post_data) , true);
            if ( 1 == $ship_money[0]['responseStatusCode'] && "" == $ship_money[0]['errorCode']) {
                $money = $ship_money[0]['listShipment'][0]['totalCharges']['monetaryValue'];
                break;
            }
            if ( ( 111208 == $ship_money[0]['errorCode'] || 111210 == $ship_money[0]['errorCode'] ) && 0 == $ship_money[0]['responseStatusCode'] ) {
                $money = null;
                break;
            }
        }
        if ( $param == false && $money === 0 ) {
            $this->_render(self::Err_Ship_Money_Failure, "", $ship_money[0]['errorDescription']);
        }
        if ( $param2 == true ) {
            $arr['money'] = $money;
            //支付后需要预计到货时间
            $arr['GuaranteedDaysToDelivery'] = $ship_money[0]['listShipment'][0]['GuaranteedDaysToDelivery'];
            return $arr;
            
        }
        return $money;

    }

    /**
     *获取超重物体运费 
     */
    protected function getFeightShipMoney( $addr_list, $weight ,$length,$width,$sell_price,  $param = false) {
        $post_data = array(
          'shipPostalCode'  => $addr_list['postcode'],        //邮编
          'weight'          => $weight,                 //商品重量 画+画框
          'length'          => $length,
          'width'           => $width,
          "address1"        => $addr_list['addr'],
          "address2"        => $addr_list['address'],
          "city"            => $addr_list['city'],
          "state"           => $addr_list['state'],
          "monetaryValue"   => $sell_price
        );
        $money = 0;
        for ($i = 0; $i < 5 ; $i++) { 
            $ship_money = json_decode( request_post(C('GETFEIGHTSHIPMONEYURL'), $post_data) , true);
            if (  $ship_money['money'] != 0 ) {
                $money = $ship_money['money'];
                break;
            }
        }
        if ( $param == false && $money === 0 ) {
            $this->_render(self::Err_Ship_Money_Failure, "", "We did not find available UPS services.");
        }
        return $money > 270 ? 270 : $money;

    }

    /**
     * 订单详情
     */
    public function orderDetail(){
        try {
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            if ( !isset( $getPost['token'] ) || empty( $getPost['token'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] ) || !isset( $getPost['order_id'] ) || empty( $getPost['order_id'] ) || !isset( $getPost['user_id'] ) || empty( $getPost['user_id'] ) ) {
                $this->_render(self::Err_No_Data);
            }
            $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id']);
            $order = D("ShopOrder");
            $t_n = $order->getTableName();
            $where_str = "  and ".$t_n.".user_id = %d and ".$t_n.".id = %d";
            $where_arr = array($getPost['user_id'], $getPost['order_id']);
            $order_info = $order->getOrder($where_str, $where_arr);

            if ( empty( $order_info )) {
                $this->_render(self::Err_No_Data);
            }
            $addr_where = empty($order_info[0]['address_id']) ? " and user_id = ".$getPost['user_id'] : "and  id = ".$order_info[0]['address_id'];
            $addr = D("ShopAddress");
            $shipAddress['id'] = $order_info[0]['address_id'];
            $shipAddress['addr'] = $order_info[0]['addr'];
            $shipAddress['address'] = $order_info[0]['address'];
            $shipAddress['city'] = $order_info[0]['city'];
            $shipAddress['state'] = $order_info[0]['state'];
            $shipAddress['state_name'] = $order_info[0]['state_name'];
            $shipAddress['country'] = "UNITED STATES";
            $shipAddress['real_address'] = $order_info[0]['real_address'].' UNITED STATES';
            $shipAddress['ext'] = $order_info[0]['ext'];
            $shipAddress['mobile'] = $order_info[0]['mobile'];
            $shipAddress['postcode'] = $order_info[0]['zip'];
            $shipAddress['addr_username'] = $order_info[0]['accept_name'];


            $addr_list =$shipAddress;
            $pack = $bill_list = null;
            if ( !empty($order_info[0]['address_id']) &&  $order_info[0]['address_id'] == $order_info[0]['billing_id'] ) {
                $bill_list = $addr_list;
            } else if ( $order_info[0]['address_id'] != $order_info[0]['billing_id'] && !empty($order_info[0]['billing_id']) ) {
                $bill_list =$addr->getAddrOrder("and  id = ".$order_info[0]['billing_id']);
                $bill_list['country'] = "UNITED STATES";
                $bill_list['real_address'] = $bill_list['real_address'].' UNITED STATES';
            }
            $j = 0;
            $order_price = 0;
            $isfree = true;
            foreach ( $order_info as $k => $v ) {
                $order_price = $order_price + sprintf("%.2f",($v["rahmen_price"] +  $v["goods_price"]) );
                $arr[$k]['goods_id'] = $v['goods_id'];
                $arr[$k]['goods']['img_url'] = $v['goods_img_url'];
                $arr[$k]['goods']['id'] = $v['goods_id'];
                $arr[$k]['goods']['weight'] = $v['goods_weight'];
                $arr[$k]['goods']['name'] = $v['goods_name'];
                $arr[$k]['goods']['sell_price'] = sprintf("%.2f",$v['goods_price']);
                $arr[$k]['goods']['size'] = $v['size'];
                $arr[$k]['goods']['authorname'] = $v['authorname'];
                $arr[$k]['goods']['backsize'] = $v['backsize'];
                $arr[$k]['goods']['backcolor'] = $v['backcolor'];
                $arr[$k]['goods']['shipnumber'] = $v['shipnumber'];
                if ( !empty($v['shipnumber']) ) {
                    $post_data1['trackingNumber'] = $v['shipnumber'];
                    $res = json_decode(request_post(C('TRACKPACKAGEURL'), $post_data1), true);
                    $pack = $res['obj']['list'];
                }
                $arr[$k]['goods']['pack'] = $pack;
                $arr[$k]['rahmen'] = null;
                if ( !empty($v['rahmen_id']) ) {
                    $arr[$k]['rahmen_id'] = $v['rahmen_id'];
                    $arr[$k]['rahmen']['id'] = $v['rahmen_id'];
                    $arr[$k]['rahmen']['weight'] = $v['rahmen_weight'];

                    $colorname = empty($v['colorname']) ? '' : ';'.$v['colorname'].' Matboard';
                    $arr[$k]['rahmen']['name'] = $v['rahmen_name'].$colorname;
                    $arr[$k]['rahmen']['price'] = sprintf("%.2f",$v['rahmen_price']);
                    $arr[$k]['rahmen']['img_url'] = $v['rahmen_img_url'];
                    $arr[$k]['rahmen']['top_left_img'] = $v['top_left_img'];
                    $arr[$k]['rahmen']['rahmen_size']   = $v['rahmen_size'];
                    $arr[$k]['rahmen']['rahmen_width'] = $v['rahmen_width'];
                    $arr[$k]['rahmen']['top_left_img'] = $v['top_left_img'];
                    $arr[$k]['rahmen']['top_right_img'] = $v['top_right_img'];
                    $arr[$k]['rahmen']['bottom_left_img'] = $v['bottom_left_img'];
                    $arr[$k]['rahmen']['bottom_right_img'] = $v['bottom_right_img'];
                    $arr[$k]['rahmen']['border_top_img'] = $v['border_top_img'];
                    $arr[$k]['rahmen']['border_bottom_img'] = $v['border_bottom_img'];
                    $arr[$k]['rahmen']['border_right_img'] = $v['border_right_img'];
                    $arr[$k]['rahmen']['border_left_img'] = $v['border_left_img'];
                }
                if ( $v['isfree'] == 0 ) {
                    $isfree = false;
                }
                $j++;
            }
            $res2['estimated_delivery'] = $order_info[0]['estimated_delivery'];
            if (0 == $order_info[0]['pay_status'] && ShopOrderModel::A_CANCEL == $order_info[0]['status']) {
                        //未支付取消订单
                $res2['order_status'] = ShopOrderModel::ORDER_STATUS_CANCLE;
            } else if (0 == $order_info[0]['pay_status'] && 1 == $order_info[0]['status'] ) {
                //未支付订单
                
                $res2['order_status'] = ShopOrderModel::ORDER_STATUS_NO_PAY;
            } else if (1 == $order_info[0]['pay_status'] && 1 == $order_info[0]['status']   ) {
                //已支付  审核中订单  
                
                $res2['order_status'] = ShopOrderModel::ORDER_STATUS_CHECKING;
            } else if (1 == $order_info[0]['pay_status'] && 0 == $order_info[0]['status']) {
                
                //已支付 发货中
                $res2['order_status'] = ShopOrderModel::ORDER_STATUS_SHIPPING;
            } else if (1 == $order_info[0]['pay_status'] && 4 == $order_info[0]['status'] ) {
                
                // 签收完成 订单
                $res2['order_status'] = ShopOrderModel::ORDER_STATUS_COMPLETE;
            } else if (1 == $order_info[0]['pay_status'] && 3 == $order_info[0]['status'] ) {
                
                //已支付 审核失败
                $res2['estimated_delivery'] = '';
                $res2['order_status'] = ShopOrderModel::ORDER_STATUS_CKFAILURE;
            } else if (1 == $order_info[0]['pay_status'] && 2 == $order_info[0]['status']) {
                
                //已支付 订单被取消
                $res2['estimated_delivery']  = '';
                $res2['order_status'] = ShopOrderModel::ORDER_STATUS_PAY_CKFAILURE;
            } else if (1 == $v['pay_status'] && 5 == $v['status']) {
                
                //已支付 订单退货中
                $res2['order_status'] = ShopOrderModel::ORDER_STATUS_RETURNING;
            } else if (1 == $v['pay_status'] && 6 == $v['status']) {
                
                //已支付 订单退货成功
                $res2['order_status'] = ShopOrderModel::ORDER_STATUS_RETURNFAILURE;
            }

            $ups = C("UPS");
            $res2['goods'] = $arr;
            $res2['order_id'] = $order_info[0]['order_id'];
            $res2['order_no'] = $order_info[0]['order_no'];
            $res2['bank_logo'] = $order_info[0]['peyment_name'];
            $res2['subtotal'] = $order_price;

            if ( $isfree == false ) {
                $res2['shipping'] = $order_info[0]['payable_freight'];
            } else {
                $res2['shipping'] = "free";
            }
            $res2['taxes'] = $order_info[0]['taxes'];
            $res2['item'] = $j;
            $res2['voucher'] = $order_info[0]['voucher'];
            $res2['balance'] = $order_info[0]['use_account_amount'];
            //$maxShipMethod = C('UPSMAXMETHOD');
            //$res2['ups_method'] = !empty($order_info[0]['ups_service_code']) ? $ups[$order_info[0]['ups_service_code']] : $maxShipMethod[0];
            $res2['ups_method'] = C('UPSISFREE');
            
            $res2['order_total'] = $order_info[0]['payable_freight'] + $order_info[0]['taxes'] + $order_price - $order_info[0]['voucher'] - $order_info[0]['use_account_amount'];
            //$res2['order_amount'] = $order_info[0]['order_amount'];
            $res2['address'] = $addr_list;
            $res2['billing'] = $bill_list;
            $this->_render(self::Err_Suc, $res2);
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    
    }



    /**
     * 订单页面选择收获地址，对应不同邮费 税费
     * 参数 address_id 收获地址id
     * order_id 
     * 返回twoday  和 nextday  luyun对应的邮费
     * type 1 收货地址 2 发票地址
     */
    public function chooseOrderAddr() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            if (!isset( $getPost['type'] ) || empty( $getPost['type'] ) || !in_array($getPost['type'], array(1, 2)) || !isset( $getPost['address_id'] ) || empty( $getPost['address_id'] ) || !isset( $getPost['token'] ) || empty( $getPost['token'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] ) || !isset( $getPost['order_id'] ) || empty( $getPost['order_id'] ) || !isset( $getPost['user_id'] ) || empty( $getPost['user_id'] ) ) {
                $this->_render(self::Err_No_Data);
            }
            $user_info = $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id'], true);

            $addr = D("ShopAddress");
            $addr_where = " and id = ".(int)$getPost['address_id']." and  user_id = ".(int)$getPost['user_id'];
            $addr_list =$addr->getAddrOrder($addr_where);
            if ( empty($addr_list) ) {
                $this->_render(self::Err_No_Shipping_Address);
            }
            $order = D("ShopOrder");
            $t_n = $order->getTableName();
            $where_str = " and ab_base_author_goods.type = 1  and ".$t_n.".user_id = %d and ".$t_n.".id = %d and ".$t_n.".pay_status in (0)";
            $where_arr = array($getPost['user_id'], $getPost['order_id']);
            $order_info = $order->getOrder($where_str, $where_arr);
            if ( empty( $order_info) ) {
                $this->_render(self::Err_No_Data);
            }
            if ( $order_info[0]['status']  == 2 ) {
                $this->_render(self::Err_More_15_Minutes);
            }

            $order_express = D("ShopOrderExpress");
            $order_express->startTrans();
            $now_time = date("Y-m-d H:i:s");
            
            $ship_max_method = C('UPSMAXMETHOD');
            $ship_max_service_code = C("UPSMAXCODE");



            if ( 1 == $getPost['type'] ) {
                $order_e_res =  $order_express->field('id , address_id')->where('order_id = %d', $getPost['order_id'])->find();
                $order_express_data["order_id"] = $getPost['order_id'];
                $order_express_data["accept_name"] = $addr_list['addr_username'];
                $order_express_data["phone"] = $addr_list['phone'];
                $order_express_data["mobile"] = $addr_list['mobile'];
                $order_express_data["province"] = $addr_list['province'];
                $order_express_data["city"] = $addr_list['city'];
                
                $order_express_data["address"] = $addr_list['address'];
                $order_express_data["addr"] = $addr_list['addr'];
                $order_express_data["zip"] = $addr_list['postcode'];
                $order_express_data["address_id"] = $addr_list['id'];
                if ( empty($order_e_res) ) {
                    $order_express_data["goods_invoice"] = 0;
                    $order_express_data["delete_status"] = 0;
                    $order_express_data["create_time"] = $now_time;
                    //插入 order_express
                    $res = $order_express->data($order_express_data)->filter('strip_tags')->add();
                    $s = 1;
                } else if ( !empty($order_e_res) && $order_e_res['address_id'] != $getPost['address_id'] ) {
                    
                    $order_express_data["update_time"] = $now_time;
                    $res = $order_express->where("order_id = %d ", $getPost['order_id'])->filter('strip_tags')->save($order_express_data);
                    $s = 1;
                } else if ( !empty($order_e_res) && $order_e_res['address_id'] == $getPost['address_id'] ){
                    $res = true;
                    $s = 2;
                }

                $res2 = true;
                $taxes = $order_info[0]['taxes'];
                if (  1 == $s ) {
                    $order = D("ShopOrder");

                    $tax = $this->getTaxAvc($addr_list['postcode']);

                    $taxes =  $tax * $order_info[0]["payable_amount"] ;
                    
                    $order_data["adjust_taxes"] = $tax;  //商品税率
                    $order_data["taxes"] = $taxes;  //商品税费
                    $order_data["order_amount"] = sprintf("%.2f",$order_info[0]['payable_amount']   + $taxes );
                    $res2 = $order->where("user_id = %d and id = %d", array($getPost['user_id'], $getPost['order_id']))->filter('strip_tags')->save($order_data);
                }

                if ( $res && $res2 !== false ) {
                    $order_express->commit();
                }else {
                    $order_express->rollback();
                    $this->_render(self::Err_Failure);
                }
                $ship_method = C('UPSMETHOD');
                $ship_service_code = C("UPSCODE");
                $order_weight = 0;
                $isfree = true;
                foreach ( $order_info as $k => $v ) {
                    $order_weight =  empty($v["rahmen_weight"] + $v["goods_weight"]) ? 1 : $v["rahmen_weight"] + $v["goods_weight"];
                    $g_width =  $v['goods_width'] ;
                    $g_longth =  $v['goods_longth'] ;
                    if ( !empty($v['rahmen_id']) ) {
                        $r_width = getInt($v["rahmen_width"]);
                        $j = 0;
                        if ( !empty($v['inline_id']) ) {
                            if ( $g_width + $g_longth <= 36 ) {
                                $j = 2;
                            } else if ( 36 < ($g_width + $g_longth) && ($g_width + $g_longth) < 60 ) {
                                $j = 3;
                            } else if ( $g_width + $g_longth >= 60 ) {
                                $j = 4;
                            }
                        }
                        $g_width += $r_width * 2 + $j * 2;
                        $g_longth += $r_width * 2 + $j * 2;
                        $goods_size = getGoodsShipSizeWithRahmen($g_longth, $g_width);
                    } else {
                        $goods_size = getGoodsShipSize($g_longth, $g_width);
                    }
                    if ( $v['isfree'] == 0 ) {
                        $isfree = false;
                     
                        if ( $goods_size['goods_longth'] + 2 * ($goods_size['goods_width'] + 5.12) >= C("UPSMAXSIZE") ) {
                            $money4 += $this->getFeightShipMoney($addr_list, 1, $goods_size['goods_longth'],$goods_size['goods_width'], $v['rahmen_price']+$v['goods_price']);
                            $feightship = true;
                        } else {
                            $money1 += $this->getShipMoney($addr_list['postcode'],1, $ship_service_code[0],$goods_size['goods_longth'],$goods_size['goods_width']);
                            $money2 += $this->getShipMoney($addr_list['postcode'],1, $ship_service_code[1],$goods_size['goods_longth'],$goods_size['goods_width']);
                            $money3 += $this->getShipMoney($addr_list['postcode'],1, $ship_service_code[2],$goods_size['goods_longth'],$goods_size['goods_width']);
                        }
                    }
                }

            if ( $money1 )  {
                $money[0]['ship_method'] = $ship_method[0];
                $money[0]['service_code'] = $ship_service_code[0];
                $money[0]['money'] = sprintf("%.2f", $money1);
            }

            if ( $money2 ) {
                $money[1]['ship_method'] = $ship_method[1];
                $money[1]['service_code'] = $ship_service_code[1];
                $money[1]['money'] = sprintf("%.2f", $money2);
            }

            if ( $money3 ) {
                $money[2]['ship_method'] = $ship_method[2];
                $money[2]['service_code'] = $ship_service_code[2];
                $money[2]['money'] = sprintf("%.2f", $money3);
            }
            if ( $money4 ) {
                $feightmoney[0]['ship_method'] = $ship_max_method[0];
                $feightmoney[0]['service_code'] = $ship_max_service_code[0];
                $feightmoney[0]['money'] =  sprintf("%.2f", $money4);
            }

            if ( $isfree == true ) {
                $money[0]['ship_method'] = "3-5 business days";
                $money[0]['service_code'] = "03";
                $money[0]['money'] =  "free";
            }
                // for ( $i = 0; $i < count($ship_method); $i++ ) {
                //     $money[$i]['ship_method'] = $ship_method[$i];
                //     $money[$i]['service_code'] = $ship_service_code[$i];
                //     $money[$i]['money'] = $this->getShipMoney($addr_list['postcode'], $order_weight, $ship_service_code[$i]);
                //     if ( $money[$i]['money'] === 0) {
                //         unset($money[$i]);
                //     }
                // }
                //税费
                //$taxes = $this->getTaxAvc($addr_list['postcode']) * $order_info[0]["payable_amount"];
                $arr['money'] = array_values($money);
                $arr['feightshipmoney'] = $feightmoney;
                $arr['taxes'] =  sprintf("%.2f",$taxes);

                $this->_render(self::Err_Suc, $arr);
            }
            if ( 2 == $getPost['type'] ) {
                $order_express_data["billing_id"] = $addr_list['id'];
                
                $order_express_data["update_time"] = $now_time;
                //print_r($order_express_data);exit();
                $res = $order_express->where("order_id = %d ", $getPost['order_id'])->filter('strip_tags')->save($order_express_data);
                $order_express->commit();
                $this->_render(self::Err_Suc, "");
            }
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    
    }
    /**
     * 在订单页面倒计时中，如果15分钟未支付将商品返回到购物车
     * 要修改商品的库存 订单的状态 判断这次取消是不是当天的第三次，是的话要把用户加锁，还要判断是不是本月的第十次，是的话要永久锁
     */
    public function reAddShopCart() {
        try {
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            if ( !isset( $getPost['token'] ) || empty( $getPost['token'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] ) || !isset( $getPost['order_id'] ) || empty( $getPost['order_id'] ) || !isset( $getPost['user_id'] ) || empty( $getPost['user_id'] ) ) {
                $this->_render(self::Err_No_Data);
            }
            $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id']);
            $order = D("ShopOrder");
            $t_n = $order->getTableName();
            $where_str = " and ab_base_author_goods.type in (1)  and ".$t_n.".user_id = %d and ".$t_n.".id = %d";
            $where_arr = array($getPost['user_id'], $getPost['order_id']);
            //$order_info = $order->getOrder($where_str, $where_arr);

            $order_info = $order->getOrderInfo($getPost['user_id'], $getPost['order_id']);
            $orderGoodsModel =  D("ShopOrderGoods");
            $order_goods_info = $orderGoodsModel->getOrderGoods((int)$getPost['order_id'], (int)$getPost['user_id']);

            if ( count( $order_info ) < 1) {
                $this->_render(self::Err_No_Data);
            }
     
            if (1 == $order_info[0]['pay_status']) {
                $this->_render(self::Err_Order_Paid);
            }

            if ( 2 == $order_info[0]['status'] && 0 == $order_info[0]['pay_status']) {
                $this->_render(self::Err_Suc,null);
            }

            $now_time = date("Y-m-d H:i:s");
            if ( !isset( $getPost['cancel_order'] ) || ( isset( $getPost['cancel_order'] ) &&  $getPost['cancel_order'] != 1 ) ) {
                //订单时间是否已超过30分钟
                if ( time()  < strtotime("+30minutes",strtotime($order_info[0]['update_time'])) ) {
                    $this->_render(self::Err_Failure);
                }
            }

            $user = D("BaseUser");
            $userinfo = $user->updateUserInfo( $getPost['email'], $getPost['user_id'] );
            $rahmen_ids = [];
            foreach ($order_goods_info as $k => $v) {
                $gstr[] = $v['goods'];
                if ( $v['frame'] ) {
                    if ( isset( $rahmen_ids[$v['frame']] ) ) {
                        $rahmen_ids[$v['frame']] = $rahmen_ids[$v['frame']] + $v['frame_count'];
                    } else {
                        $rahmen_ids[$v['frame']] = $v['frame_count'];
                    }
                }
                if ( $v['inline_id'] ) {
                    if ( isset( $rahmen_ids[$v['inline_id']] ) ) {
                        $rahmen_ids[$v['inline_id']] = $rahmen_ids[$v['inline_id']]+ $v['frame_in_count'];
                    } else {
                        $rahmen_ids[$v['inline_id']] = $v['frame_in_count'];
                    }
                }

                $order_goods_info[$k]["backColor"] = $v['backcolor'];
                $order_goods_info[$k]["backSize"] = $v['backsize'];
                $order_goods_info[$k]["colorName"] = $v['colorname'];
            }

 
            $enshrine = D("ShopEnshrine");
            $order->startTrans();
            $order_data['status'] = 2;
            $order_data['obsolete_reason'] = "Haven't pay for the order ,cancelled automatically";
            $order_data['update_time'] = $now_time;
            $res = $order->where("id = %d", $order_info[0]['id'])->filter('strip_tags')->save($order_data);
            $order_ob = D("ShopOrderObsolete");
            $sql1 = "update ab_base_goods set inventory = 1 where id in (".implode(",", $gstr).")";
            
            $sql3 = "insert into ab_shop_order_obsolete (order_id, user_id, obsolete_time) values (".$order_info[0]['id'].",".(int)$getPost['user_id'].",'".$now_time."')";
            //$sql4 = "insert into ab_shop_enshrine (user, frame, goods,backColor,backSize,frame_count,frame_in_count,inline_id) values ".implode(",", $order_goods_info);
            $m = M();
            
            $res1 = $m->execute($sql1);
                
            $res2 = true;
            if ( $rahmen_ids ) {
                $res2 = false;
                foreach ( $rahmen_ids as $k => $v ) {
                    $sql2 = "update ab_shop_rahmen set inventory = inventory + ".$v." where id = ".$k;
                    $res2 = $m->execute($sql2);
                    if ( $res2 == false ) {
                        break;
                    }
                }
            }
            $res3 = $m->execute($sql3);
            $res4 = true;
            if ( $userinfo[0]['countshopcart'] + count($order_goods_info) <= C('SHOPCARTMAXNUM')) {
                // $res4 = $m->execute($sql4);
                $res4 = $enshrine->addAll($order_goods_info);
            }
            if ( $res && ($res1 || $res1 === 0) && $res2 && $res3 && $res4 ) {
                $order->commit();
            } else {
                $order->rollback();
                $this->_render(self::Err_Failure);
            }
            $user = D("BaseUser");
            $ob_hours = $order_ob->getCount($getPost['user_id'], "24 HOUR");
 
            $lock = false;
            //一天3次取消订单一天禁止登陆
            if ($ob_hours[0]['count'] >= 3) {
                $user_data['update_time'] = $now_time;
                $user_data['is_able'] = 1;
                $res4 = $user->where("id = %d",array($getPost['user_id']))->filter('strip_tags')->save($user_data);
                $lock = true;
            }
            $ob_day = $order_ob->getCount($getPost['user_id'], "30 DAY");

            //一个月10次永远禁止登陆
            if ($ob_day[0]['count'] >= 10) {
                $user_data['update_time'] = $now_time;
                $user_data['is_able'] = 2;
                $user->where("id = %d",array($getPost['user_id']))->filter('strip_tags')->save($user_data);
                $lock = true;
            }
            if ($userinfo[0]['countshopcart'] >= 40) {
                if ($lock) {
                    $this->_render(self::Err_Shopcart_Lock);
                }
                $this->_render(self::Err_Shopcart_Noreadd);
            }
            if ( $lock ) {
                $this->_render(self::Err_User_Locked);
            }

            $this->_render(self::Err_Suc,null);
        } catch (\Think\Exception $e) {
            $order->rollback();
            $this->_render(self::Err_Failure);
        }
    }

    public function changeOrderTime() {
        try {
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            if ( !isset( $getPost['token'] ) || empty( $getPost['token'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] ) || !isset( $getPost['order_id'] ) || empty( $getPost['order_id'] ) || !isset( $getPost['user_id'] ) || empty( $getPost['user_id'] ) ) {
                $this->_render(self::Err_No_Data);
            }
            $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id']);
            $order = D("ShopOrder");
            $t_n = $order->getTableName();
 
            $order_info = $order->getOrderInfo($getPost['user_id'], $getPost['order_id']);
            
            if ( empty( $order_info )) {
                $this->_render(self::Err_No_Data);
            }
            if ( $order_info[0]['status']  == 2 ) {
                $this->_render(self::Err_More_15_Minutes);
            }
            if ( $order_info[0]['pay_status']  == 1 ) {
                $this->_render(self::Err_Order_Paid);
            }
            $res = $order->execute("update ab_shop_order set update_time = DATE_ADD(update_time,INTERVAL 30 MINUTE) where update_time = create_time and id =  ".$order_info[0]['id']);
            if ( $res == false ) {
                $this->_render(self::Err_Failure);
            }
            //0 可订单可延时 1不能延时 
            $data['order'] = ["create_time"=>strtotime($order_info[0]['create_time']),"update_time"=>strtotime('+30minutes',strtotime($order_info[0]['create_time'])), "now_time"=>time(), "expire_time"=>strtotime('+1hours',strtotime($order_info[0]['create_time']))];
            $this->_render(self::Err_Suc,$data);
        } catch (\Think\Exception $e) {
            $order->rollback();
            $this->_render(self::Err_Failure);
        }
    }

}