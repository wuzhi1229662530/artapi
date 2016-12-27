<?php
namespace Home\Controller;
use Home\Controller\MyController;
class LifeController extends MyController {
    public function _empty() {
        $this->errRender();
    }

    /**
     *  life首页数据
     */
    public function index()
    {
       try{
            if(IS_GET) {
                
                $life_news = D('LifeNews');
                $life_column = D('LifeColumn');
                //侧栏目录
                $help_cate = D('BaseHelpCategory');
                $activity = D("LifeActivity");
                abCache();
                if ( S('cehua') ) {
                    $help_column = S("cehua");
                } else {
                    $help_cate = D('BaseHelpCategory');
                    $help_column = $help_cate->getTree();
                    S("cehua", $help_column);
                }
                if ( S('life_cehua') ) {
                    $column_res = S("life_cehua");
                } else {
                    $column_res = $life_column->getLifeColumn();
                    S("life_cehua", $column_res);
                }
                $column_res_2 = array_merge($column_res, $help_column );
                //life 首页  TRENDING NEWS           取3条 推荐
                $trend_res  = $life_news->getTrendingNews();
                //life 首页  EVENTS AND EXHIBITIONS  取3条 推荐
                $activity_where = " and a_top = 1 ";
                $events_exhibiions  = $activity->getLifeActivity();
                //life 首页  KNOWLEDGE               
                
                $knowledge = $life_column->field(" CONCAT('LifeNewsID_',ab_life_news.id) as jump_url, ab_life_column.column_name as title,".$life_news->getImgUrl("ab_life_news.cover_imgPath"))
                                        ->join(" ab_life_news on ab_life_column.id = ab_life_news.column")
                                         ->where("ab_life_column.column_parent in (26) and ab_life_column.is_Enable in (0) and ab_life_news.delete_status in (0) and ab_life_column.delete_status in (0)")
                                         ->order(" ab_life_column.sort desc")
                                         ->limit(0,6)
                                         ->select();
                //LIFE 首页  LIFESTYLE                取3条 推荐
                $style_res  = $life_news->getNews(0, 3, 1, $life_news::LIFESTYLE);
                
                //life 首页  轮播推荐
                
                if ( S("life_lunbo")) {
                    $lunbo = S("life_lunbo");
                } else {
                    $life_featured = D("BaseFeatured");
                    $lunbo      = $life_featured->getFeaturedById($life_featured::LIFE_LUNBO_TUIJIAN_ID,C("__AB_WEB_URL__"));
                    
                    S("life_lunbo", $lunbo);
                }


                $arr1 = array('column'=>$column_res_2,"lunbo"=>$lunbo, "trend_news"=>$trend_res , 'events_exhibiions'=>$events_exhibiions , 'knowledge'=>$knowledge , "life_style"=> $style_res);
                $arr1['banner'] = array('jump_url'    => "LifeNewsID_1",
                                        "jump_type"    => "0",
                                        "img_url"     => "http://img5.duitang.com/uploads/item/201412/29/20141229012311_JnJ4m.jpeg",
                                        "title"       => "This is banner title ",
                                        "desc"        => "This is banner desc ",
                                        "create_time" => "March 03,2016"
                                );
                $this->data = $arr1;
                $this->error = self::Err_Suc;
                $this->desc = self::Desc_Suc;
            } else {
                $this->error = self::Err_Not_Legal;
                $this->desc = self::Desc_Not_Legal;
            }
            $this->render();
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }

    }

    public function lifeActivity(){
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            
            if ( !isset( $getPost['page'] ) || empty( $getPost['page'] ) || !isset( $getPost['count'] ) || empty( $getPost['count'] || !isset( $getPost['keyword'] )  || !isset( $getPost['time'] )  ) ) {
                $this->_render(self::Err_No_Data);
            }
            $activity = D("LifeActivity");
            $activity_where =   !empty($getPost['keyword']) ? " and a_area = '".$getPost['keyword']."'" : "";
            if ( $getPost['time'] == 1 ) {
                //当前活动s.a_start_time  s.a_end_time
                $activity_where .= " and  NOW() >= a_start_time and NOW() <=  a_end_time";
            } else if (  $getPost['time'] == 2 ) {
                $activity_where .= " and  a_start_time > NOW()  ";
            }

            $data['life_news']  = $activity->_getActivity($getPost['page'], $getPost['count'], $activity_where);
            $data['count'] = $activity->countActivity($activity_where);

            $data['keyword'] = $getPost['keyword'];
            $area = array( "OR", "UT", "WY", "NE", "KS", "OK", "MN", "IA", "AR", "WI", "IL", "MI", "IN","OH", "KY", "TN", "MS", "GA", "SC", "WV", "PA", "VT", "NH", "MA", "CT", "RI", "NJ", "DE", "MD", "NY", "WA", "ID", "CA", "NV", "MT", "AZ", "NM", "CO", "ND", "SD", "LA", "MO", "AL", "FL", "AK", "HI", "NC","VA", "ME", "TX");
            sort($area);
            $data["area"] = $area;
            $this->_render(self::Err_Suc, $data);
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
            
    }


