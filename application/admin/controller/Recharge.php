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

class Recharge extends Init
{
    private $payway_cn = array(
        'wallet'        =>  '<span class="btn btn-success-outline radius size-S">余额支付</span>',
        'coin'         =>  '<span class="btn btn-success-outline radius size-S">法币支付</span>',
        'alipay'         =>  '<span class="btn btn-success-outline radius size-S">支付宝支付</span>',
        'wxpay'         =>  '<span class="btn btn-success-outline radius size-S">微信支付</span>',
    );
    private $status_cn = array(
        '-1'        =>  '<span class="label label-danger radius">系统删除</span>',
        '0'         =>  '<span class="label label-warning radius">未支付</span>',
        '1'         =>  '<span class="label label-success radius">已成功</span>',
    );

    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'].'member_order_recharge';
    }

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
            $res = $this->obj[1]->table($this->config['prefix'].'member')->where($where)->field('username,nickname')->find();
            $list[$key]['username'] = $res['username'];
            $list[$key]['nickname'] = $res['nickname'];
            $list[$key]['status_cn'] = $this->status_cn[$value['status']];
            $list[$key]['payway_cn'] = $this->payway_cn[$value['payway']];
        }

    }
}