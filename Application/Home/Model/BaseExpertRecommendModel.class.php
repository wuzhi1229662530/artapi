<?php
namespace Home\Model;
use Home\Model\MyModel;
use Home\Model\BaseGoodsModel;
class BaseExpertRecommendModel extends MyModel
{
    protected $tableName = 'base_expert_recommend';

    public function getExpertCommend($type, $limit = 8) {
        $sql = "SELECT 
                    CONCAT('ShopGoodsID_',g.id) as jump_url,
                    g.title as goods_name,
                    ".self::getImgUrl('g.cover_path').",
                    g.longth goods_longth,
                    g.width goods_width,
                    g.sell_price goods_price,
                    c.name  authorname,
                    g.url,
                    g.id as goods_id
                FROM ab_base_expert_recommend er
                LEFT JOIN ab_base_goods g ON g.id=er.goodsid
                LEFT JOIN ab_base_author_goods a ON g.id = a.goods and a.delete_status=0 AND a.type = 1
                LEFT JOIN ab_base_author c ON a.author = c.id AND c.delete_status=0
                    WHERE
                    g.is_online in (1)
                    and g.inventory != 0
                    and g.delete_status=0
                    and er.delete_status in (0)
                    and er.moduletype in (".$type.")
                    ORDER BY g.sort asc, er.create_time DESC
                    LIMIT ".$limit;
        $res = $this->query($sql);
        $count = count($res);
        if ( $count < $limit ) {
            if (  $count > 1 ) {
                foreach ( $res as $k => $v ) {
                    $ids[] = $v['goods_id'];
                }
                $id = implode(",", $ids);
                $str = "AND t.id NOT IN (".$id.")";
            } else {
                $str = "";
            }
            $goodsModel = new BaseGoodsModel();
            $res2 = $goodsModel->getExpertRecommendGoods($str, ($limit-$count));
            return array_merge($res, $res2);
        } else {
            return $res;
        }

    }
 
}
