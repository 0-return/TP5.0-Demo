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

class Mail extends Init
{
    function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        $res = Db::table($this->table)->find();
        $this->assign('vo',$res);
        return view();
    }
}