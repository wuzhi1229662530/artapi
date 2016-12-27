<?php
namespace Home\Controller;
use Home\Controller\MyController;

class ShopcartController extends MyController {
    public function _empty() {
        $this->errRender();
    }


    /**
     * 购物车列表
     */
    public function shopCartList() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            if (!isset( $getPost['user_id'] ) || empty( $getPost['user_id'] ) ||!isset( $getPost['token'] ) || empty( $getPost['token'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] )) {
                $this->_render(self::Err_No_Data);
            }
            $enshrine = D("ShopEnshrine");
            $res = $enshrine->_getShopCartList($getPost['user_id']);
            $rahmen_price = 0;
            foreach ( $res as $k => $v ) {  
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
                $ens_arr[$k]['goods']['backcolor'] = $v['backcolor'];
                $ens_arr[$k]['goods']['backsize'] = $v['backsize'];
                $ens_arr[$k]['rahmen'] = null;
                if ( !empty($v['rahmen_id']) ) {
                    $ens_arr[$k]['rahmen']['id'] = $v['rahmen_id'];
                    $ens_arr[$k]['rahmen']['weight'] = $v['rahmen_weight'];
                    $ens_arr[$k]['rahmen']['inventory'] = $v['rahmen_inventory'];
                    $colorname = empty($v['colorname']) ? '' : ';'.$v['colorname'].' Matboard';
                    $ens_arr[$k]['rahmen']['name'] = $v['rahmen_name'].$colorname;
                    $ens_arr[$k]['rahmen']['price'] = sprintf("%.2f",$v['price']);
                    $rahmen_price = $v['price'];
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
                }
                $ens_arr[$k]['goods']['isfree'] = null;
                if ( !empty($v['freeshipmoney']) && ($v['sell_price'] + $rahmen_price) >= $v['freeshipmoney'] ) {
                    $ens_arr[$k]['goods']['isfree'] = 1;
                }
            }

