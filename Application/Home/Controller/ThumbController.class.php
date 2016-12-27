<?php
namespace Home\Controller;
use Home\Controller\MyController;
class ThumbController extends MyController {

    public function _empty() {
        $this->errRender();
    }

    /**
     * 取缩略图
     */
    public function getImg()
    {
        echo  "getImg";
    }
 

}