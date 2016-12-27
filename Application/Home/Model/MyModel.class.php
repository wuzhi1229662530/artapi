<?php
namespace Home\Model;
use Think\Model;
class MyModel extends Model
{

    public $filter_type = "strip_tags";
    //数据库create_time 格式转换
    // $tableName.create_time : 2016-03-09
    // 转换后     March 03,2016
    public static function getUnixTime($field, $param="create_time"){
        return ' FROM_UNIXTIME(UNIX_TIMESTAMP('.$field.'),"%M %e, %Y") as '.$param;
    }
    /**
     * 前台通用取图片
     */
    public static function getImgUrl($imgurl, $param = "goods_img_url") {
        return "CONCAT('".AB_BACKEND_IMG_URL."',CONCAT(SUBSTRING_INDEX(".$imgurl.",'.',1),'_s',SUBSTRING(".$imgurl.",LENGTH(SUBSTRING_INDEX(".$imgurl.",'.',1))+1,LENGTH(".$imgurl.")))) as ".$param;
        //return   " CONCAT('http://120.24.240.78:8080/artbean',".$imgurl.") as ".$param;
    }
    /**
     * 前台取life trending news 推荐 图片
     */
    public static function getLifeNewsRecommendImgUrl($imgurl, $param = "img_url") {
        return "WHEN s.top_imgPath IS NOT NULL or s.top_imgPath = ''
    THEN CONCAT('".AB_BACKEND_IMG_URL."',CONCAT(SUBSTRING_INDEX(s.cover_imgPath,'.',1),'_s',SUBSTRING(s.cover_imgPath,LENGTH(SUBSTRING_INDEX(s.cover_imgPath,'.',1))+1,LENGTH(s.cover_imgPath)))) 
     ELSE CONCAT('".AB_BACKEND_IMG_URL."',CONCAT(SUBSTRING_INDEX(s.top_imgPath,'.',1),'_s',SUBSTRING(s.top_imgPath,,LENGTH(SUBSTRING_INDEX(s.top_imgPath,'.',1))+1,LENGTH(s.top_imgPath)))) END ".$param;
        //return   " CONCAT('http://120.24.240.78:8080/artbean',".$imgurl.") as ".$param;
    }
    /**
     * 银行logo
     */
    public static function getCardLogo($imgurl, $param = "img_url") {
        return "CONCAT('".AB_FRONTED_IMG_URL."',".$imgurl.") as ".$param;
        //return   " CONCAT('http://120.24.240.78:8080/artbean',".$imgurl.") as ".$param;
    }

    /**
     * 取画框图片
     */
    public static function getRahmenImgUrl($imgurl, $param = "img_url") {
        return "CONCAT('".AB_BACKEND_IMG_URL."',".$imgurl.") as ".$param;
        //return   " CONCAT('http://120.24.240.78:8080/artbean',".$imgurl.") as ".$param;
    }
}


