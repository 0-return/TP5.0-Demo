<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Cache;
use think\Session;
use think\Db;
use think\Cookie;

/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：用户中心
 */
class User extends Init
{

    private $web_info;
    /**
     * @auth YW
     * @date 2017.12.2
     * @purpose 初始化
     * @return void
     */
    public function _initialize()
    {

        parent::_init();
        $this->table = $this->config['prefix'].'admin_user';

        $where['id'] = getUser($this->request->module())['id'];
        $user = $this->obj[1]->table(strtolower($this->table))->where($where)->field('num,preip,pretime')->find();
        $where['id'] = getUser($this->request->module())['id'];
        $this->web_info['name'] = php_uname('n');               //计算机名称
        $this->web_info['os'] = php_uname('s');                 //操作系统
        $this->web_info['ip'] = getIp();                        //ip地址
        $this->web_info['host'] = $_SERVER['HTTP_HOST'];        //域名
        $this->web_info['port'] = $_SERVER['SERVER_PORT'];      //端口
        $this->web_info['php_version'] = 'PHP '.PHP_VERSION ;   //php版本
        $this->web_info['root'] = $_SERVER['DOCUMENT_ROOT'];    //根目录
        $this->web_info['session_id'] = empty(Session::get()['id'])?'0':Session::get()['id'];
        $this->web_info['session_num'] = count(Session::get());
        $this->web_info['time'] = date('Y-m-d H:i:s',time());
        $this->web_info['agent'] = $_SERVER['HTTP_USER_AGENT'];
        $this->web_info = array_merge($this->web_info,$this->config);
        $this->web_info = array_merge($this->web_info,$user);
    }



    /**
     * @auth YW
     * @date 2017.12.2
     * @purpose 管理中心
     * @return void
     */
    public function welcome(){

        $map = '';
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        return view();
    }

    /**
     * @auth YW
     * @date 2018.11.18
     * @purpose 首页
     * @return void
     */
    public function info()
    {
        $this->assign('vo',$this->web_info);
        return view();
    }

    /**
     * @auth YW
     * @date 2018.03.06
     * @purpose 用户管理
     * @return void
     */
    public function index()
    {
        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $map['status'] = array('gt',-1);
        $where['where'] = $map;
        $this->_list('',$where);
        return view();
    }


    /**
     * @auth YW
     * @date 2017.12.2
     * @purpose
     * @return void
     */
    protected function _filter(&$map)
    {
        $top_menu = $this->takMenu();
        $this->assign('top_menu',$top_menu);

        $left_menu = $this->getMenu();

        $left_menu = self::editMenu($left_menu);
        $menu_first = self::findMenu($left_menu,1);
        $menu_child = self::findMenu($left_menu,2);

        $this->assign('menu_first',$menu_first);
        $this->assign('menu_child',$menu_child);
        $this->assign('vo',$this->config);
        $this->checkSearch($map);
    }

    public function _after_list(&$list)
    {
        foreach ($list as $key => $value)
        {
            $where['id'] = $value['roleid'];
            $role = $this->obj[1]->table($this->config['prefix'].'admin_role')->where($where)->field('name')->find();
            $list[$key]['roleid'] = $role['name'];
        }
    }


    /**
     * @auth YW
     * @date 2018.03.06
     * @purpose 添加用户
     * @return void
     */
    public function _before_add(&$list)
    {
        if ($this->request->isPost())
        {
            $data = input('post.');
            if ($this->request->isAjax())
            {
                $this->checkUnique('username',$data['username']);
            }
            $list['add_time'] = time();
            $list['password'] = md5($list['password']);
            $list['num'] = '0';

        }else{

            $list = $this->obj[1]->table($this->config['prefix'].'admin_role')->where('status','>','0')->select();

            $this->assign('list', $list);
        }
    }
    /**
     * @auth YW
     * @date 2018.03.06
     * @purpose 修改
     * @return void
     */
    public function _after_edit(&$list)
    {
        $res = $this->obj[1]->table($this->config['prefix'].'admin_role')->select();
        foreach ($res as $ky => $vl)
        {
            $res[$ky]['mark'] = $list['roleid'] == $vl['id']?'selected':'';
        }
        $this->assign('list',$res);
    }

