<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Db;
/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：角色管理
 */
class Role extends Init
{
	function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'].'admin_role';
    }

    /**
     * @auth YW
     * @date 2017.12.2
     * @purpose
     * @return void
     */
    protected function _filter(&$map)
    {
        $this->checkSearch($map);
    }
    /**
     * @auth YW
     * @date 2018.03.06
     * @purpose 角色管理
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
     * @date 2018.03.06
     * @purpose 角色管理
     * @return void
     */
    public function _after_list(&$list)
    {

    }


    /**
     * @auth YW
     * @date 2018.03.06
     * @purpose 添加角色
     * @return void
     */
    public function _before_add(&$list)
    {
        $data = input('post.');
        if ($this->request->isAjax()) {
            $this->checkUnique('title', $data['title']);
        }
        $list['add_time'] = time();

    }
    /**
     * @auth YW
     * @date 2018.03.06
     * @purpose 编辑权限
     * @return void
     */
    public function _before_update(&$list)
    {

        if (isset($list['rules']))
        {
            $list['rules'] = implode(',',$list['rules']);
        }
    }

    /**
     * @auth YW
     * @date 2018.03.06
     * @purpose 读取权限列表
     * @return void
     */
    public function auth_list()
    {

        $id = input('get.id');
        $where['id'] = $id;
        $res = $this->obj[1]->table($this->table)->field('id,rules')->where($where)->find();
        if ($res)
        {
            //获取菜单目录数
            $menu = $this->getMenuTree($pid = 0,$level = 0);

            //定义新数组，用于存放返回的目录数
            $temp = array();
            $rolesArr = explode(',',$res['rules']);
            foreach ($menu as $v){
                if(in_array($v['id'],$rolesArr)){
                    $v['checked'] = true;
                }
                $v['open'] = true;
                $temp[]=$v;
            }

            $this->assign('vo',$res);// 赋值数据集
            $this->assign('menu',json_encode($temp,true));// 赋值数据集*/
        }

        return view();
    }

    /**
     * note:获取菜单目录
     * auth:YW
     * date:2018/10/19
     */
    private function getMenuTree($pid = 0,$level = 0)
    {
        $res = $this->obj[1]->table($this->config['prefix'].'admin_auth')->where('pid = '.$pid)->field('id,title,pid')->select();
        $tree = array();//声明新数组存放子元素
        if($res){
            $level ++;//子目录层级
            $line = str_repeat('—',$level);//线条个数
            foreach($res as $value){
                $value["line"] = "|".$line;
                $tree[] = $value;
                $data = $this->getMenuTree($value['id'],$level);
                if(!empty($data)){
                    foreach($data as $v){
                        array_push($tree,$v);
                    }
                }
            }
        }
        return $tree;
    }
}