            $addr = D("ShopAddress");
            $arr = [];
            $arr['addr_num'] = $addr->field('count(id) as addr_num')->where(" delete_status = 0 and adr_type = 0 and user_id = %d", array( $getPost['user_id'] ) )->find();
            $arr['subdata'] = $ens_arr;
            $this->_render(self::Err_Suc, $arr);
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    
    }

    /**
     * 添加购物车
     * cover  ==  提示是否覆盖当前购物车里已添加的商品，== 2  覆盖操作不提示
     */

    public function addShopCart() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');

            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);

            if (  !isset( $getPost['color_id'] ) || !isset( $getPost['token'] ) || empty( $getPost['token'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] ) ||!isset( $getPost['ShopGoodsID'] ) || empty( $getPost['ShopGoodsID'] ) || !isset( $getPost['UserID'] ) || empty( $getPost['UserID'] )  || !isset( $getPost['RahmenID'] )  ) {
                $this->_render(self::Err_No_Data);
            }

            $user_info = $this->ckUser($getPost['token'], $getPost['email'], $getPost['UserID'], true);
            $enshrine = D("ShopEnshrine");
            $num = $enshrine->_countShopCart($getPost['UserID']);
            if ( $num >= 40) {
                $url =  "https://mandrillapp.com/api/1.0/templates/info.json?key=rawgdIlhVJf5ph4hGg5tag&name=40%2B%20paintings%20Template" ;
                $res = request_get($url);
                $res = json_decode($res, true);
                $subject = $res['name'];
                $body = $res['code'];
                $body = str_replace("[username]", $user_info['username'], $body);
                SendMail( $getPost['email'], $subject, $body );
                $this->_render(self::Err_Shopcart_More);
            }

            $goods = D("BaseGoods");
            $rahmen = D("ShopRahmen");
            $user = D("BaseUser");
            $scaleboard = D("BaseScaleboard");
            $bundling = D("ShopBundling");
            $color_res = "";

            $goods_res = $goods->field('ab_base_goods.id,ab_base_goods.material,longth,width, inventory, is_buy_framed,sc.id as board_id ')
                               ->join(" left join ab_base_scaleboard sc on sc.goodsid = ab_base_goods.id and sc.delete_status in (0)")
                               ->where('ab_base_goods.is_online in (1) and  ab_base_goods.delete_status in (0) and ab_base_goods.id = %d', $getPost['ShopGoodsID'])
                               ->find();

            $rahmen_res = null;
            if ( !empty($getPost['RahmenID']) ) {
                $rahmen_res = $bundling->getRahmenById($getPost['RahmenID']);
            }

            if ( empty($goods_res) || (!empty($getPost['RahmenID']) && empty($rahmen_res) ) ) {
                $this->_render(self::Err_No_Data);
            }
            if ( $goods_res['inventory'] < 1) {
                $this->_render(self::Err_Goods_Over);
            } 
            if ( 1 == $goods_res['is_buy_framed'] && empty($getPost['RahmenID']) ) {
                $this->_render(self::Err_Goods_Buy_Frame);
            }

            if (  ( ($goods_res['material'] == 400 || $goods_res['material'] == 401) && empty( $getPost['RahmenID'] )) || ( ($goods_res['material'] == 400 || $goods_res['material'] == 401) && empty( $getPost['color_id'] ) ) ) {
                // $this->_render(self::Err_Failure);
                $rahmen_res = null;
            } 
            if ( $rahmen_res ) {

                $color_res = array();
                if ( !empty($getPost['color_id']) && ($goods_res['material'] == 400 || $goods_res['material'] == 401) ) {
                    $color_res = $scaleboard->getColorBoard(" and cb.id = %d and  ab_base_scaleboard.goodsid = %d ", array( $getPost['color_id'], $getPost['ShopGoodsID'] ));

                    if ( count($color_res) == 0 ) {
                        // $this->_render(self::Err_Failure);
                        $rahmen_res = null;
                    }
                }
                
            }

            $ens_res = $enshrine->field('id,frame,backColor')->where('user = %d and goods = %d ', array($getPost['UserID'], $getPost['ShopGoodsID'] ) )->find();
            $now_time = date("Y-m-d H:i:s");
            // if ( !empty($ens_res) ) {
            //     if (  $getPost['RahmenID']  == "") {
            //         $sql = "update ab_shop_enshrine set frame = null,backColor = null,backSize=null,colorName=null,frame_price=null,frame_count=null,frame_in_count=null,inline_id=null where id = ".$ens_res['id'];
            //         if ( $enshrine->execute($sql) ) {
            //             $this->_render(self::Err_Suc,L("成功") );
            //         }
            //     }
            //     if (  !empty( $getPost['RahmenID'] ) ) {
            //         $price_res = $this->getRahmenPrice($rahmen_res, $goods_res, $color_res);
            //         $data2['frame'] = $getPost['RahmenID'];
            //         if ( $rahmen_res ) {
            //             $price_res = $this->getRahmenPrice($rahmen_res, $goods_res, $color_res);
            //             $data2['frame_price'] = round($price_res['price']);
            //             $data2['frame_count'] = $price_res['outnum'];
            //             $data2['frame_in_count'] = $price_res['innum'];
            //             $data2['inline_id'] = $price_res['inline_size'];
            //             $data2['create_time'] = $now_time;
            //             if ( $color_res ) {
            //                 $data2['backColor'] = $color_res[0]['colorcode'];
            //                 $data2['colorName'] = $color_res[0]['colorname'];
            //                 $data2['backSize'] = $price_res['backsize'];
            //             }
            //         }
            //         $res = $enshrine->where("id = ".$ens_res['id'])->filter('strip_tags')->save($data2);
            //         if ( $res || $res === 0) {
            //             $this->_render(self::Err_Suc,L("成功") );
            //         }
            //     }
                
            // }

            if ( !empty($ens_res) ) {
                $sql = " delete from ab_shop_enshrine where goods in (%d) and user = %d ";
                $enshrine->execute($sql, array($getPost['ShopGoodsID'], $getPost['UserID']) );
                
            }
