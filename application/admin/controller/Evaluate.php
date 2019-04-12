<?php
namespace app\admin\controller;

use app\admin\common\controller\Init;
use think\Db;

/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：律师评价
 */
class Evaluate extends Init
{

    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'] . 'lawyer_evaluate';
    }

    /**
     * @auth YW
     * @date 2019.3.29
     * @purpose 列表
     * @return void
     */
    public function index()
    {
        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $map['status'] = array('gt', '-1');
        $where['where'] = $map;
        $this->_list('', $where);
        return view();
    }

    public function _filter()
    {
        $get = $this->request->get();
        if (!empty($get['begintime']) && !empty($get['endtime']))
        {
            $map['add_time'] = array('between',array(strtotime($get['begintime']),strtotime($get['endtime'])));
        }
        $this->checkSearch($map);
    }

    public function _after_list(&$list)
    {
        foreach ($list as $key => $value)
        {
            $where['uid'] = $value['uid'];
            $list[$key]['username'] = getFields($this->obj->table($this->config['prefix'].'lawyer'),$where,array('type' => 'value','fields' => 'username'));
        }
    }
}