<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Db;
use think\Cookie;
/**
 * Created by PhpStorm.
 * User: EVOL
 * Date: 2018/10/27
 * Time: 17:11
 */

class Taskbar extends Init
{
    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'].'taskbar';
    }
    /**
     * @auth YW
     * @date 2018.10.31
     * @purpose 常用设置
     * @return void
     */
    public function index()
    {
        $user = json_decode(Cookie::get($this->request->module().'_info'),1);

        $where['uid'] = $user['id'];
        $ids = $this->obj[1]->table($this->table)->where($where)->find();
        $ids['acids'] = explode(',',$ids['acids']);
        unset($where);
        $where['ismenu'] = '1';
        $where['pid'] = array('neq',0);
        $res = $this->obj[1]->table($this->config['prefix'].'admin_auth')->where($where)->select();
        foreach ($res as $key => $value) {
            if (in_array($value['id'],$ids['acids']))
            {
                $res[$key]['mark'] = '1';
            }else{
                $res[$key]['mark'] = '0';
            }
        }
        $this->assign('vo',$ids);
        $this->assign('list',$res);
        return view();
    }
    /**
     * @auth YW
     * @date 2018.10.31
     * @purpose 更新
     * @return void
     */
    public function _before_update()
    {
        if ($this->request->isPost()) {
            $_POST['acids'] = implode(',', $_POST['acids']);
        }
    }
}