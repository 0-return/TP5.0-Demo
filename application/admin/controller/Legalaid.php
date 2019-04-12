<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Db;

/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：律师管理
 */
class Legalaid extends Init
{
    private $status_cn = array(
        '-1'        =>  '<span class="label label-success radius">系统删除</span>',
        '0'         =>  '<span class="label label-success radius">初始订单</span>',
        '1'         =>  '<span class="label label-success radius">正常订单</span>',
    );
    /**
     * @auth YW
     * @date 2017.12.2
     * @purpose 初始化
     * @return void
     */
    public function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'].'order_help';

    }

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

    public function _filter(&$map)
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
            $user = $this->obj->table($this->config['prefix'].'member')->where($where)->field('username,province_cn,city_cn,area_cn')->find();
            $list[$key]['username'] = $user['username'];
            $list[$key]['dn_address'] = $user['province_cn'].$user['city_cn'].$user['area_cn'];

            $where['uid'] = $value['lid'];
            $lawyer = $this->obj->table($this->config['prefix'].'lawyer')->where($where)->field('username,province_cn,city_cn,area_cn')->find();
            $list[$key]['lawyername'] = $lawyer['username'];

            $list[$key]['up_address'] = $lawyer['province_cn'].$lawyer['city_cn'].$lawyer['area_cn'];

            $list[$key]['status_cn'] = $this->status_cn[$value['status']];
        }

    }

}