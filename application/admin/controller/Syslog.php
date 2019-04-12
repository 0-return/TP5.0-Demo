<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use app\common\controller\Common;

/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：日志管理
 */
class Syslog extends Init
{

    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'].'log_sys';
    }

    /**
     * @auth YW
     * @date 2018.11.19
     * @purpose 列表
     * @return void
     */
    public function index()
    {
        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $map['status'] = array('gt','-1');
        $where['where'] = $map;
        $this->_list('',$where);
        return view();
    }
    private function _filter(&$map)
    {
        $get = $this->request->get();
        if (!empty($get['begintime']) && !empty($get['endtime']))
        {
            $map['addtime'] = array('between',array(strtotime($get['begintime']),strtotime($get['endtime'])));
        }

        $this->checkSearch($map);
    }

    public function _after_list(&$list)
    {


    }



}
