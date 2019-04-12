<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use app\common\controller\Common;
use think\Db;

/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：退款处理
 */
class Refund extends Init
{
    public $pay_config;

    function _initialize()
    {
        $this->table = $this->config['prefix'].'aftersale';
        $where['status'] = '1';
        $where['type'] = 'alipay';
        $this->pay_config = $this->obj->table($this->config['prefix'].'payapi')->where($where)->find();
    }


}
