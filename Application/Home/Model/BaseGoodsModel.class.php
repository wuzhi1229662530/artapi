<?php
namespace Home\Model;
use Home\Model\MyModel;
use Home\Model\BaseAuthorGoodsModel;
class BaseGoodsModel extends MyModel
{
    protected $tableName = 'base_goods';

    //商品对应 栏目的名称
    const EXPERT_RECOMMENDATIONS        = 'EXPERT RECOMMENDATIONS';
    const TOP_CATEGORIES                = 'TOP CATEGORIES';
    const ART_LIFE_RECOMMENDATIONS      = 'ART LIFE RECOMMENDATIONS';
    const RECOMMENDED_FOR_YOU           = 'RECOMMENDED FOR YOU';

    // shop 站 view all post 对应 key
    const KEY_EXPERT_RECOMMENDATIONS    = "shop_expert_reconm";
    const KEY_TOP_CATEGORIES            = "shop_top_cate";
    const KEY_ART_LIFE_RECOMMENDATIONS  = "shop_art_life_recomm";
    const KEY_RECOMMENDED_FOR_YOU       = "shop_recomm_for_you";

    //BaseExpertRecommendModel调用取商品
    public function getExpertRecommendGoods($wherestr, $limit){
        $sql = "SELECT
                        CONCAT('ShopGoodsID_',t.id) as jump_url,
                        ".self::getImgUrl('t.cover_path').",
                        t.longth goods_longth,
                        t.width goods_width,
                        t.sell_price goods_price,
                        c.name as authorname,
                        t.url,
                        t.title as goods_name
                    FROM
                        ab_base_goods t
                    LEFT JOIN ab_base_author_goods a ON t.id = a.goods AND a.delete_status = 0 and a.type = 1
                    LEFT JOIN ab_base_author c ON a.author = c.id AND c.delete_status = 0
                    WHERE
                        t.is_online = 1
                    AND t.inventory in (1)
                    AND a.author IS NOT NULL
                    AND t.delete_status = 0
                    and t.on_sale = 0
                    ".$wherestr."
                    ORDER BY
                        t.sort asc,
                        t.online_time DESC
                    LIMIT ". $limit ;
        return $this->query($sql);
    }

    public function getBackGoods($offset = 0, $count = 4){
        $sql = "SELECT
                    g.id goods_id,
                    ".self::getImgUrl('g.cover_path').",
                    CONCAT('ShopGoodsID_', g.id) AS jump_url,
                    g.longth goods_longth,
                    g.width goods_width,
                    g.sell_price goods_price,
                    c.name authorname,
                    g.url,
                    g.title goods_name
                FROM ab_base_goods g
                    LEFT JOIN ab_base_author_goods a ON g.id = a.goods AND a.delete_status in (0) AND a.type in (1)
                    LEFT JOIN ab_base_author c ON a.author = c.id AND c.delete_status in (0)
                WHERE g.is_online in (1) AND  g.inventory in (1)
                    AND g.delete_status in (0) AND g.on_sale in (1)
                ORDER BY g.sort ASC
                LIMIT ".$count;
        return $this->query($sql);
    }

    //猜你喜欢
    public function getRecommendForUser($ip, $user_id) {
        $operClickModel = new OperationClickModel();
        $operInfo = $operClickModel->getInfo($ip, $user_id);
        $themenumConut = 0;
        foreach ( $operInfo as $k => $v ) {
            $themenumConut += $v['themenum'];
        }
        $arr = [];
        $ids = [];
        foreach ( $operInfo as $k => $v ) {
            $limit = round( ( $v['themenum']/$themenumConut ) * 8 );
            if ( $limit == 0 ) {
                continue;
            }
            $goodsRes = $operClickModel->getGoods($v['id'], $limit, $ids);
            $ids = [];
            foreach ( $goodsRes as $k1=> $v1 ) {
                $ids[] = $v['id'];
                $arr[] = $v1;
            }
        }
        if ( count($arr) < 8 ) {
            $arr1 = $operClickModel->getGoods("", 8 - count($arr), array());
            $res = array_merge($arr, $arr1);
        } else if ( count($arr) > 8 ) {
            $res = array_slice(0,8, $arr);
        } else {
            $res = $arr;
        }
        return $res;
    }

    public function getShopGoodsByList($offset = 0, $count = 10, $where = "") {
        $author = new BaseAuthorGoodsModel();
        $author_t_n = $author->getTableName();
        $goods_t_n = $this->getTableName();
        $res = $this->alias('a')->field(self::getImgUrl($goods_t_n.'.cover_path').',    '.$goods_t_n.'.id, '.$goods_t_n.'.inventory, '.$goods_t_n.'.weight, '.$goods_t_n.'.recommended, CONCAT("ShopGoodsID_", '.$this->getTableName().'.id) as jump_url, '.$this->getTableName().'.title as name, '.$this->getTableName().'.sell_price, CONCAT(round('.$this->getTableName().'.longth)'.',"×", round('.$this->getTableName().'.width) ) as size, a.name as authorname, (select d.dictname from sys_diction d where d.id = ab_base_goods.medium) as medium ,'.$goods_t_n.'.material')
            ->join("left join ab_base_author_goods ON ab_base_author_goods.goods = ab_base_goods.id and ab_base_author_goods.delete_status in (0)  and ab_base_author_goods.type in (1)")
            ->join(" left join ab_base_author a on ab_base_author_goods.author = a.id and ab_base_author_goods.delete_status=0 ")
            ->where($goods_t_n.'.is_online in (1) and '.$goods_t_n.'.inventory in (1) and ab_base_author_goods.author is not null and '.$goods_t_n.'.delete_status in (0)  ' .$where)
            ->limit($offset, $count)
            ->order($goods_t_n.'.sort asc,'.$goods_t_n.'.online_time desc')
            ->select();
        // echo $this->getLastSql();exit();
        return $res;
    }


}
