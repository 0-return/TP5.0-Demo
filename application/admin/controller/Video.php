<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use app\common\controller\Common;

/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：视频管理
 */
class Video extends Init
{
    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'].'video';
    }

    /**
     * @auth YW
     * @date 2018.11.19
     * @purpose 列表
     * @return void
     */
    public function index()
    {
        /*$map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $map['where']['status'] = array('gt','-1');
        $this->_list('',$map);*/
        echo '该功能还未确认';exit;
        return view();
    }

    private function _filter(&$map)
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
            
            $list[$key]['weburl'] = $this->config['weburl'];
            

        }

    }

}