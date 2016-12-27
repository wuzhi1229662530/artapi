<?php
namespace Home\Model;
use Home\Model\MyModel;
class BaseRecommendModel extends MyModel
{
    protected $tableName = 'base_recommend';


    public function getRecommendById( $id, $url ) {
        $sql = 'SELECT '.self::getUnixTime('create_time').',recommend_link link,recommend_title title,recommend_remark content,`author`,'.self::getImgUrl('recommend_pic').' FROM `ab_base_recommend` WHERE ( delete_status in (0) and featured_id in ('.$id.')  ) ORDER BY pxh asc ';
        $res = $this->query($sql);
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
                if (  is_numeric( $path[$str_num - 1] ) && $path[$str_num - 2] == "goods" ) {
                    $res[$k]['jump_url'] = "ShopGoodsID_".$path[$str_num - 1];
                    $res[$k]['type'] = 1;
                    $res[$k]['link'] = "";
                }
                if (  is_numeric( $path[$str_num - 1] ) &&  is_numeric( $path[$str_num - 2]  )&& in_array( $path[$str_num - 3 ], array("news", "lifestyle", "events", "knowledge")  ) ) {
                    $res[$k]['jump_url'] = $path[$str_num - 2] == "events" ? "ActivityID_".$path[$str_num - 1] : "LifeNewsID_".$path[$str_num - 1];
                    $res[$k]['type'] = 1;
                    $res[$k]['link'] = "";
                }
            }
        }
        return $res;
    }

}

