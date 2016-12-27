<?php
namespace Home\Controller;
use Home\Controller\MyController;
use Home\Model\BaseExpertRecommendModel;
use Home\Model\BaseFeaturedModel;
use Home\Model\BaseGoodsModel;
use Home\Model\BaseRecommendModel;
use Home\Model\DictionModel;
use Home\Model\LifeNewsModel;
use Home\Model\OperationClickModel;
use Home\Model\BaseSaleConfigModel;
use Home\Model\DictypeModel;
use Home\Model\BaseHelpCategoryModel;
use Home\Model\ApiConstModel;
class ShopController extends MyController {


    public function _initialize(){
        $this->noParam();
    }

    /**
     * shop首页数据
     */
    public function index()
    {
        try{
            //轮播图
            $recommendModel = new BaseRecommendModel();
            $lunboInfo = $recommendModel->getRecommendById(BaseFeaturedModel::SHOP_LUNBO_ID,C('__AB_WEB_URL__'));

            //特价
            $SaleCfgModel = new BaseSaleConfigModel();
            $saleCfgInfo = $SaleCfgModel->getSaleCfg();
            $goodsModel = new BaseGoodsModel();
            $count = 32;
            $special_arr = [];
            if ( $saleCfgInfo && $saleCfgInfo['config_flag'] != 1 ) {
                $special_arr = $goodsModel->getBackGoods();
                $count = 16;
            }
            //推荐
            $expertModel = new BaseExpertRecommendModel();
            $expertInfo  = $expertModel->getExpertCommend("1,3",$count);
//            //TOPCATE分类
//            $dictionModel = new DictionModel();
//            $dictionInfo = $dictionModel->getTopCate();
//            //news
//            $lifenewsModel = new LifeNewsModel();
//            $newsInfo = $lifenewsModel->getNewsFromShop();
//            //猜你喜欢
//            $user_id = null;
//            $post_data = file_get_contents('php://input','r');
//            if ( $post_data && !empty($post_data) ) {
//                $getPost = json_decode($post_data, 1);
//                if ( isset($getPost['user_id'])  ) {
//                    $user_id = empty($getPost['user_id']) ? "" : (int)$getPost['user_id'];
//                }
//            }
//            $ip = _get_client_ip();
//            $ip = empty( $ip ) ? "" : $ip;

//            $recommInfo = $goodsModel->getRecommendForUser($ip, $user_id);
            //侧滑
            $helpModel = new BaseHelpCategoryModel();
            $help_column = $helpModel->getTree();
            $home_arr = array("id"=>"", "help_url"=>"", "help_name"=>"HOME", "parent_id"=> "","url"=>"", "lev"=>0, "son"=>array());
            array_unshift($help_column, $home_arr);
            $arr = [
                "lunbo"=>$lunboInfo,
                "expert_recom"=>$expertInfo,
                "help_column"=>$help_column,
                "special_value"=>$special_arr
            ];
            $this->myResponse(ApiConstModel::SUCCESS,$arr);
        } catch (\Think\Exception $e) {
            $this->myResponse(ApiConstModel::ERROR);
        }
    }

    /**
     * shop 商品列表页 filters搜索
     */
    public function filterGoods() {
//        try{
//            $post_data = file_get_contents('php://input','r');
//            if ( !$post_data ) {
//                $this->myResponse(ApiConstModel::ERROR);
//            }
//            $getPost = json_decode($post_data, 1);
            $filter = new DictypeModel();
            $filter_res = $filter->getFilter();
        print_r($filter_res);exit();
//            if ( !isset( $getPost['filters'] ) || !isset( $getPost['page'] ) || empty( $getPost['page'] ) || !isset( $getPost['count'] ) || empty( $getPost['count'] ) ) {
//                $this->myResponse(ApiConstModel::ERROR);
//            }
//            $param = $getPost['filters'];
//            //theme orientation(format)  medium  size
//            //{"THEME":["434","382","385"],"FORMAT":["429","431"],"MEDIUM":["395"]}
//            $page = (int)$getPost['page'];
//            $count = (int)$getPost['count'];
//            $medium = array();
//            if ( !empty($param) ) {
//                foreach ( $param as $key => $val ) {
//                    if ( !in_array( $key, array("THEME", "FORMAT", "MEDIUM", "SIZE", "PRICE") ) ) {
//                        $this->myResponse(ApiConstModel::ERROR);
//                    }
//                    $key = strtolower( $key );
//                    if ( count($val) < 1 || $key == "price") {
//                        continue;
//                    }
//                    foreach ( $val as $_key => $_val) {
//                        $_val = addslashes($_val);
//                        if ( $key == "format") {
//                            $str[$key][] =  " orientation = " .$_val;
//                        } else if ( $key == "theme" ) {
//                            $str[$key][] =  " theme like '%".$_val."%'";
//                        } else {
//                            if ( $key == "medium") {
//                                $medium[] = $_val;
//                            }
//                            $str[$key][] = $key." = " .$_val;
//                        }
//                    }
//                    $sql_str[] = "(".implode(" or ", $str[$key]).")";
//                }
//                $sql_where = empty($sql_str) ? "" : ' and '.implode(' and ', $sql_str);
//                if ( isset( $param['PRICE'] ) && $param['PRICE'][1] >= $param['PRICE'][0] ) {
//                    $sql_where = $sql_where." and sell_price >= ".$param['PRICE'][0]." and sell_price <= ".$param['PRICE'][1];
//                } else {
//                    $sql_where = $sql_where;
//                }
//            }
//            $goods =  new BaseGoodsModel();
//            $offset = ( $page - 1 ) * $count;
//            $filter_res = $filter->getFilter($medium);
////            $res['keyword'] = array("keyword"=>$getPost['keyword']);
//            $res['filters'] = $filter_res;
////            if ( !empty($getPost['keyword']) ) {
////                $sql_where = $sql_where." and ( ( ab_base_goods.title like '%".$getPost['keyword']."%' or ab_base_goods.name like '%".$getPost['keyword']."%' or ab_base_goods.description like '%".$getPost['keyword']."%' or ab_base_author_goods.authorname like '%".$getPost['keyword']."%') or (SELECT GROUP_CONCAT(a.news_label_name) FROM ab_life_news_type a WHERE a.b_id = ab_base_goods.id AND a.type IN (3) ) LIKE '%".$getPost['keyword']."%' )";
////            }
//            if ( isset($getPost['onsale']) && $getPost['onsale'] == 1 ) {
//                $sql_where = $sql_where." and ab_base_goods.on_sale in (1)";
//            } else {
//                $sql_where = $sql_where." and ab_base_goods.on_sale in (0)";
//            }
//            if ( isset($getPost['author_id']) && !empty($getPost['author_id']) ) {
//                $sql_where = $sql_where." and a.id = ".(int)$getPost['author_id'];
//            }
//
//            $res['goods']['length'] = $goods->countShopGoods($sql_where);
//            $res['goods']['subdata'] = $goods->getShopGoodsByList($offset, $count, $sql_where);
//
//            $this->_render(self::Err_Suc, $res);
//        } catch (\Think\Exception $e) {
//            $this->myResponse(ApiConstModel::ERROR);
//        }

    }

}