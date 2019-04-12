<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Db;



/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：订单管理
 */
class Order extends Init
{

    private $payway_cn = array(
        'wallet'        =>  '<span class="label label-success radius">余额支付</span>',
        'point'         =>  '<span class="label label-success radius">积分</span>',
        'integral'      =>  '<span class="label label-success radius">固消金</span>',
    );

    function _initialize()
    {
        parent::_initialize();
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
        $map['join'] = [['db_member u','o.uid = u.id']];
        $map['field'] = 'o.*,u.phone';
        $map['where']['o.status'] = array('gt','-1');
        $this->_list('',$map,'','o.addtime',false);
        return view();
    }

    public function _after_list(&$list)
    {
        foreach ($list as $key => $value)
        {
            if ($value['status'] == '1') {
                // 处理订单状态中文字段
                $v = json_decode($value['status_cn']);
                $list[$key]['status_cn'] =$v[count($v)-1];
            }else if ($value['status'] == '0'){
                $list[$key]['status_cn'] ='已禁用';
            }else if ($value['status'] == '-1'){
                $list[$key]['status_cn'] ='已删除';
            }
            $list[$key]['payway_cn'] = $this->payway_cn[$value['payway']];
        }
    }

    public function _filter(&$map)
    {   
        $this->checkSearch($map);
    }

    public function _before_edit()
    {
        if($this->obj->post())
        {

        }else{

            $id = $_GET['id'];
            $vo = Db::table($this->config['prefix'].'order')->where('id = '.$id)->find();
            $this->assign('vo',$vo);
        }
    }

    public function _after_update(&$id)
    {

        $where['id'] = $id;
        $str = json_decode(Db::table($this->table)->where($where)->find()['status_cn'],1);
        array_push($str,'已发货');
        $data['status_cn'] = json_encode($str);
        $data['edittime'] = time();
        $data['deliver'] = '1';
        $res = Db::table($this->table)->where($where)->update($data);
        if ($res) {
            echoMsg('10000',$this->message['success']);
        }else{
            echoMsg('10001',$this->message['error']);
        }
    }

    public function _after_delete(&$id)
    {
        $where['id'] = $id;
        $data['edittime'] = time();
        $res = Db::table($this->table)->where($where)->update($data);
        if ($res) {
            echoMsg('10000',$this->message['success']);
        }else{
            echoMsg('10001',$this->message['error']);
        }
    }

    public function recycle()
    {
        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $map['where'] = $map;
        $map['alias'] = 'o';
        $map['join'] = [['db_member u','o.uid = u.id']];
        $map['field'] = 'o.*,u.phone';
        $map['where']['o.status'] = '-1';
        $this->_list('',$map,'','edittime',false);
        return view();
    }


}