    /**
     * @auth YW
     * @date 2018.03.06
     * @purpose 修改
     * @return void
     */
    public function _before_update()
    {
        if (!empty($_POST['password']))
        {
            $_POST['password'] = md5($_POST['password']);
        }else{
            unset($_POST['password']);
        }
    }
    /**
     * @auth YW
     * @date 2018.10.26
     * @purpose 快捷菜单
     * @return void
     */
    public function takMenu()
    {
        $user = json_decode(Cookie::get($this->request->module().'_info'),1);
        $where['uid'] = $user['id'];
        $ids = $this->obj[1]->table($this->config['prefix'].'taskbar')->where($where)->value('acids');unset($where);

        $ids_arr = explode(',',$ids);
        $data = '';
        foreach ($ids_arr as $key => $value)
        {
            $where['id'] = $value;
            $where['ismenu'] = '1';
            $where['pid'] = array('neq','0');
            $res = $this->obj[1]->table($this->config['prefix'].'admin_auth')->where($where)->order('sort asc')->find();
            if ($res)
            {
                $data[] = $res;
            }
        }
        return $data;
    }
    /**
     * @auth YW
     * @date 2018.10.26
     * @purpose 左边顶级菜单
     * @return void
     */
    public function getMenu()
    {

        if (Cache::has('backstage'))
        {
            $data = json_decode(Cache::get('backstage'),1);

        }else{
            $user = json_decode(Cookie::get($this->request->module().'_info'),1);
            $ids = getFields($this->obj[1]->table($this->config['prefix'].'admin_role'),"id = {$user['roleid']}",array('fields'=>'rules','type' => 'value'));
            $ids_arr = explode(',',$ids);
            $data = '';
            foreach ($ids_arr as $key => $value)
            {
                $where['id'] = $value;
                $where['ismenu'] = '1';
                $res = $this->obj[1]->table($this->config['prefix'].'admin_auth')->where($where)->find();

                if ($res)
                {
                    $data[] = $res;
                }
            }
            Cache::set('backstage',json_encode($data));
        }
        return $data;
    }
    /**
     * note:刷新
     * auth:YW
     * date:2019/03/25
     */
    public function refresh()
    {
        self::refreshMenu();
        echoMsg('10000',$this->message['success']);
    }
    /**
     * note:刷新菜单
     * auth:YW
     * date:2019/03/25
     */
    private function refreshMenu()
    {
        $user = json_decode(Cookie::get($this->request->module().'_info'),1);
        $ids = getFields($this->obj[1]->table($this->config['prefix'].'admin_role'),"id = {$user['roleid']}",array('fields'=>'rules','type' => 'value'));
        $ids_arr = explode(',',$ids);
        $data = '';
        foreach ($ids_arr as $key => $value)
        {
            $where['id'] = $value;
            $where['ismenu'] = '1';
            $res = $this->obj[1]->table($this->config['prefix'].'admin_auth')->where($where)->find();

            if ($res)
            {
                $data[] = $res;
            }
        }
        Cache::set('backstage',json_encode($data));
    }

    /**
     * note:刷新权限
     * auth:YW
     * date:2019/03/25
     */
    private function refreshAuth()
    {

    }

    /**
     * note:菜单组装(无限级)
     * auth:Duncan
     * date:2018/01/08
     */
    static public function editMenu($data, $str = '|— ', $pid=0, $level=0){
        $arr = array();
        foreach ($data as $v){

            if($v['pid'] == $pid){
                $v['level'] = $level + 1;
                $v['str'] = str_repeat($str,$level);
                $v['ltitle'] = $v['str'].$v['title'];
                $arr[] = $v;
                $arr = array_merge($arr,self::editMenu($data,$str,$v['id'], $level+1));
            }
        }
        return $arr;
    }

    /**
     * note:查找目录层级
     * auth:YW
     * date:2018/01/08
     */
    static public function findMenu($data,$level=0){
        $arr = array();

        foreach ($data as $key => $val){
            if($val['level'] == $level){
                array_push($arr,$val);
            }
        }
        return $arr;
    }


}