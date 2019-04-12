<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Db;
/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：权限管理
 */
class Auth extends Init
{
	function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'].'admin_auth';
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
     * @purpose 权限管理
     * @return void
     */
    public function index()
    {
        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $this->_list('',$map,'','','',1000);
        return view();
    }
    /**
     * @auth YW
     * @date 2018.03.06
     * @purpose 权限管理排序
     * @return void
     */
    public function _after_list(&$list)
    {
        $list = self::getTree($list);
    }

    /**
     * @auth YW
     * @date 2018.03.06
     * @purpose 添加权限
     * @return void
     */
    public function _before_add(&$list)
    {
        $data = input('post.');
        if ($this->request->isPost()) {
            if ($this->request->isAjax()) {
                $this->checkUnique('mca', $data['mca']);
            }
            $list['add_time'] = time();

        }else{
            $list = $this->obj[1]->table($this->table)->select();
            $list = self::getTree($list,0,1);
            $this->assign('list', $list);
        }
    }

    /**
     * @auth YW
     * @date 2018.03.06
     * @purpose 添加权限
     * @return void
     */
    public function _after_add(&$id)
    {
        $ids = $this->obj[1]->table($this->config['prefix'].'admin_role')->where('id = 1')->find();
        $ids['rules'] = $ids['rules'].','.$id;
        $res = $this->obj[1]->table($this->config['prefix'].'admin_role')->where('id = 1')->update($ids);
        $list = $this->obj[1]->table($this->table)->select();
        $list = self::getTree($list,0,1);
        $this->assign('list', $list);
        if ($res) {
            echoMsg('10000',$this->message['success']);
        }else{
            echoMsg('10001',$this->message['error']);
        }

    }


    /**
     * @auth YW
     * @date 2018.03.06
     * @purpose 权限编辑，数据完善
     * @return void
     */
    public function _after_edit()
    {
        $res = $this->obj[1]->table($this->config['prefix'].'admin_auth')->select();
        $tree = self::getTree($res,0,1);
        $this->assign('list', $tree);
    }

    /**
     * note:目录数组装(无限级)
     * auth:YW
     * date:2018/01/08
     */
    static public function getTree($data,$pid=0,$level=0,$str='|— '){
        $temp = array();
        foreach ($data as $v){
            if($v['pid'] == $pid){
                $v['level'] = $level + 1;
                $v['str'] = str_repeat($str,$level);
                $v['show'] = $v['str'].$v['title'];
                $temp[] = $v;
                $temp = array_merge($temp,self::getTree($data,$v['id'], $level+1,$str));
            }
        }
        return $temp;
    }



}