<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Db;



/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：财务
 */
class Statis extends Init
{

    function _initialize()
    {
        parent::_initialize();
    }
    /**
     * @auth YW
     * @date 2018.12.07
     * @purpose 财务明细
     * @return void
     */
    public function index()
    {
        return view();
    }

}