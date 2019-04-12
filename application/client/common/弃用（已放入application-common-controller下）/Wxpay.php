<?php
namespace app\lapi\common\controller;
/*
 * 支付初始类，只负责实例化支付接口，和参数设置
 * auth:YW
 * date:2018/06/12
 *
 */
class Wxpay
{
    private $config;
    private $wx_appid;
    private $wx_mch_id;
    private $appsecret;
    private $notify_url;    //商户回调函数

    public function __construct()
    {
        if (method_exists($this,'_init'))
        {
            $this->_init();
        }
    }

    /*
     *note:加载微信商户
     *auth:YW
     *date:2018/01/18
     *return obj
     */
    public function wx_obj()
    {
        Vendor("wxpay.wxpay");
        $wxpay = new \Common_util_pub();
        $this->wx_appid = $this->config['appid'];    //appid
        $this->wx_mch_id = $this->config['merchant'];                       //商户号
        $this->appsecret = $this->config['appsecret'];                      //秘钥
        return $wxpay;
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

    public function _empty()
    {
        header('HTTP/1.1 404 Not Found');
    }
}