<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Db;

/**
 * Created by PhpStorm.
 * User: EVOL
 * Date: 2018/10/27
 * Time: 17:11
 */

class Website extends Init
{
    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'].'config_system';
    }

    public function index()
    {

        $res = $this->obj[1]->table($this->table)->find();
        $this->assign('vo',$res);
        return view();
    }

}