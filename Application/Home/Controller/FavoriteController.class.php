<?php
namespace Home\Controller;
use Home\Controller\MyController;

class FavoriteController extends MyController {
    public function _empty() {
        $this->errRender();
    }

    /**
     * 收藏列表
     */
    public function favoriteList() {
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
            $favorite = D("UserFavorite");
            $res = $favorite->_getFavoriteList($getPost['user_id']);
            $this->_render(self::Err_Suc, $res);
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    
    }

    /**
     * 添加收藏
     */
    public function addFavorite() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);

            if (!isset( $getPost['token'] ) || empty( $getPost['token'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] ) ||!isset( $getPost['goods_id'] ) || empty( $getPost['goods_id'] ) || !isset( $getPost['user_id'] ) || empty( $getPost['user_id'] )  || !isset( $getPost['rahmen_id'] ) || !isset( $getPost['color_id'] )  ) {
                $this->_render(self::Err_No_Data);
            }

            $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id']);

            $goods = D("BaseGoods");
            $rahmen = D("ShopRahmen");
            $user = D("BaseUser");
            $scaleboard = D("BaseScaleboard");
            $bundling = D("ShopBundling");
            $color_res = "";

            $goods_res = $goods->field('ab_base_goods.id,ab_base_goods.material,longth,width, inventory, is_buy_framed,sc.id as board_id ')
                               ->join(" left join ab_base_scaleboard sc on sc.goodsid = ab_base_goods.id and sc.delete_status in (0)")
                               ->where('ab_base_goods.is_online in (1) and  ab_base_goods.delete_status in (0) and ab_base_goods.id = %d', $getPost['goods_id'])
                               ->find();
            //$str = empty($getPost['rahmen_id']) ? " (frame_id is null or frame_id = '' or frame_id = 0 ) " : "frame_id = ".$getPost['rahmen_id'];
            $favorite = D("UserFavorite");
            $ens_res = $favorite->field('id')->where('user_id = %d and goods_id = %d  ', array( $getPost['user_id'], $getPost['goods_id'] ) )->find();

            if (!empty($ens_res)) {
                $data['goods_id'] = $getPost['goods_id'];
                $data['user_id']  = $getPost['user_id'];
                if ( $favorite->where("goods_id = %d and user_id = %d", $data['goods_id'], $data['user_id'])->delete() ) {
                    $this->_render(self::Err_Suc, L("取消成功") );
                }
            } 
            $rahmen_res = null;
            if ( !empty($getPost['rahmen_id']) ) {
                $rahmen_res = $bundling->getRahmenById($getPost['rahmen_id']);
            }
     
            if ( empty($goods_res) || (!empty($getPost['rahmen_id']) && empty($rahmen_res) ) ) {
                $this->_render(self::Err_No_Data);
            }
        
            if ( 1 == $goods_res['is_buy_framed'] && empty($getPost['rahmen_id']) ) {
                $this->_render(self::Err_Goods_Buy_Frame);
            }
            if ( $goods_res['inventory'] < 1) {
                $this->_render(self::Err_Goods_Over);
            }

            if (  ( ($goods_res['material'] == 400 || $goods_res['material'] == 401) && empty( $getPost['rahmen_id'] )) || ( ($goods_res['material'] == 400 || $goods_res['material'] == 401) && empty( $getPost['color_id'] ) ) ) {
                $this->_render(self::Err_Failure);
            }

            if ( $rahmen_res ) {
                $color_res = array();
                if ( !empty($getPost['color_id']) && ($goods_res['material'] == 400 || $goods_res['material'] == 401) ) {
                    $color_res = $scaleboard->getColorBoard(" and cb.id = %d and ab_base_scaleboard.goodsid = %d", array( $getPost['color_id'], $getPost['goods_id'] ));
                    
                    if ( count($color_res) == 0 ) {
                        $this->_render(self::Err_Failure);
                    }
                }
                
            } 
             
            $data['user_id']  = $getPost['user_id'];
            if ( !empty($getPost['rahmen_id'])) {
                $price_res = $this->getRahmenPrice($rahmen_res, $goods_res, $color_res);

                $data['frame_id'] = $getPost['rahmen_id'];
                $data['frame_price'] = $price_res['price'];
                $data['frame_count'] = $price_res['outnum'];
                $data['inline_id'] = $price_res['inline_size'];
                $data['frame_in_count'] = $price_res['innum'];
                if ( $color_res ) {
                    $data['backColor'] = $color_res[0]['colorcode'];
                    $data['backSize'] = $price_res['backsize'];
                    $data['colorName'] = $color_res[0]['colorname'];
                    
                }
            }
            $data['goods_id'] = $getPost['goods_id'];
            $res = $favorite->data($data)->filter('strip_tags')->add();
            if ( $res ) {
                $this->_render(self::Err_Suc, L("收藏成功") );
            } 
            $this->_render(self::Err_Suc,L("失败") );
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    
    }

    protected function getRahmenPrice($rahmen_res, $goods_res, $color_res){



            $goods_res['width'] = getInt($goods_res['width']);
            $goods_res['longth'] = getInt($goods_res['longth']);
            $rahmen_res['width'] = getInt($rahmen_res['width']);
            if ( $goods_res['width'] + $goods_res['longth'] <= 36 ) {
                $k = 2;
            } else if ( 36 < ($goods_res['width'] + $goods_res['longth']) && ($goods_res['width'] + $goods_res['longth']) < 60 ) {
                $k = 3;
            } else if ( $goods_res['width'] + $goods_res['longth'] >= 60 ) {
                $k = 4;
            }
            //商品的宽度和长度
            $width = $goods_res['width'] + 2 * $rahmen_res['width'];
            $height = $goods_res['longth'] + 2 * $rahmen_res['width'];
            //如果有村办商品的宽度和长度
            if ( $color_res ) {
                $width +=  $k * 2;
                $height +=  $k * 2;
            }
            $min = $width;   //18
            $max = $height;     //14
            if ( $height < $width) {
                $min = $height;     
                $max = $width;      
            }
            //outline_length 11
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
                $this->_render(self::Err_Rahmen_Over);
            }
            $outprice = $i * $rahmen_res['unit_price'];  
            $j = $inprice = $inline_id =  0;

            if ( count($color_res) == 0 ) {
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
                    $this->_render(self::Err_Rahmen_Over);
                }
                $inprice = $j * $inline['unit_price'];

                $rahmen_res['auxiliary_price2'] = $rahmen_res['auxiliary_price'];
            }

            $price = ( $outprice + $inprice + $rahmen_res['operation_price'] + $rahmen_res['auxiliary_price2'] ) * C("RAHMEN_PRICE_PARAM");

        return array("outnum"=>$i,"innum"=>$j,"price"=>round($price), "backsize"=>$k, "inline_size"=>$inline_id);
    }

    /**
    *删除收藏
    */
    public function deleteFavorite() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            if (!isset( $getPost['token'] ) || empty( $getPost['token'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] ) ||!isset( $getPost['favorite_id'] ) || empty( $getPost['favorite_id'] ) || !isset( $getPost['user_id'] ) || empty( $getPost['user_id'] )   ) {
                $this->_render(self::Err_No_Data);
            }
            $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id']);
            $favorite = D("UserFavorite");
            $fav_res = $favorite->field('id')->where('user_id = %d and id = %d', array($getPost['user_id'], $getPost['favorite_id']))->find();
            if ( empty($fav_res) ) {
                $this->_render(self::Err_No_Data);
            }
            if ( $favorite->where('user_id = %d and id = %d', array($getPost['user_id'], $getPost['favorite_id']) )->delete() ){
                $this->_render(self::Err_Suc, L("删除成功") );
            }
            $this->_render(self::Err_Suc,L("失败") );
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    
    }

    /**
    *收藏页面 点击 添加购物车 到购物车
    *参数 favorites 为 每条收藏的id集合 
    *参数形式 {"favorites":[17,18,19,20],"user_id":"","token":"","email":""}
    */
    public function favoriteToShopCart() {
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            if (!isset( $getPost['token'] ) || empty( $getPost['token'] ) || !isset( $getPost['email'] ) || empty( $getPost['email'] ) || !isset( $getPost['favorites'] ) || empty( $getPost['favorites'] ) || !isset( $getPost['user_id'] ) || empty( $getPost['user_id'] )   ) {
                $this->_render(self::Err_No_Data);
            }
            $this->ckUser($getPost['token'], $getPost['email'], $getPost['user_id']);
            $favorite = D("UserFavorite");
            $id = implode(',', $getPost['favorites']);
            $where['id'] = array("in", $id);
            $where['user_id'] = $getPost['user_id'];
            $res = $favorite->field(" inline_id,   goods_id as goods, frame_id as frame , backColor  , backSize , colorName , frame_price , frame_count , frame_in_count")->where($where)->select();
            $enshrine = D("ShopEnshrine");
            $ens_res = $enshrine->field('goods')->where('user = %d  ', $getPost['user_id'])->select();
            foreach ($ens_res as $key => $value) {
                $ids[] = $value['goods'];
            }
            
            $now_time = date("Y-m-d H:i:s");
            foreach ( $res as $k=> $v ) {
                if (in_array( $v['goods'], $ids) ) {
                    // $sql = "update ab_shop_enshrine SET frame = ".$v['frame'].", backColor = '".$v['backcolor']."', backSize = '".$v['backsize']."', colorName = '".$v['colorname']."', frame_price = ".$v['frame_price'].", frame_count = '".$v['frame_count']."', frame_in_count = '".$v['frame_in_count']."', inline_id = ".$v['inline_id']." where goods_id in (".$v['goods'].")  and user_id = ".(int)$getPost['user_id'];
                    // $res = $enshrine->execute($sql);
                    // continue;
                    $_ids[] = $v['goods'];
                }
                $v['create_time'] = $now_time;
                $v['user'] = $getPost['user_id'];
                $v["backColor"] = $v['backcolor'];
                $v['backSize'] = $v['backsize'];
                $v['colorName'] = $v['colorname'];
                unset($v['backcolor']);
                unset($v['backsize']);
                unset($v['colorname']);
                $data[] = $v;
            }
            if ( count($_ids) > 0 ) {
                $sql = " delete from ab_shop_enshrine where goods in (".implode(',', $_ids).") and user =  ".$getPost['user_id'];
                $res = $enshrine->execute($sql);
            }
            
            if ( empty($data) ) {
                $this->_render(self::Err_Suc, L("添加成功"));
            } else {
                $data = array_values($data);
                if ( $enshrine->addAll($data) ) {
                    $this->_render(self::Err_Suc, L("添加成功"));
                }
                $tis->_render(self::Err_Failure);
            }
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    
    }
    
}