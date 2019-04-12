<?php
namespace app\uapi\common\controller;
/**
 * 支付初始类，只负责实例化支付接口，和参数设置
 * auth:YW
 * date:2018/06/12
 *
 */
class Alipay
{

    private $config;
    private $ali_aop;               //alipay类
    private $ali_request;           //alipay类
    private $ali_seller_email;      //ali商户
    private $method;                //方法
    private $notify_url;            //商户回调函数
    private $api;                   //接口文件

    public function __construct()
    {
        if (method_exists($this,'_init'))
        {
            $this->_init();
        }
    }
    /*
     *note:支付信息配置封装
     *auth:YW
     *date:2018/05/30
     */
    public function ali_obj()
    {
        Vendor("Alipay.AopClient");
        Vendor("Alipay.SignData");
        $this->ali_aop = new \AopClient();
        $this->ali_aop->gatewayUrl = 'https://openapi.alipaydev.com/gateway.do';        //测试地址，上线时注销本行
        if ($this->config)
        {
            $this->ali_aop->appId = $this->config['appid'];    //appid
            $this->ali_seller_email = $this->config['merchant'];  //商户id
            $this->ali_aop->gatewayUrl = $this->config['gateway'];  //网关
            $this->ali_aop->rsaPrivateKey = $this->config['rsaprivatekey'];   //私钥
            $this->ali_aop->alipayrsaPublicKey = $this->config['rsapublickey'];    //公钥
            $this->ali_aop->format = "json";
            $this->ali_aop->timestamp = time();
            $this->ali_aop->charset = "UTF-8";
            $this->ali_aop->signType = "RSA2";
            $this->ali_aop->method = $this->method;
            $this->notify_url = $this->notify_url;
        }else {
            $msg['code'] = 10001;
            $msg['msg'] = '配置载入错误';
            return $msg;
        }
    }

    /*
     *note:设置私有属性
     *auth:YW
     *date:2018/05/30
     */
    public function __set($name, $value)
    {
        // TODO: Implement __set() method.
        if (!empty($name))
        {
            $this->$name = $value;
        }else{
            return false;
        }

    }
    /*
     *note:获取私有属性
     *auth:YW
     *date:2018/05/30
     */
    public function __get($name)
    {
        // TODO: Implement __get() method.
        if (!empty($name))
        {
            return $this->$name;
        }
    }

    /*
     *note:外部商户APP唤起快捷SDK创建订单并支付
     *auth:YW
     *date:2018/05/30
     */
    public function AlipayTradeAppPayRequest()
    {

        Vendor($this->api);
        $this->ali_request = new \AlipayTradeAppPayRequest();
    }

    /*
     *note:收银员使用扫码设备读取用户手机支付宝“付款码”/声波获取设备（如麦克风）读取用户手机支付宝的声波信息后，将二维码或条码信息/声波信息通过本接口上送至支付宝发起支付。
     *auth:YW
     *date:2018/05/30
     */
    public function AlipayTradePayRequest()
    {

        Vendor($this->api);
        $this->ali_request = new \AlipayTradePayRequest();
    }

    /*
     *note:收银员通过收银台或商户后台调用支付宝接口，生成二维码后，展示给用户，由用户扫描二维码完成订单支付。
     *auth:YW
     *date:2018/05/30
     */
    public function AlipayTradePrecreateRequest()
    {
        Vendor($this->api);
        $this->ali_request = new \AlipayTradePrecreateRequest();
    }

    /*
     *note:商户通过该接口进行交易的创建下单
     *auth:YW
     *date:2018/05/30
     */
    public function AlipayTradeCreateRequest()
    {
        Vendor($this->api);
        $this->ali_request = new \AlipayTradeCreateRequest();
    }

    /*
     *note:PC场景下单并支付
     *auth:YW
     *date:2018/05/30
     */
    public function AlipayTradePagePayRequest()
    {
        Vendor($this->api);
        $this->ali_request = new \AlipayTradePagePayRequest();
    }
    /*
     *note:统一退款接口
     *auth:YW
     *date:2018/05/30
     */
    public function AlipayTradeRefundRequest()
    {
        Vendor($this->api);
        $this->ali_request = new \AlipayTradeRefundRequest();
    }

    /*
     *note:授权
     *auth:YW
     *date:2018/05/30
     */
    public function AlipayOpenAuthTokenAppRequest()
    {

        Vendor($this->api);
        $this->ali_request = new \AlipayOpenAuthTokenAppRequest();
    }
    /*
     *note:授权查询
     *auth:YW
     *date:2018/05/30
     */
    public function AlipayOpenAuthTokenAppQueryRequest()
    {
        Vendor($this->api);
        $this->ali_request = new \AlipayOpenAuthTokenAppQueryRequest();
    }

    public function _empty()
    {
        header('HTTP/1.1 404 Not Found');
    }
}