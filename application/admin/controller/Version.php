<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use app\common\controller\Common;
use think\Db;
/**
 * Created by PhpStorm.
 * User: EVOL
 * Date: 2018/10/27
 * Time: 17:11
 */

class Version extends Init
{
    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'].'version';
    }

    public function index()
    {
        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $map['status'] = array('gt','0');
        $where['where'] = $map;
        $this->_list('',$where);
        return view();
    }

    public function _filter(&$map)
    {
        $get = $this->request->get();
        if (!empty($get['begintime']) && !empty($get['endtime']))
        {
            $map['add_time'] = array('between',array(strtotime($get['begintime']),strtotime($get['endtime'])));
        }
        $this->checkSearch($map);
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
                $this->checkUnique('title',$data['title']);
            }
            $list['add_time'] = time();

        }else{

            $list = $this->obj[1]->table($this->config['prefix'].'admin_role')->where('status','>','0')->select();
            $this->assign('list', $list);
        }
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