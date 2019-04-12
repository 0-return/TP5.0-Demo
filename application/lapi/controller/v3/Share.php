<?php
namespace app\lapi\controller\v3;
use app\common\controller\Common;
use think\Request;
use think\Db;
/**
 * auth YW
 * note 分享版块
 * date 2018-08-06
 */
class Share extends Index
{

    /**
     * auth YW
     * note 初始化
     * date 2018-08-06
     */
    public function _initialize()
    {
        parent::_init();

    }


    /**
     * auth YW
     * note 空操作
     * date 2018-08-06
     */
    public function _empty(){
        self::returnMsg('10107','操作不合法');
    }
}