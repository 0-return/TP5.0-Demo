<?php
namespace app\lapi\common\controller;

/*
 * 支付参数对接，逻辑处理
 * auth:YW
 * date:2018/06/12
 *
 */

class Wxapp extends Wxpay
{
    private $obj;
    private $config;
    private $notify_url;
    private $database;


    /*
     *note:初始化
     *auth:YW
     *date:2018/05/30
     */
    public function _init()
    {
        $this->notify_url=get_url()['weburl'].'index.php/Uapi/index/index/do/Wxnotify.wxpay_pay_notify';
        $this->database = M()->Db('2', 'DB_CONFIG2');
        $where['type'] = 'wxpay';
        $res = $this->database->table('fwy_payapi')->where($where)->find();
        //获取配置文件
        $this->config = array('merchant'=>$res['merchant'],'appid'=>array('JSAPI' => $res['jsapi'],'APP' => $res['appid']),'appsecret'=>$res['appsecret']);
    }

    /*************************************************[扫码支付]华丽的分割线************************************************************/
    /*
     *note:收银员使用扫码设备读取微信用户刷卡授权码以后，二维码或条码信息传送至商户收银台，由商户收银台或者商户后台调用该接口发起支付。
     *auth:YW
     *date:2018/06/01
     * 注意：trade_type类型[APP,MICROPAY,JSAPI,MWEB]
     * data比传参数[type,body,out_trade_no,total,trade_type,auth_code]
     */
    public function micropay($data,$config = '')
    {

        $this->obj = new WxpayController();
        $gateway = 'https://api.mch.weixin.qq.com/pay/micropay';
        $this->obj->notify_url = $this->notify_url;                 //回调地址

        //配置信息
        if (empty($config))
        {
            $this->config['appid'] = $this->config['appid'][$data['type']];
            $this->obj->config = $this->config;

        }else{
            $this->obj->config = $config;
        }

        $obj = $this->obj->wx_obj();
        $data['nonce_str'] = $obj->createNoncestr();
        $dataArr = $this->set_micropay_data($data);                    //格式化数据
        unset($data);
        $dataArr['sign'] = $obj->getSign($dataArr,$this->obj->appsecret);    //签名
        $xml = $obj->arrayToXml($dataArr);                             //array -- xml
        $data_xml = $obj->postXmlCurl($xml, $gateway);              //向网关发起请求
        $response = $obj->xmlToArray($data_xml);                    //xml -- array
        return $response;
    }

    /*
     *note:设置支付参数
     *auth:YW
     *date:2018/01/18
     */
    protected function set_micropay_data($data)
    {
        $data = array(
            'appid' => $this->obj->wx_appid,
            'mch_id' => $this->obj->wx_mch_id,
            'nonce_str' => $data['nonce_str'],                      //随机字符
            'body' => $data['title'],                                // 商品描述
            'out_trade_no' => $data['order_no'],                // 订单号
            'total_fee' => $data['total']*100,                          //金额
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],          //终端ip
            'auth_code'=>$data['auth_code'],
            'time_start'=> time(),
        );
        return $data;
    }


    /*************************************************[统一支付]华丽的分割线************************************************************/
    /*
     *note:商户系统先调用该接口在微信支付服务后台生成预支付交易单，返回正确的预支付交易会话标识后再在APP里面调起支付。
     *auth:YW
     *date:2018/06/01
     * 注意：trade_type类型[APP,MICROPAY,JSAPI,MWEB]
     * data比传参数[type,body,out_trade_no,total,trade_type]
     */
    public function unifiedorder($data,$config = '',$notify_url='')
    {

        $this->obj = new WxpayController();
        $gateway = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $this->obj->notify_url = empty($notify_url)?$this->notify_url:$notify_url;                 //回调地址
        if (empty($data['trade_type'])) {
            $data['trade_type']='APP';
        }
        if (empty($config))
        {
            $this->config['appid'] = $this->config['appid'][$data['trade_type']];
            $this->obj->config = $this->config;
        }else{
            $this->obj->config = $config;
        }

        $obj = $this->obj->wx_obj();
        $data['nonce_str'] = $obj->createNoncestr();
        $dataArr = $this->set_unifiedorder_data($data);      //格式化数据
        $dataArr['sign'] = $obj->getSign($dataArr,$this->obj->appsecret);    //签名
        $xml = $obj->arrayToXml($dataArr);                             //array -- xml
        $data_xml = $obj->postXmlCurl($xml, $gateway);              //向网关发起请求
        $data_arr = $obj->xmlToArray($data_xml);         //xml -- array
        $response = $this->response_sign($data_arr);    //验证返回
        // 创建预订单成功
        if ($response) {
            $response['sign']=$obj->getSign($response,$this->obj->appsecret);//二次签名
            $response['mch_id']=$data_arr['mch_id'];
            $response['order_no'] = $data['order_no'];
        }
        return $response;
    }

    /*
     *note:设置支付参数(小程序，扫码，H5支付需要openid)
     *auth:YW
     *date:2018/01/18
     */
    protected function set_unifiedorder_data($data)
    {

        $unifiedOrder = '';
        if ($data['trade_type'] == 'JSAPI')
        {
            $unifiedOrder = array(
                'openid' => $data['openid'],
            );
        }

        $unifiedOrder = array(
            'appid' => $this->obj->wx_appid,
            'mch_id' => $this->obj->wx_mch_id,
            'notify_url' => $this->obj->notify_url,
            'nonce_str' => $data['nonce_str'],                      //随机字符
            'body' => $data['title'],                                // 商品描述
            'out_trade_no' => $data['order_no'],                    // 订单号
            'total_fee' => $data['total']*100,                          //金额
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],          //终端ip
            'time_start'=> time(),
            'trade_type' => $data['trade_type'],                    //trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识。
        );
        return $unifiedOrder;
    }

/*
     *note:验证预下单返回
     *auth:杨炜
     *date:2018/01/18
     */
    protected function response_sign($response_result)
    {
        if ($response_result['return_code'] == 'SUCCESS' && $response_result['result_code'] == 'SUCCESS') {
            $response = array(
                'appid' => $response_result['appid'],
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