    /**
     *  life站所有viewall
     */

    public function lifeViewAll() {
        try{
            if ( IS_POST ) {
                $post_data =   file_get_contents('php://input','r');
                if ( $post_data ) {
                    $getPost = json_decode($post_data, 1);
                    $life_news = D('LifeNews');
                    $arr_life_key = $life_news->getPostLifeKey();
                    if ( !empty( $getPost['count'] ) && !empty( $getPost['page'] ) &&  !empty( $getPost['key'] ) && in_array( $getPost['key'], $arr_life_key ) ) {
                        $offset = ($getPost['page'] - 1) * $getPost['count'];
                        $life_column = D('LifeColumn');
                        abCache();
                        $life_featured = D("BaseFeatured");
                        switch ( $getPost['key'] ) {
                            case $life_news::KEY_STRENDINGNEWS :
                                $id = $life_featured::LIFE_STRENDING_TUIJIAN_ID;
                                $type = $life_news::TRENDINGNEWS;
                                if ( S("news_lunbo") ) {
                                    $lunbo = S("news_lunbo");
                                } else {
                                    $lunbo = $life_featured->getFeaturedById($life_featured::LIFE_STRENDING_TUIJIAN_ID,C("__AB_WEB_URL__"));
                                    S("news_lunbo", $lunbo);
                                }
                                $life_views_res  = $life_news->getNews($offset, $getPost['count'], 0, $type);
                                $res1 = array( 'life_news'=>$life_views_res ,'lunbo'=>$lunbo );
                                $this->_render( self::Err_Suc , $res1);
                                break;
                            case $life_news::KEY_EVENTS_AND_EXHIBITIONS :
                                $activity = D("LifeActivity");
                                $activity_where = isset($getPost['keyword']) && !empty($getPost['keyword']) ? " and a_area = '".$getPost['keyword']."'" : "";
                                $events_exhibiions  = $activity->_getActivity($getPost['page'], $getPost['count'], $activity_where);
                                $event_count = $activity->countActivity($activity_where);
                                $area = array( "OR", "UT", "WY", "NE", "KS", "OK", "MN", "IA", "AR", "WI", "IL", "MI", "IN","OH", "KY", "TN", "MS", "GA", "SC", "WV", "PA", "VT", "NH", "MA", "CT", "RI", "NJ", "DE", "MD", "NY", "WA", "ID", "CA", "NV", "MT", "AZ", "NM", "CO", "ND", "SD", "LA", "MO", "AL", "FL", "AK", "HI", "NC","VA", "ME", "TX");
                                sort($area);
                                $data = array( 'life_news'=>$events_exhibiions, "area"=>$area,"count"=>$event_count, "keyWord"=>$getPost['keyword']);
                                $this->_render( self::Err_Suc , $data);
                                break;
                            case $life_news::KEY_KNOWLEDGE :
                                $res3['life_news']= $life_column->field(" CONCAT('LifeNewsID_',ab_life_news.id) as jump_url, ab_life_column.column_name as title,".$life_news->getImgUrl("ab_life_news.cover_imgPath"))
                                                        ->join(" ab_life_news on ab_life_column.id = ab_life_news.column")
                                                         ->where("ab_life_column.column_parent = 26 and ab_life_column.is_Enable = 0 and ab_life_news.delete_status = 0 and ab_life_column.delete_status = 0")
                                                         ->order(" ab_life_column.sort desc")
                                                         ->limit($offset, $getPost['count'])
                                                         ->select();
                                $this->_render( self::Err_Suc , $res3);
                                break;
                            case $life_news::KEY_LIFESTYLE : 
                                $type = $life_news::LIFESTYLE;
                                if ( S("lifestyle_lunbo") ) {
                                    $lunbo = S("lifestyle_lunbo");
                                } else {
                                    $lunbo = $life_featured->getFeaturedById($life_featured::LIFE_LIFESTYLE_TUIJIAN_ID);
                                    S("lifestyle_lunbo", $lunbo);
                                }
                                $life_views_res  = $life_news->getNews($offset, $getPost['count'], 0, $type);
                                $res4 = array( 'life_news'=>$life_views_res, 'lunbo'=>$lunbo );
                                $this->_render( self::Err_Suc , $res4);
                                break;
                        }

                    } else {
                        $this->error = self::Err_No_Data;
                        $this->desc = self::Desc_No_Data;
                    }
                }
            } else {
                $this->error = self::Err_Not_Legal;
                $this->desc = self::Desc_Not_Legal;
            }
            $this->render();
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
            
    }

    /**
     * 新闻详情（不包括活动与展览id）
     */
    public function LifeNewsID(){
        try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            
            if ( !isset( $getPost['LifeNewsID'] ) || empty( $getPost['LifeNewsID'] ) ) {
                $this->_render(self::Err_No_Data);
            }
            $life_news = D('LifeNews');
            
            $res = $life_news->getNewsById( $getPost['LifeNewsID'] );

            if ( empty($res)  ) {
                $this->_render(self::Err_No_Data);
            }
 
            $where = "er.moduletype in (1, 2) ";
            $life_column = D("LifeColumn");
            $knowledge_id  = $life_column->getLifeColumn(26);
            foreach ($knowledge_id as $key => $value ) {

                if (  $res[0]['column'] ==  $value['id']    ) {
                    $where = " ( (er.moduletype in (1) and er.news_column in (".$value['id'].")) or (er.moduletype in (2) and er.news_column in (".$value['id'].") ) )";
                    break;
                }
            }
            
            $expert_recom = D("BaseExpertRecommend");
            if ( $res[0]['column'] != 27  ) {
                $res[0]['expert_res'] =  $expert_recom->getExpertCommendByNews($where);
            }
            
             

             

            $json_text = json_decode($res[0]['app_text'], true);
            
            //去掉 &nbsp
            $json_text['html'] =  str_replace("&nbsp", "", $json_text['html'] ) ;
            //因为有多余的\r\n\t等字符 首先全部替换 为_w_
            $str = str_replace(array("\r", "\n", "\t"), "_w_",  $json_text['html']);
            //然后再将_w_全都替换为 一个\r\n
            $str2 = preg_replace("/(_w_)+/",'\r\n',$str);
            //赋值到html
            $json_text['html'] = $str2;
            // //根据 \r\n 分组，目的 当news_desc为空时 去第一段的前100个字符
            // $str3 = explode("\\r\\n", $str2);
            // //把第一段里面出现的img去掉    
            // $str4 = preg_replace("/img_.*/",'',$str3[0]);
            // //去掉 a 连接 替换成文本 
            // preg_match_all('/a_.*/',$str4,$arr);
            // //判断第一段有没有100个字符
            // $desc = strlen($str4) > 100 ? mb_substr($str4,0,100,'utf8')."..." : $str4;
            //判断赋值
            $res[0]['news_desc'] = empty($res[0]['news_desc']) ? $res[0]['news_title'] : $res[0]['news_desc'];
            $res[0]['web_url'] = AB_FRONTED.'life/news/'.$res[0]['column'].'/'.$res[0]['id'].'.html';
            $res[0]['app_text'] = $json_text;
            $this->_render(self::Err_Suc, $res[0]);
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
            
        // print_r($res[0]);
        // $str = preg_replace("#<*></*>", "/r/n", $res[0]['news_content']);
        //echo $str;
        //echo $res[0]['news_content'];
        //preg_match_all('/<img.*?src="(.*?)".*?\>/is',$res[0]['news_content'],$imgs);
        //       $str = $res[0]['news_content'];
        // if ( $imgs ) {
        //     foreach ($imgs[0] as $k => $v ) {
        //         $str = str_replace($imgs[0][$k], "/r/n IMG".$k." /r/n" , $str);
        //     }
        // }
        // //echo $str;

        //echo preg_replace("/\<(?!a|\/a|br\/|br|\/p).(.*?)\>/is",'',$str);
    }
    /**
     * 活动与展览id
     */
    public function lifeActivityID() {
         try{
            if ( !IS_POST ) {
                $this->_render(self::Err_Not_Legal);
            }
            $post_data = file_get_contents('php://input','r');
            if ( !$post_data ) {
                $this->_render(self::Err_No_Data);
            }
            $getPost = json_decode($post_data, 1);
            if ( !isset( $getPost['ActivityID'] ) || empty( $getPost['ActivityID'] ) ) {
                $this->_render(self::Err_No_Data);
            }
            $activity = D("LifeActivity");
            $activity_where =   " and id = ".$getPost['ActivityID'];
            $events_exhibiions  = $activity->getActivityById( $activity_where );
            if ( empty( $events_exhibiions ) ) {
                $this->_render(self::Err_No_Data);
            }
            $expert_recom = D("BaseExpertRecommend");
            $events_exhibiions = $events_exhibiions[0];
            $where = "er.moduletype in (1, 2) ";
            $events_exhibiions['expert_res'] = $expert_recom->getExpertCommendByNews($where);
            $events_exhibiions['news_desc'] = empty($events_exhibiions['a_abst']) ? $events_exhibiions['title'] : $events_exhibiions['a_abst'];
            $events_exhibiions['web_url'] = AB_FRONTED.'life/events/25/'.$events_exhibiions['id'].'.html';
            $this->_render(self::Err_Suc, $events_exhibiions);
        } catch (\Think\Exception $e) {
            $this->_render(self::Err_Failure);
        }
    
    }
}