//            if ( empty($ens_res) ) {
                $data['user']  = $getPost['UserID'];
                if (!empty($getPost['RahmenID']) ) {
                    $data['frame'] = $getPost['RahmenID'];
                    if ( $rahmen_res ) {
                        $price_res = $this->getRahmenPrice($rahmen_res, $goods_res, $color_res);
                        if ( $price_res ) {
                            $data['frame_price'] = round($price_res['price']);
                            $data['frame_count'] = $price_res['outnum'];
                            $data['frame_in_count'] = $price_res['innum'] ;
                            $data['inline_id'] = $price_res['inline_size'];
                            if ( $color_res ) {
                                $data['backColor'] = $color_res[0]['colorcode'];
                                $data['colorName'] = $color_res[0]['colorname'];
                                $data['backSize'] = $price_res['backsize'];
                            }
                        }

                    }
                }
                $data['goods'] = $getPost['ShopGoodsID'];
                $data['create_time'] = $now_time;
                if ( $enshrine->data($data)->filter('strip_tags')->add() ) {
                    $this->_render(self::Err_Suc,L("成功") );
                }
 //           }
            $this->_render(self::Err_Failure,L("失败") );
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    
    }

 

    protected function getRahmenPrice($rahmen_res, $goods_res, $color_res){
 
            $rahmen_res['width'] = getInt($rahmen_res['width']);
            $glass = 0;  //装裱用的玻璃价格
            if ( $goods_res['width'] + $goods_res['longth'] <= 36 ) {
                $k = 2;
                $glass = 20;
            } else if ( 36 < ($goods_res['width'] + $goods_res['longth']) && ($goods_res['width'] + $goods_res['longth']) < 60 ) {
                $k = 3;
                $glass = 45;
            } else if ( $goods_res['width'] + $goods_res['longth'] >= 60 ) {
                $k = 4;
                $glass = 65;
            }
            //商品的宽度和长度 
            $width = $goods_res['width'] + 2 * $rahmen_res['width'];
            $height = $goods_res['longth'] + 2 * $rahmen_res['width']; 
            //如果有村办商品的宽度和长度
            if ( $color_res ) {
                $width +=  $k * 2;
                $height +=  $k * 2;
            }
            $min = $width;   //16
            $max = $height;     //17
            if ( $height < $width) {
                $min = $height;     
                $max = $width;      
            }
            //outline_length 30
            //$i 外线条 的数量
            if ( ( $width + $height ) * 2 < $rahmen_res['length'] ) {
                $i = 1;
            } else if ( $rahmen_res['length'] <=( $width + $height ) * 2 && ( $width + $height ) * 2 < 2 * $rahmen_res['length']  ) {
                $i = 2;
            } else if (  2 * $rahmen_res['length'] <= ( $width + $height ) * 2 && ( $width + $height ) * 2 < 3 * $rahmen_res['length']   && $min < $rahmen_res['length'] * 0.5 ) {
                $i = 3;
            } else  {
                if (  $rahmen_res['length'] < $max  ) {
                    $i = ceil(( $height + $width ) * 2 / $rahmen_res['length']);
                } else {
                    $i = 4;
                }
            }
            if ( $rahmen_res['inventory'] < $i ) {
                return [];
            }
            $outprice = $i * $rahmen_res['unit_price'] + $glass;  
            $j = $inprice = $inline_id =  0; 
            if ( count($color_res) == 0 ) {
                $outprice = $outprice - $glass;  
                $width = $goods_res['width'] ;
                $height = $goods_res['longth']; 
                //$inline_size 内线条的尺寸
                $inline_size_length = $width + $height;
                //大号：9999中号：8610小号：8609
                $inline_size = 0;
                if ( $inline_size_length < 48 ) {
                    $inline_size = C("INLINE_SIZE_S");   //9609
                } else if ( $inline_size_length >= 48 && 70 >= $inline_size_length ) {
                    $inline_size = C("INLINE_SIZE_M");  //8610
                } else if ( 70 < $inline_size_length ) {
                    $inline_size = C("INLINE_SIZE_L");  //9999
                } 
                $shop_bundling = D("ShopBundling");
                $inline = $shop_bundling->getInline($inline_size);
                $inline = $inline[0];
                $inline_id = $inline['id'];
                $min = $width;
                $max = $height;
                if ( $height < $width) {
                    $min = $height;
                    $max = $width;
                }
                
                if ( ( $width + $height ) * 2 < $inline['length'] ) {
                    $j = 1;
                } else if ( $inline['length'] <=( $width + $height ) * 2  && ( $width + $height ) * 2 < 2 * $inline['length']  ) {
                    $j = 2;
                } else if ( ( 2 * $inline['length'] <= ( $width + $height ) * 2 && ( $width + $height ) * 2 < 3 * $inline['length'] ) && $min < $inline['length'] * 0.5 ) {
                    $j = 3;
                } else  {
                    if (  $inline['length'] < $max  ) {
                        $j = (int)ceil(( $height + $width ) * 2 / $inline['length']);
                    } else {
                        $j = 4;
                    }
                }
                if ( $inline['inventory'] < $j ) {
                    return [];
                }
                $inprice = $j * $inline['unit_price']; 
                $rahmen_res['auxiliary_price2'] = $rahmen_res['auxiliary_price'];
            }
            $price = ( $outprice + $inprice  + $rahmen_res['operation_price'] + $rahmen_res['auxiliary_price2'] ) * C("RAHMEN_PRICE_PARAM");

        return array("outnum"=>$i,"innum"=>$j,"price"=>$price, "backsize"=>$k, "inline_size"=>$inline_id);
         
    }


    /**
     * 删除购物车
     */

    public function deleteShopCart() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            if (!isset( $getPost['token'] ) || empty( $getPost['token'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] ) ||!isset( $getPost['enshrine_id'] ) || empty( $getPost['enshrine_id'] ) || !isset( $getPost['user_id'] ) || empty( $getPost['user_id'] )   ) {
                $this->_render(self::Err_No_Data);
            }
            $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id']);
            $enshrine = D("ShopEnshrine");
            $ens_res = $enshrine->field('id')->where('user = %d and id = %d', array($getPost['user_id'], $getPost['enshrine_id']))->find();
            if ( empty($ens_res) ) {
                $this->_render(self::Err_No_Data);
            }
            if ( $enshrine->where('user = %d and id = %d', array($getPost['user_id'], $getPost['enshrine_id']) )->delete() ){
                $this->_render(self::Err_Suc, L("成功") );
            }
            $this->_render(self::Err_Failure  );
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    
    }
    /**
    *购物车页面 点击 添加收藏 到收藏
    *参数 shopcarts 为 每条购物车的id集合 
    *参数形式 {"shopcarts":[17,18,19,20],"user_id":"","token":"","email":""}
    */
    public function shopcartToFavorite() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            if (!isset( $getPost['token'] ) || empty( $getPost['token'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] ) || !isset( $getPost['shopcarts'] ) || empty( $getPost['shopcarts'] ) || !isset( $getPost['user_id'] ) || empty( $getPost['user_id'] )   ) {
                $this->_render(self::Err_No_Data);
            }
            $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id']);
            $favorite = D("UserFavorite");
              $enshrine = D("ShopEnshrine");
            $id = implode(',', $getPost['shopcarts']);
            $where['id'] = array("in", $id);
            $where['user'] = $getPost['user_id'];
            $res = $enshrine->field(" inline_id,   goods as  goods_id ,  frame as frame_id  , backColor , backSize , colorName , frame_price , frame_count , frame_in_count")->where($where)->select();
            $ens_res = $favorite->field('goods_id')->where('user_id = %d  ', $getPost['user_id'])->select();
            foreach ($ens_res as $key => $value) {
                $ids[] = $value['goods_id'];
            }
            foreach ( $res as $k=> $v ) {
                if (in_array( $v['goods_id'], $ids) ) {
                    // $sql = "update ab_user_favourit SET frame_id = ".$v['frame_id'].", backColor = '".$v['backcolor']."', backSize = '".$v['backsize']."', colorName = '".$v['colorname']."', frame_price = ".$v['frame_price'].", frame_count = '".$v['frame_count']."', frame_in_count = '".$v['frame_in_count']."', inline_id = ".$v['inline_id']." where goods_id in (".$v['goods_id'].")  and user_id = ".(int)$getPost['user_id'];
                    // $res = $enshrine->execute($sql);
                    // continue;
                    $_ids[] = $v['goods_id'];
                }
                $v['user_id'] = $getPost['user_id'];
                $v["backColor"] = $v['backcolor'];
                $v['backSize'] = $v['backsize'];
                $v['colorName'] = $v['colorname'];
                unset($v['backcolor']);
                unset($v['backsize']);
                unset($v['colorname']);
                $data[] = $v;
            }
            if ( count($_ids) > 0 ) {
                $sql = " delete from ab_user_favourit where goods_id in (".implode(',', $_ids).") and user_id =  ".$getPost['user_id'];
                $res = $enshrine->execute($sql);
            }
            
            
            if ( empty($data) ) {
                $this->_render(self::Err_Suc, L("收藏成功"));
            } else {
                $data = array_values($data);
                if ( $favorite->addAll($data) ) {
                    $this->_render(self::Err_Suc, L("收藏成功"));
                }
                $tis->_render(self::Err_Failure);
            }
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    
    }
}