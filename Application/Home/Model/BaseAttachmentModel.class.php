<?php
namespace Home\Model;
use Home\Model\MyModel;
class BaseAttachmentModel extends MyModel
{
    protected $tableName = 'base_attachment';

    public function getAtta( $where, $start, $offset ) {
        $res = $this->field($this->getImgUrl('attachment_path') )->where($where)->limit($start, $offset)->select();
        return $res;
    }
}
