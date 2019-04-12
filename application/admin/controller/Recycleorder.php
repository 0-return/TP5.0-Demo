<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Db;


/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：删除订单管理
 */
class Recycleorder extends Init
{

    private $payway_cn = array(
        'wallet'        =>  '<span class="btn btn-primary radius size-S">余额支付</span>',
        'coin'         =>  '<span class="btn btn-warning radius size-S">法币支付</span>',
        'alipay'         =>  '<span class="btn btn-secondary radius size-S">阿里支付</span>',
        'wxpay'         =>  '<span class="btn btn-success radius size-S">微信支付</span>',
    );

    private $status_cn = array(
        '-1'        =>  '<span class="label label-success radius">系统删除</span>',
        '0'         =>  '<span class="label label-success radius">初始订单</span>',
        '1'         =>  '<span class="label label-success radius">正常订单</span>',
    );

    private $ustatus_cn = array(
        '0'        =>  '<span class="label label-secondary radius">等待接单</span>',
        '1'         =>  '<span class="label label-success radius">律师服务</span>',
        '2'         =>  '<span class="label label-warning radius">服务完成</span>',
        '3'         =>  '<span class="label label-danger radius">拒绝接单</span>',
    );

    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'].'order';
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
        $map['where'] = $map;
        $map['alias'] = 'o';
        $map['join'] = [[$this->config['prefix'].'member u','o.uid = u.uid']];
        $map['field'] = 'o.*,u.phone';
        $map['where']['o.status'] = '-1';
        $this->_list('',$map,'','o.add_time',false);
        return view();
    }

    public function _after_list(&$list)
    {

        foreach ($list as $key => $value)
        {
            if($this->request->action() == 'index' || $this->request->action() == 'recycle')
            {
                $list[$key]['status_cn'] = $this->status_cn[$value['status']];
                $list[$key]['payway_cn'] = $this->payway_cn[$value['payway']];
                if ($value['urgent'] == '1')
                {
                    $list[$key]['total'] = sprintf("%.2f",$value['total']+$value['urgenttotal']);
                }
                $list[$key]['price'] = !$value['price']?'0':$value['price'];
                $list[$key]['detail'] = $value['title'];
                $list[$key]['ustatus_cn'] = $this->ustatus_cn[$value['ustatus']];
                $list[$key]['lid_cn'] = $this->obj->table($this->config['prefix'].'lawyer')->where('uid','=',$value['lid'])->value('username');

            }else{
                $list[$key]['status_cn'] = $this->status_cn[$value['status']];
                $list[$key]['payway_cn'] = $this->payway_cn[$value['payway']];
                $list[$key]['lid_cn'] = $this->obj->table($this->config['prefix'].'lawyer')->where('uid','=',$value['lid'])->value('username');
                $list[$key]['uid_cn'] = $this->obj->table($this->config['prefix'].'member')->where('uid','=',$value['lid'])->value('username');

            }
        }
    }

    public function _filter(&$map)
    {
        $get = $this->request->get();
        if (!empty($get['begintime']) && !empty($get['endtime']))
        {
            $map['o.add_time'] = array('between',array(strtotime($get['begintime']),strtotime($get['endtime'])));
        }
        $this->checkSearch($map);
    }

    public function _before_edit()
    {
        if($this->request->post())
        {

        }else{

            $id = $_GET['id'];
            $vo = $this->obj->table($this->config['prefix'].'order')->where('id = '.$id)->find();
            $this->assign('vo',$vo);
        }
    }

    public function _after_update(&$id)
    {

        $where['id'] = $id;
        $str = $this->obj->table($this->table)->where($where)->find()['status_cn'];
        if ($str == '') {
            $str = '已发货';
        }else{
            $str = json_decode($str,1);
            array_push($str,'已发货');
        }
        $data['status_cn'] = json_encode($str);
        $data['edit_time'] = time();
        $data['deliver'] = '1';
        $data['status'] = '1';
        $res = $this->obj->table($this->table)->where($where)->update($data);
        if ($res) {
            echoMsg('10000',$this->message['success']);
        }else{
            echoMsg('10001',$this->message['error']);
        }
    }

    public function _after_delete(&$id)
    {
        $where['id'] = $id;
        $data['edit_time'] = time();
        $res = $this->obj->table($this->table)->where($where)->update($data);
        if ($res) {
            echoMsg('10000',$this->message['success']);
        }else{
            echoMsg('10001',$this->message['error']);
        }
    }



}