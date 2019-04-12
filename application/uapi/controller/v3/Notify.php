<?php
namespace app\uapi\controller\v3;
use app\uapi\common\controller\Wxpay;
use app\uapi\common\controller\Alipay;

class Notify extends Index{

    public $pay_alipay_config;
    public $pay_wxpay_config;
    /**
     * 初始化
     * 检查请求类型，数据格式等
     */
    public function _initialize()
    {
        parent::_init();
        $where['type'] = 'alipay';
        $res = $this->obj->table('fwy_payapi')->where($where)->find(); unset($where);
        //获取支付宝支付配置文件
        $this->pay_alipay_config = array('merchant'=>$res['merchant'],'gateway'=>$res['gateway'],'appid'=>$res['app_id'],'rsaprivatekey'=>$res['rsaprivatekey'],'rsapublickey'=>$res['rsapublickey']);

        $where['type'] = 'wxpay';
        $res = $this->obj->table('fwy_payapi')->where($where)->find();
        //获取微信支付配置文件
        $this->pay_wxpay_config = array('merchant'=>$res['merchant'],'appid'=>$res['app_id'],'appkey' => $res['app_key'],'appsecret'=>$res['app_secret']); unset($where);
    }


    /**
     * note:支付宝支付回调
     * auth:YW
     * date:2019/02/21
     */
    public function alipayRefund()
    {
        $result = $this->request->post();
        $res = self::getOrder($result);

        if ($res['pay'] == '1')
        {
            Vendor("Alipay.AopClient");
            $aop = new \AopClient();
            $aop->alipayrsaPublicKey = $this->config['rsapublickey'];
            //此处验签方式必须与下单时的签名方式一致
            $flag = $aop->rsaCheckV1($result, NULL, "RSA2");
            if ($flag) {               //状态值不为空
                if ($result['trade_status'] == 'TRADE_FINISHED' OR $result['trade_status'] == 'TRADE_SUCCESS')
                {

                    $data['order_no'] = $result['out_trade_no'];
                    $data['out_trade_no'] = $result['trade_no'];
                    $data['total_amount'] = $result['total_amount'];
                    $data['trade_status'] = $result['trade_status'];
                    $data['seller_email'] = $result['seller_email'];
                    $data['notify_time'] = time();
                    $data['status'] = 1;
                    $data['pay'] = 1;
                    if (self::pay_edit($data))
                    {
                        echo 'success';exit(0);
                    }
                }
            }
        }else{
            echo 'success';exit(0);
        }
    }

    /**
     * note:微信支付回调
     * auth:YW
     * date:2019/02/21
     */
    public function wxpayRefund()
    {
        Vendor("Wxpay.lib.WxPayData");
        Vendor("Wxpay.lib.WxPayNotify");
        Vendor("Wxpay.lib.WxPayApi");
        $obj = new \WxPayNotify();

        $config = new Wxpay();
        $config->wx_obj();
        $config->config = $this->pay_wxpay_config;

        $res = $obj->Handle($config->config);
        file_put_contents('wxpayRefund.txt',json_encode($res).PHP_EOL,FILE_APPEND);
        exit;

        $data['order_no'] = $result['out_trade_no'];
        $data['out_trade_no'] = $result['transaction_id'];
        $data['total_amount'] = floatval($result['total_fee']/100);
        $data['trade_status'] = $result['result_code'];
        $data['seller_email'] = $result['openid'];
        $data['notify_time'] = time();
        $data['status'] = 1;
        if (self::pay_edit($data))
        {
            echo 'success';exit(0);
        }
    }


    /**
     * note:数据操作
     * auth:YW
     * date:2019/02/21
     */
    private function pay_edit($data)
    {
        $where['id'] = $data['order_id'];
        return $this->obj->table('fwy_order')->where($where)->update($data);
    }
    /**
     * note:支付宝充值回调
     * auth:YW
     * date:2019/02/21
     */
    public function alipayRechargeRefund()
    {
        $res = self::alipayPost();
    }

    /**
     * note:微信充值回调
     * auth:YW
     * date:2019/02/21
     */
    public function wxpayRechargeRefund()
    {
        $res = self::wxpayPost();
    }


    /**
     * note:获取订单信息
     * auth:YW
     * date:2019/02/21
     */
    private function getOrder($data)
    {
        $where['id'] = $data['id'];
        return $this->obj->table('fwy_order')->where($where)->find();
    }

    /**
     * note:获取订单信息
     * auth:YW
     * date:2019/02/21
     */
    private function getRechar($data)
    {
        $where['id'] = $data['id'];
        return $this->obj->table('fwy_rechargelog')->where($where)->find();
    }

    /**
     * auth YW
     * note 空操作
     * date 2018-08-06
     */
    public function _empty(){
        return self::returnMsg('10107','操作不合法');
    }


}