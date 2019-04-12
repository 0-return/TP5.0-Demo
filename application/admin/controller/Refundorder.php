<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use app\common\controller\Common;
use think\Db;


/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：退款订单管理
 */
class Refundorder extends Init
{
    public $pay_config;
    private $assist = '';
    private $payway_cn = array(
        'wallet'        =>  '<span class="btn btn-primary radius size-S">余额支付</span>',
        'coin'         =>  '<span class="btn btn-warning radius size-S">法币支付</span>',
        'alipay'         =>  '<span class="btn btn-secondary radius size-S">阿里支付</span>',
        'wxpay'         =>  '<span class="btn btn-success radius size-S">微信支付</span>',
    );


    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'].'order';
        $this->assist = $this->obj->table('fwy_assist')->find();

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
        $map['join'] = array([$this->config['prefix'].'aftersale a','o.order_no = a.order_no','RIGHT']);
        $map['field'] = 'o.lid,o.payway,a.*';
        $map['where']['a.status'] = '0';
        $map['group'] = 'a.order_no';
        $map['where']['a.status'] = array('gt',-1);
        $this->_list('',$map,'','a.ask_time',false);
        return view();
    }

    public function _after_list(&$list)
    {

        foreach ($list as $key => $value)
        {
            $list[$key]['lid_cn'] = $this->obj->table($this->config['prefix'].'lawyer')->where('uid','=',$value['lid'])->value('username');
            $list[$key]['uid_cn'] = $this->obj->table($this->config['prefix'].'member')->where('uid','=',$value['lid'])->value('username');
            $list[$key]['payway_cn'] = $this->payway_cn[$value['payway']];
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
        $this->table = $this->config['prefix'].'aftersale';
    }

    public function _before_delete()
    {
        $this->table = $this->config['prefix'].'aftersale';
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
    /**
     * @auth YW
     * @date 2019.03.25
     * @purpose 拒绝退款
     * @param 退款说明[refund_content]
     * @return void
     */
    public function dontRefund()
    {
        $this->table = $this->config['prefix'].'aftersale';
        $post = $this->request->post();
        $this->obj->startTrans();
        $where['order_no'] = $post['order_no'];
        $data['refund_content'] = $post['refund_content'];
        $data['act_status'] = '2';
        $aftersale = $this->obj->table($this->table)->where($where)->update($data); unset($where,$data);

        $where['order_no'] = $post['order_no'];
        $where['refund'] = '1';
        $data['refund'] = '4';
        $order = $this->obj->table($this->config['prefix'].'order')->where($where)->update($data);

        if ($order && $aftersale)
        {
            $this->obj->commit();
            echoMsg('10000','拒绝退款成功！');
        }else{
            $this->obj->rollback();
            echoMsg('10001','退款金额修改失败！');
        }
    }

    /**
     * @auth YW
     * @date 2019.03.23
     * @purpose 退款数据准备（支持批量）（自定义金额退款到余额，不支持批量）,如果被退款目标金额不足，则从其他金额里面扣
     * @return void
     */
    public function refundReady()
    {
        $this->table = $this->config['prefix'].'aftersale';
        $post = $this->request->post();

        if (isset($post['total']))
        {
            $where['id'] = intval($post['id']);
            $where['act_status'] = '0';
            $data['total'] = $post['total'];
            $res = $this->obj->table($this->table)->where($where)->update($data);
            if (!$res)
            {
                echoMsg('10001','退款金额修改失败！');
            }
        }

        $ids = explode(',',trim($post['id'],','));
        if (count($ids) > 1)
        {
            $where['id'] = array('in',trim($post['id']));
            $where['act_status'] = '0';
            $res = $this->obj->table($this->table)->where($where)->select();
        }else{
            $where['id'] = $post['id'];
            $where['act_status'] = '0';
            $res = $this->obj->table($this->table)->where($where)->find();
        }


        if ($res)
        {
            $fun = $res['payway'].'Refund';

            $res = count($ids) > 1?$this->$fun($res):$this->$fun($res,false);
            count($ids) > 1?$res === true?echoMsg('10000','全部退款成功'):echoMsg('10001','部分退款成功'):$res === true?echoMsg('10000','退款成功'):echoMsg('10001',$res);

        }else{
            echoMsg('10001',$this->message['error'],$res);
        }

    }
    /**
     * @auth YW
     * @date 2019.03.23
     * @purpose 支付宝退款
     * @return bool
     * @paramry $from退款去向[true钱包，false支付宝]
     */
    private function alipayRefund(&$res,$isArr = '',$from = false)
    {
        $where['status'] = '1';
        $where['type'] = 'alipay';
        $this->pay_config = $this->obj->table($this->config['prefix'].'payapi')->where($where)->find();

        if ($from)      //退回到余额
        {
            $res['payway'] = 'wallet';
            $res = self::updOrder($res,$isArr);
            if ($res === 'true')
            {
                return true;
            }else{
                return '退款失败';
            }
        }else{
            $obj = new Common();
            $res = $obj->refundToAlipay($res,$this->pay_config);

            if ($res->code == '10000')
            {
                return true;
            }else{
                return $res->sub_msg;
            }
        }

    }
    /**
     * @auth YW
     * @date 2019.03.23
     * @purpose 微信退款
     * @return bool
     * @paramry $from退款去向[钱包，微信]
     */
    private function wxpayRefund(&$res,$isArr = '',$from = false)
    {
        $where['status'] = '1';
        $where['type'] = 'wxpay';
        $this->pay_config = $this->obj->table($this->config['prefix'].'payapi')->where($where)->find();

        if ($from)      //退回到余额
        {
            $res['payway'] = 'wallet';
            $res = self::updOrder($res,$isArr);
            if ($res === 'true')
            {
                return true;
            }else{
                return '退款失败';
            }
        }else{
            $obj = new Common();
            $res = $obj->refundToWxpay($res,$this->pay_config);
            if (isset($res['result_code']) && $res['result_code'] === 'SUCCESS')
            {
                return true;
            }else{
                return $res['info']['err_code_des'];
            }
        }
    }
    /**
     * @auth YW
     * @date 2019.03.23
     * @purpose 余额退款
     * @return bool
     */
    private function walletRefund(&$res,$isArr = '',$from = true)
    {
        $res = self::updOrder($res,$isArr);
        if ($res === 'true')
        {
            return true;
        }else{
            return '退款失败';
        }

    }
    /**
     * @auth YW
     * @date 2019.03.23
     * @purpose 法币退款
     * @return bool
     */
    private function coinRefund(&$res,$isArr = '',$from = true)
    {

        $res = self::updOrder($res,$isArr);
        if ($res === 'true')
        {
            return true;
        }else{
            return '退款失败';
        }
    }
    /**
     * @auth YW
     * @date 2019.03.23
     * @purpose 退款数据处理（支持批量）
     * @return void
     */
    private function updOrder(&$res,$isArr = true)
    {
        Db::startTrans();$this->obj->startTrans();
        /**修改钱包金额(已收货，未收货)*/
        $where['uid'] = $res['uid'];
        $obj = new Common();
        //用户余额加
        $this->assist['user_type'] = 'uid';
        $user = $obj->wallet($res,$this->assist);
        //律师余额减
        $this->assist['user_type'] = 'lid';
        $lawyer = $obj->wallet($res,$this->assist,'setDec');

        /**修改退款状态*/
        if ($isArr)
        {
            $count = count($res);
            $i = 0;
            foreach ($res as $key => $value)
            {
                $where['order_no'] = $value['order_no'];
                $where['act_status'] = '0';
                $data['act_status'] = '1';
                $aftersale = $this->obj->table($this->table)->where($where)->update($data); unset($where['act_status'],$data);

                $where['refund'] = '1';
                $data['refund'] = '2';
                $order = $this->obj->table($this->config['prefix'].'order')->where($where)->update($data);

                if ($user && $lawyer && $aftersale && $order)
                {
                    $this->obj->commit();Db::commit();
                    $i++;
                }else{
                    $this->obj->rollback();Db::rollback();
                    $temp[$key] = $value;
                }

                if ($count == $i)
                {
                    return 'true';
                }else{
                    return $temp;
                }
            }
        }else{
            $where['order_no'] = $res['order_no'];
            $where['act_status'] = '0';
            $data['act_status'] = '1';
            $aftersale = $this->obj->table($this->table)->where($where)->update($data); unset($where['act_status'],$data);

            $where['refund'] = '1';
            $data['refund'] = '2';
            $order = $this->obj->table($this->config['prefix'].'order')->where($where)->update($data);

            if ($user && $lawyer && $aftersale && $order)
            {
                Db::commit();$this->obj->commit();
                return 'true';
            }else{
                Db::rollback();$this->obj->rollback();
                return $res;
            }
        }
    }



}