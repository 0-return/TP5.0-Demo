<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Db;

/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：未接订单
 */
class Missed extends Init
{

    private $payway_cn = array(
        'wallet'        =>  '<span class="btn btn-success-outline radius size-S">余额支付</span>',
        'coin'         =>  '<span class="btn btn-success-outline radius size-S">法币支付</span>',
        'alipay'         =>  '<span class="btn btn-success-outline radius size-S">支付宝支付</span>',
        'wxpay'         =>  '<span class="btn btn-success-outline radius size-S">微信支付</span>',
    );

    private $status_cn = array(
        '-1'        =>  '<span class="label label-danger radius">系统删除</span>',
        '0'         =>  '<span class="label label-warning radius">未接订单</span>',
        '1'         =>  '<span class="label label-success radius">已接订单</span>',
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
        $this->table = $this->config['prefix'] . 'ortemp';
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
     * @date 2018.11.24
     * @purpose 列表
     * @return void
     */
    public function index()
    {
        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $map['status'] = array('eq','0');
        $where['where'] = $map;
        $this->_list('',$where);
        return view();
    }
    public function _after_list(&$list)
    {
        foreach ($list as $key => $value)
        {
            $list[$key]['status_cn'] = $this->status_cn[$value['status']];

        }
    }

}