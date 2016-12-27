<?php
namespace Home\Model;
use Home\Model\MyModel;
class BaseAuthorModel extends MyModel
{
    protected $tableName = 'base_author';

    public function getGoodsAuthor($id) {
        $t_n = $this->getTableName();
        $res = $this->field("id, name, introduction, ".$this->getImgUrl('author_img'))
                    ->where("delete_status in (0) and id = %d",$id)
                    ->find();
        return $res;
    }
}