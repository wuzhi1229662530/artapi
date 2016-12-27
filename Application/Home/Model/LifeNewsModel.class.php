<?php
namespace Home\Model;
use Home\Model\MyModel;

class LifeNewsModel extends MyModel
{
    protected $tableName = 'life_news';

    // column对应新闻名称
    const TRENDINGNEWS                  = 'TRENDING NEWS';
    const EVENTS_AND_EXHIBITIONS        = 'EVENTS AND EXHIBITIONS';
    const LIFESTYLE                     = 'LIFESTYLE';
    const KNOWLEDGE                     = 'KNOWLEDGE';

    // viewAll post对应的Key值
    const KEY_STRENDINGNEWS             = "life_news";
    const KEY_EVENTS_AND_EXHIBITIONS    = "life_events";
    const KEY_LIFESTYLE                 = "life_style";
    const KEY_KNOWLEDGE                 = "life_knowledge";

    //shop首页取新闻
    public function getNewsFromShop(){
        $sql = "SELECT s.id,
                     CONCAT('LifeNewsID_', s.id) jump_url,
                     s.news_title,
                     ".self::getImgUrl('s.cover_imgPath', 'img_url').",
                     ".self::getUnixTime('s.create_time').",
                     s.news_sources,
                     s.news_type,
                     s.news_desc,
                     s.author news_author,
                     s.column
                from ab_life_news s
                where (
                        s.news_examine in (1) or
                        (
                        s.news_examine in (2) and
                         DATE_FORMAT(now(),'%Y-%m-%d %H:%i:%s')
                         >=
                         DATE_FORMAT(s.issuance_time,'%Y-%m-%d %H:%i:%s')
                        )
                    ) and s.delete_status in (0)
                and s.column=27
                            and s.is_highlight in (1)
                order by s.pxh asc,s.create_time desc
                limit 0,6 ";
        return $this->query($sql);
    }

}


