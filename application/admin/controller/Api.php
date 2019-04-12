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

class Api extends Init
{
    function _initialize()
    {
        parent::_initialize();
    }
    /**
     * @auth YW
     * @date 2018.03.06
     * @purpose 接口信息展示
     * @return void
     */
    public function index()
    {
        $api['alipay'] = Db::table($this->table)->where('type','alipay')->find();
        $api['wxpay'] = Db::table($this->table)->where('type','wxpay')->find();
        $api['sms'] = Db::table($this->table)->where('type','sms')->find();
        $this->assign('list',$api);
        return view();
    }

    /**
     * @auth YW
     * @date 2018.03.06
     * @purpose
     * @return void
     */
    public function _before_update(&$list)
    {
        if ($this->request->isPost())
        {
            $list['edit_time'] = time();
        }
    }
}