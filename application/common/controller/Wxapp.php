<?php
namespace app\common\controller;

/**
 * 支付参数对接，逻辑处理
 * auth:YW
 * date:2018/06/12
 *
 */

class Wxapp
{
    private $obj;
    private $config;

    /*
     *note:初始化
     *auth:YW
     *date:2018/05/30
     */
    public function _init()
    {

    }
    /**
     * note:退款接口
     * auth:YW
     * date:2018/06/01
     * 注意：
     *
     */
    public function refund($data,$config = '')
    {

        Vendor("Wxpay.lib.WxPayApi");
        $obj = new \WxPayRefund();
        $obj->SetAppid($config['app_id']);
        $obj->SetMch_id($config['merchant']);
        $obj->SetOut_trade_no($data['order_no']);
        $obj->SetOut_refund_no($data['out_refund_no']);
        $obj->SetTotal_fee($data['total']);
        $obj->SetRefund_fee($data['total']);
        $obj->SetOp_user_id($config['merchant']);
        //配置文件
        $Wxpay_config = new Wxpay();
        $Wxpay_config->wx_obj();
        $config['signtype'] = 'MD5';
        $Wxpay_config -> config = $config;
        $res = \WxPayApi::refund($Wxpay_config,$obj);
        if ($res['return_code'] == 'SUCCESS')
        {
            return array('info' => $res);
        }
    }



    /*************************************************[扫码支付]华丽的分割线************************************************************/
    /**
     * note:收银员使用扫码设备读取微信用户刷卡授权码以后，二维码或条码信息传送至商户收银台，由商户收银台或者商户后台调用该接口发起支付。
     * auth:YW
     * date:2018/06/01
     * 注意：trade_type类型[APP,MICROPAY,JSAPI,MWEB]
     * data比传参数[type,body,out_trade_no,total,trade_type,auth_code]
     */
    public function micropay($data,$config = '')
    {


    }

    /*************************************************[统一支付]华丽的分割线************************************************************/
    /**
     * note:商户系统先调用该接口在微信支付服务后台生成预支付交易单，返回正确的预支付交易会话标识后再在APP里面调起支付。
     * auth:YW
     * date:2018/06/01
     * 注意：trade_type类型[APP,MICROPAY,JSAPI,MWEB]
     * data比传参数[type,body,out_trade_no,total,trade_type]
     */
    public function unifiedorder($data,$config = '')
    {
        Vendor("Wxpay.lib.WxPayApi");
        $obj = new \WxPayUnifiedOrder();
        $obj->SetBody($data['title']);
        $obj->SetAttach("test");
        $obj->SetOut_trade_no($data['order_no']);
        $obj->SetTotal_fee($data['total']);
        $obj->SetTime_start(date("YmdHis", time()));
        $obj->SetTime_expire(date("YmdHis", time() + 600));
        $obj->SetGoods_tag('order');
        $obj->SetNotify_url("http://paysdk.weixin.qq.com/notify.php");
        $obj->SetTrade_type("APP");
        //配置文件
        $Wxpay_config = new Wxpay();
        $Wxpay_config->wx_obj();
        $config['signtype'] = 'MD5';
        $config['notify_url'] = $config['notify_url'];
        $Wxpay_config -> config = $config;
        $res = \WxPayApi::unifiedOrder($Wxpay_config,$obj);
        if ($res['return_code'] == 'SUCCESS')
        {
            return array('info' => $res);
        }
    }

    /*
     *note:设置支付参数(小程序，扫码，H5支付需要openid)
     *auth:YW
     *date:2018/01/18
     */
    protected function set_unifiedorder_data($data,$config)
    {
        $unifiedOrder = '';
        if ($config['trade_type'] == 'JSAPI')
        {
            $unifiedOrder = array(
                'openid' => $config['openid'],
            );
        }

        $unifiedOrder = array(
            'appid' => $config['app_id'],
            'mch_id' => $config['merchant'],
            'trade_type' => $config['trade_type'],                    //trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识。
            //'nonce_str' => $data['nonce_str'],                      //随机字符
            'body' => $data['title'],                                // 商品描述
            'out_trade_no' => $data['order_no'],                    // 订单号
            'total_fee' => $data['total']*100,                          //金额
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],          //终端ip
            'time_start'=> time(),

        );
        return $unifiedOrder;
    }

    /**
     * note:验证预下单返回
     * auth:YW
     * date:2018/01/18
     */
    protected function response_sign($response_result)
    {
        if ($response_result['return_code'] == 'SUCCESS' && $response_result['result_code'] == 'SUCCESS') {
            $response = array(
                'appid' => $response_result['app_id'],
                'noncestr' => $response_result['nonce_str'],
                'package' => 'Sign=WXPay',
                'partnerid' => $response_result['mch_id'],
                'prepayid' => $response_result['prepay_id'],
                'timestamp' => time()
            );
            return $response;
        } else {
            return false;
        }
    }

}
