<?php
namespace app\common\controller;

/**
 * 支付初始类，只负责实例化支付接口，和参数设置
 * auth:YW
 * date:2018/06/12
 *
 */
class Wxpay extends \WxPayConfigInterface
{
    private $config;

    public function __construct()
    {
        if (method_exists($this,'_init'))
        {
            $this->_init();
        }
    }

    /**
     * note:设置私有属性
     * auth:YW
     * date:2018/05/30
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
    /**
     * note:获取私有属性
     * auth:YW
     * date:2018/05/30
     */
    public function __get($name)
    {
        // TODO: Implement __get() method.
        if (!empty($name))
        {
            return $this->$name;
        }
    }

    /**
     *note:加载微信商户
     *auth:YW
     *date:2018/01/18
     *return obj
     */
    public function wx_obj()
    {
        return $this;
    }
    public function GetAppId()
    {
        return $this->config['app_id'];
    }
    public function GetMerchantId()
    {
        return $this->config['merchant'];
    }
    public function GetNotifyUrl()
    {
        return $this->config['notify_url'];
    }
    public function GetSignType()
    {
        return $this->config['signtype'];
    }
    public function GetProxy(&$proxyHost, &$proxyPort)
    {

    }
    public function GetReportLevenl()
    {

    }
    public function GetKey()
    {
        return $this->config['appkey'];
    }

    public function GetAppSecret()
    {
        return $this->config['appsecret'];
    }
    public function GetSSLCertPath(&$sslCertPath, &$sslKeyPath)
    {

    }
    public function _empty()
    {
        header('HTTP/1.1 404 Not Found');
    }
}