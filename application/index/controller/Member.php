<?php
namespace app\index\controller;
use app\index\common\controller\Init;
use think\Cookie;
use think\Db;
use think\Session;

/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：用户中心
 */
class Member extends Init
{
    /**
     * @auth YW
     * @date 2017.12.2
     * @purpose 初始化
     * @return void
     */
    public function _initialize()
    {
        parent::_initialize();
    }


    /**
     * @auth YW
     * @date 2017.12.2
     * @purpose
     * @return void
     */
    protected function _filter(&$map)
    {
        $map['active_status'] = '1';
        $map['user_status'] = '1';
        $map['status'] = '1';
    }

    /**
     * @auth YW
     * @date 2018.03.06
     * @purpose 用户管理
     * @return void
     */
    public function index()
    {
        $get = input('get.');
        $where['id'] = $get['id'];
        if (method_exists($this, '_filter')) {
            $this->_filter($where);
        }
        $res = Db::table($this->table)->field('id,pid,username,phone,vip,face')->where($where)->find();unset($where);
        $where['title_en'] = $res['vip'];
        $res['vip_cn'] = Db::table($this->table.'cate')->where($where)->value('title');
        $res['web_url'] = $this->config['web_url'];
        $this->assign('vo',$res);
        return view();
    }

    /**
     * @auth YW
     * @date 2018.12.13
     * @purpose 注销登录
     * @return bool
     */
    function logout(){
        $data = array(
            'last_ip' => getIP(),
            'last_time' => time(),
        );
        $where['id'] = getUser($this->obj->module())['id'];
        $res = Db::table($this->table)->where($where)->update($data);
        if ($res)
        {
            Cookie::delete('info',$this->obj->module().'_');
            Session::delete('token',$this->obj->module().'_');
            echoMsg('10000','退出成功',url($this->obj->module().'/index/index'));
        }
    }



}