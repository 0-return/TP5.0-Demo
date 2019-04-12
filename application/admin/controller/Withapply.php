<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use app\common\controller\Common;

/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：系统消息管理
 */
class Withapply extends Init
{
    private $assist;
    private $status_cn = array(
        '未认证','未认证','正常','审核未通过',
    );

    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'] . 'withdraw';
        $this->assist = $this->obj->table($this->config['prefix'].'assist')->find();
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

    public function _filter(&$map)
    {
        $this->checkSearch($map);
    }

    public function _after_list(&$list)
    {
        foreach ($list as $key => $value)
        {
            $where['uid'] = $value['uid'];
            $res = $this->obj->table($this->config['prefix'].'lawyer')->where($where)->field('username,bankcard,phone,status')->find();
            $list[$key]['username'] = $res['username'];
            $list[$key]['bankcard'] = $res['bankcard'];
            $list[$key]['status_cn'] = $this->status_cn[$res['status']];

        }
    }

    public function apply()
    {
        $res = $this->request->Post();
        $where['id'] = $res['id'];
        $where['status'] = '0';
        $withdraw = $this->obj->table($this->table)->where($where)->find(); unset($where);
        if ($res)
        {
            $where['status'] = '2';
            $where['uid'] = $withdraw['uid'];
            $res = $this->obj->table($this->config['prefix'].'lawyer')->where($where)->find();
            if($res && $res['bankcard'] != '')
            {

                //计算手续费后的实际金额
                $amount = sprintf("%.2f",$withdraw['amount']*$this->assist['service_charge']/100);
                $withdraw['amount'] = sprintf("%.2f",$withdraw['amount']-$amount);
                $this->obj->startTrans();
                $response = self::withdraw_money($withdraw);
                if ($response === true && self::withdraw_status($withdraw))
                {
                    $this->obj->commit();
                    echoMsg('10000','提现处理成功');
                }else{
                    $this->obj->rollback();
                    echoMsg('10003','提现处理失败：'.$response->sub_msg);
                }
            }else{
                echoMsg('10002','提现处理失败：律师认证失效或缺少必要信息');
            }

        }else{
            echoMsg('10001','提现处理失败：申请提现订单异常');
        }
    }

    private function withdraw_money(&$list)
    {
        switch ($list['totype'])
        {
            case 'alipay':
                $where['status'] = '1';
                $where['type'] = 'alipay';
                $list['alipay_logonid_type'] = 'ALIPAY_LOGONID';
                $list['merchant_name'] = $this->config['web_name'];
                $list['remark'] = $list['remark']?$list['remark']:'提现';
                $assist = $this->obj->table($this->config['prefix'].'payapi')->where($where)->find();
                $res = self::withdraw_start_alipay($list,$assist);
                return $res->code == '10000'?true:$res;
                break;
            case 'wxpay':
                $where['status'] = '1';
                $where['type'] = 'wxpay';
                $assist = $this->obj->table($this->config['prefix'].'payapi')->where($where)->find();
                $res = self::withdraw_start_alipay($list,$assist);
                return $res->code == '10000'?true:false;
                break;
            default:
                return false;
                break;
        }
    }

    private function withdraw_start_alipay(&$list,&$assist)
    {
        $obj = new Common();
        $res = $obj->payToAlipay($list,$assist);
        return $res;
    }

    private function withdraw_start_wxpay(&$list,&$assist)
    {
        $obj = new Common();
        $res = $obj->payToWxpay($list,$assist);
        return $res;
    }

    private function withdraw_status(&$list)
    {

        $where['id'] = $list['id'];
        $where['status'] = '0';
        $data['status'] = '1';
        $data['processing_person'] = getUser($this->request->module())['username'];
        $data['over_time'] = time();
        $data['account'] = $list['amount'];
        $res = $this->obj->table($this->table)->where($where)->update($data);
        return $res?true:false;
    }
    /**
     * @auth YW
     * @date 2018.11.19
     * @purpose 列表
     * @return void
     */
    public function withdrawList()
    {
        $post = $this->request->Post();
        $where['uid'] = $post['uid'];
        $res = $this->obj->table($this->table)->where($where)->select();
        $this->assign('list',$res);
        return view('list');
    }



}