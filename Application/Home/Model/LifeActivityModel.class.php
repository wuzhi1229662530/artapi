<?php
namespace Home\Model;
use Home\Model\MyModel;
class LifeActivityModel extends MyModel
{
    protected $tableName = 'life_activity';
    
    public function getActivity($page, $count, $where) {
        $offset = ($page - 1) * $count;
        $res = $this->field("create_time as time, case when a_video_code != '' then 2 else 1 end as type , CONCAT('ActivityID_', id) as jump_url,a_address as address, a_video_code as videocode,a_area as area, a_theme, a_title as title, CONCAT(FROM_UNIXTIME( UNIX_TIMESTAMP(a_start_time), \"%M %e\" ), '-', FROM_UNIXTIME(UNIX_TIMESTAMP(a_end_time), \"%M %e, %Y\")) as create_time, ".$this->getImgUrl('a_img'))
                    ->where("delete_status in (0) ".$where)
                    ->limit($offset, $count)
                    ->order("a_sort desc")
                    ->select();

        return $res;
    }
 public function _getActivity($page, $count, $where) {
    $offset = ($page - 1) * $count;
    $sql  = "SELECT
                id,
                ".$this->getImgUrl("a_img").",
                a_theme,
                concat('ActivityID_',id) jump_url,
                case when a_video_code != '' then 2 else 1 end as type ,
                a_title title,
                a_video_code videocode,
                concat(                    FROM_UNIXTIME(
                    UNIX_TIMESTAMP(s.a_start_time),
                    '%M %e'
                ),'-',FROM_UNIXTIME(
                    UNIX_TIMESTAMP(s.a_end_time),
                    '%M %e, %Y'
                ) ) create_time,
                a_address address,
                s.a_area area
            FROM
                ab_life_activity s
            WHERE
                s.delete_status in (0)
            ".$where."
            ORDER BY
                s.a_sort ASC,
                s.a_start_time DESC
            LIMIT ".$offset.','.$count;

        return $this->query($sql);
    }

    public function getLifeActivity() {
        $sql  = "SELECT
                    id,
                    ".$this->getImgUrl("a_img").",
                    a_theme,
                    concat('ActivityID_',id) jump_url,
                    case when a_video_code != '' then 2 else 1 end as type ,
                    a_title title,
                    a_video_code videocode,
                    concat(                    FROM_UNIXTIME(
                        UNIX_TIMESTAMP(s.a_start_time),
                        '%M %e'
                    ),'-',FROM_UNIXTIME(
                        UNIX_TIMESTAMP(s.a_end_time),
                        '%M %e, %Y'
                    ) ) create_time,
                    a_address address,
                    s.a_area area
                FROM
                    ab_life_activity s
                WHERE
                    s.delete_status = 0
                AND s.a_top = 1
                ORDER BY
                    s.a_sort ASC,
                    s.a_start_time DESC
                LIMIT 1,
                 3";

        return $this->query($sql);
    }


    public function countActivity($where) {
        $offset = ($page - 1) * $count;
        $res = $this->field('id')
                    ->where("delete_status in (0) ".$where)
                    ->select();
        
        return count($res);;
    }

    public function getActivityById($where) {
        $res = $this->field("id, a_content as news_content,a_abst, a_video_code as videocode,museum_times, ticket_prices,location,a_theme, a_title as title,a_address as address,a_area as area, CONCAT(FROM_UNIXTIME(UNIX_TIMESTAMP(a_start_time), \"%M %e\"), '-', FROM_UNIXTIME(UNIX_TIMESTAMP(a_end_time), \"%M %e, %Y\")) as create_time, ".$this->getImgUrl('a_img'))
                    ->where("delete_status in (0) ".$where)
                    ->select();
        return $res;
    }
}
