<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Db;
/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：地区管理
 */
class Region extends Init{

    function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {

    }

    public function getRegion()
    {
        $post = $_REQUEST;
        $map['parentId'] = isset($post['parentId']) && empty($post['parentId'])?$post['parentId']:'1';
        $res = $this->_list('',$map,'','id','desc',100);
        echoMsg('10000',$this->message['get_success'],$res->items());
    }
}