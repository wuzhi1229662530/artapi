<?php
namespace Home\Model;
use Home\Model\MyModel;
class BaseFeaturedModel extends MyModel
{
    protected $tableName = 'base_featured';
    // life首页 轮播推荐id
    const LIFE_LUNBO_TUIJIAN_ID              = 34;
    // life strending news 轮播推荐
    const LIFE_STRENDING_TUIJIAN_ID          = 36;
    // life  lifestyle  轮播推荐
    const LIFE_LIFESTYLE_TUIJIAN_ID          = 37;
    //大首页 大背景图
    const INDEX_TUIJIAN_ID                     = 31;
    const INDEX_SHOP_TUIJIAN_ID             =32;
    const INDEX_LIFE_TUIJIAN_ID             =33;
    //shop 站轮播图
    const SHOP_LUNBO_ID                     =60;



    public function getFeaturedById( $id, $url ) {
        if ( $id == 0) {
            return array();
        }
        $base_recom = D('BaseRecommend');
        $res = $base_recom->field(' FROM_UNIXTIME( UNIX_TIMESTAMP(create_time), "%M %e, %Y") create_time,recommend_link link, recommend_title title,recommend_remark content, author,'.$this->getImgUrl('recommend_pic'))
                    ->where('delete_status in (0) and featured_id in (%s) ',$id)
                    ->order('pxh asc')
                    ->select();
        if ( empty($res) ) {
            return null;
        }
        //推荐链接处理
        foreach ( $res as $k => $v ) {

            $arr[$k] = parse_url($v['link']);
            //改url 为站内地址 目前只改了 商品详情页和新闻详情页 还有 活动与展览详情页
            //type = 1 走站内
            //type = 2 走外链
            $res[$k]['jump_url'] = "";
            $res[$k]['type'] = 2;
            if ( preg_match("/".$url."/", $v['link']) && $arr[$k]['path'] != "" ) {
                $path = explode('.', $arr[$k]['path']);
                $path = explode('/', $path[0]);
                $str_num = count($path);
                //
                if (  is_numeric( $path[$str_num - 1] ) && $path[$str_num - 2] == "goods" ) {
                    $res[$k]['jump_url'] = "ShopGoodsID_".$path[$str_num - 1];
                    $res[$k]['type'] = 1;
                    $res[$k]['link'] = "";
                }
                if (  is_numeric( $path[$str_num - 1] ) &&  is_numeric( $path[$str_num - 2]  )&& in_array( $path[$str_num - 3 ], array("news", "lifestyle", "events", "knowledge")  ) ) {
                    $res[$k]['jump_url'] = $path[$str_num - 2] == "events" ? "ActivityID_" : "LifeNewsID_";
                    $res[$k]['jump_url'] = $res[$k]['jump_url'].$path[$str_num - 1];
                    $res[$k]['type'] = 1;
                    $res[$k]['link'] = "";
                }
            }
        }
        return $res;
    }
}


