<?php
namespace app\admin\controller;

use app\admin\common\controller\Init;
use think\Db;


class Pushtype extends Init
{


    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'] . 'pushtype';
    }

    public function index()
    {
        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $map['status'] = array('in', '0,1');
        $where['where'] = $map;
        $this->_list('', $where);
        return view();
    }

    public function _filter(&$map)
    {
        $this->checkSearch($map);
    }

    public function _after_list(&$list)
    { }

    public function _before_add(&$list)
    {
        if ($this->request->post()) {
            $list['add_time'] = time();
        }
    }
}
