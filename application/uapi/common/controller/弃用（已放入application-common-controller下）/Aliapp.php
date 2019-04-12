<?php
namespace app\uapi\common\controller;
/**
 * 支付参数对接，逻辑处理
 * auth:YW
 * date:2018/06/12
 *
 */

class Aliapp extends Alipay
{
    private $obj;
    private $config;
    private $notify_url;

    /*
     *note:初始化
     *auth:YW
     *date:2018/05/30
     */
    public function _init()
    {

    }

    /*************************************************[APP支付]华丽的分割线************************************************************/

    /*
     *note:外部商户APP唤起快捷SDK创建订单并支付
     *auth:YW
     *date:2018/05/30
     */
    public function alipay_trade_app_pay($data,$config = '',$notify_url='')
    {
        $this->obj = new Alipay();
        $this->obj->method = 'alipay.trade.app.pay';
        $this->obj->api = 'Alipay.request.AlipayTradeAppPayRequest';
        $this->obj->notify_url = empty($notify_url)?$this->notify_url:$notify_url;
        $this->obj->config = empty($config)?$this->config:$config;
        $this->obj->ali_obj();
        $this->obj->AlipayTradeAppPayRequest();
        return $this->alipay_trade_app_pay_launch($data);

    }

    /*
     *note:支付信息封装
     *auth:YW
     *date:2018/05/30
     */
    private function alipay_trade_app_pay_launch($data)
    {
        $data['extend_params'] = $this->extend_params($data);
        $data['product_code'] = 'QUICK_MSECURITY_PAY';
        $bizcontent = $this->set_app_pay_bizcontent($data);
        $this->obj->ali_request->setNotifyUrl($this->obj->notify_url);//设置回调地址
        $this->obj->ali_request->setBizContent($bizcontent);//设置订单参数
        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $this->obj->ali_aop->sdkExecute($this->obj->ali_request);
        return $response;
    }

    /*
     *note:支付设置预下单参数
     *auth:YW
     *date:2018/05/30
     * input $subject，$out_trade_no，$total
     */
    private function set_app_pay_bizcontent($data)
    {
        $bizcontent = array(
            'timeout_express' => '30m',     //时间
            'product_code' => $data['product_code'],
            'total_amount' => $data['total'],
            'subject' => $data['title'],
            'out_trade_no' => $data['order_no'],
            'extend_params' => $data['extend_params'],      //分期信息
        );
        $bizcontent = json_encode($bizcontent);
        return $bizcontent;
    }

    /*************************************************华丽的分割线************************************************************/

    /*
     *note:商户通过该接口进行交易的创建下单
     *auth:YW
     *date:2018/05/30
     */
    public function alipay_trade_create($data,$config = '')
    {
        $this->obj = new Alipay();
        $this->obj->method = 'alipay.trade.create';
        $this->obj->api = 'Alipay.request.AlipayTradeCreateRequest';
        $this->obj->notify_url = $this->notify_url;
        $this->obj->config = empty($config)?$this->config:$config;
        $this->obj->ali_obj();
        $this->obj->AlipayTradeCreateRequest();
        return $this->alipay_trade_create_launch($data);

    }

    /*
     *note:支付信息封装
     *auth:YW
     *date:2018/05/30
     */
    private function alipay_trade_create_launch($data)
    {
        $data['extend_params'] = $this->extend_params($data);
        $bizcontent = $this->set_create_bizcontent($data);
        $this->obj->ali_request->setNotifyUrl($this->obj->notify_url);//设置回调地址
        $this->obj->ali_request->setBizContent($bizcontent);//设置订单参数
        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $this->obj->ali_aop->sdkExecute($this->obj->ali_request);
        return $response;
    }

    /*
     *note:支付设置预下单参数
     *auth:YW
     *date:2018/05/30
     * input $subject，$out_trade_no，$total
     */
    private function set_create_bizcontent($data)
    {
        $bizcontent = array(
            'timeout_express' => '30m',     //时间
            'total_amount' => $data['total'],
            'subject' => $data['title'],
            'out_trade_no' => $data['order_no'],
            'extend_params' => $data['extend_params'],      //分期信息
        );
        $bizcontent = json_encode($bizcontent);
        return $bizcontent;
    }


    /*************************************************[扫码支付]华丽的分割线************************************************************/

    /*
     *note:收银员使用扫码设备读取用户手机支付宝“付款码”/声波获取设备（如麦克风）读取用户手机支付宝的声波信息后，将二维码或条码信息/声波信息通过本接口上送至支付宝发起支付。
     *auth:YW
     *date:2018/05/30
     */
    public function alipay_trade_pay($data,$config = '')
    {
        $this->obj = new Alipay();
        $this->obj->method = 'alipay.trade.pay';
        $this->obj->api = 'Alipay.request.AlipayTradePayRequest';
        $this->obj->notify_url = $this->notify_url;
        $this->obj->config = empty($config)?$this->config:$config;

        $this->obj->ali_obj();
        $this->obj->AlipayTradePayRequest();
        return $this->alipay_trade_pay_launch($data);

    }

    /*
     *note:支付信息封装
     *auth:YW
     *date:2018/05/30
     */
    private function alipay_trade_pay_launch($data)
    {
        $data['extend_params'] = $this->extend_params($data);
        $data['product_code'] = 'FACE_TO_FACE_PAYMENT';
        $bizcontent = $this->set_trade_bizcontent($data);
        $this->obj->ali_request->setNotifyUrl($this->obj->notify_url);//设置回调地址
        $this->obj->ali_request->setBizContent($bizcontent);//设置订单参数
        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $this->obj->ali_aop->execute($this->obj->ali_request);
        return $response;
    }

    /*
     *note:支付设置预下单参数
     *auth:YW
     *date:2018/05/30
     * input $subject，$out_trade_no，$total
     */
    private function set_trade_bizcontent($data)
    {
        $bizcontent = array(
            'out_trade_no' => $data['order_no'],        //商户订单号
            'scene' => $data['scene'],                      //支付场景 条码支付，取值：bar_code 声波支付，取值：wave_code
            'auth_code' => $data['auth_code'],              //支付授权码
            'total_amount' => $data['total'],
            'timeout_express' => '30m',     //时间
            'product_code' => $data['product_code'],        //销售产品码
            'subject' => $data['title'],
            'extend_params' => $data['extend_params'],      //分期信息
            'body' => $data['title'],
        );
        $bizcontent = json_encode($bizcontent);
        return $bizcontent;
    }

    /*************************************************[扫码支付]华丽的分割线************************************************************/

    /*
     *note:收银员通过收银台或商户后台调用支付宝接口，生成二维码后，展示给用户，由用户扫描二维码完成订单支付。
     *auth:YW
     *date:2018/05/30
     */
    public function alipay_trade_precreate($data,$config = '')
    {
        $this->obj = new Alipay();
        $this->obj->method = 'alipay.trade.precreate';
        $this->obj->api = 'Alipay.request.AlipayTradePrecreateRequest';
        $this->obj->notify_url = $this->notify_url;
        $this->obj->config = empty($config)?$this->config:$config;
        $this->obj->ali_obj();
        $this->obj->AlipayTradePrecreateRequest();
        return $this->alipay_trade_precreate_launch($data);

    }

    /*
     *note:支付信息封装
     *auth:YW
     *date:2018/05/30
     */
    private function alipay_trade_precreate_launch($data)
    {
        $data['extend_params'] = $this->extend_params($data);
        $bizcontent = $this->set_precreate_bizcontent($data);
        $this->obj->ali_request->setNotifyUrl($this->obj->notify_url);//设置回调地址
        $this->obj->ali_request->setBizContent($bizcontent);//设置订单参数
        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $this->obj->ali_aop->execute($this->obj->ali_request);
        return $response;
    }

    /*
     *note:支付设置预下单参数
     *auth:YW
     *date:2018/05/30
     * input $subject，$out_trade_no，$total
     */
    private function set_precreate_bizcontent($data)
    {
        $bizcontent = array(
            'timeout_express' => '30m',     //时间
            'total_amount' => $data['total'],
            'subject' => $data['title'],
            'out_trade_no' => $data['order_no'],
            'extend_params' => $data['extend_params'],      //分期信息
        );
        $bizcontent = json_encode($bizcontent);
        return $bizcontent;
    }

    /*************************************************[网站支付]华丽的分割线************************************************************/
    /*
     *note:PC场景下单并支付
     *auth:YW
     *date:2018/05/30
     */
    public function alipay_trade_page_pay($data,$config = '')
    {
        $this->obj = new Alipay();
        $this->obj->method = 'alipay.trade.page.pay';
        $this->obj->api = 'Alipay.request.AlipayTradePagePayRequest';
        $this->obj->notify_url = $this->notify_url;
        $this->obj->config = empty($config)?$this->config:$config;
        $this->obj->ali_obj();
        $this->obj->AlipayTradePagePayRequest();
        return $this->alipay_trade_page_pay_launch($data);
    }

    /*
     *note:支付信息封装
     *auth:YW
     *date:2018/05/30
     */
    private function alipay_trade_page_pay_launch($data)
    {
        $data['extend_params'] = $this->extend_params($data);
        $data['product_code'] = 'FAST_INSTANT_TRADE_PAY';
        $bizcontent = $this->set_page_bizcontent($data);
        $this->obj->ali_request->setNotifyUrl($this->obj->notify_url);//设置回调地址
        $this->obj->ali_request->setBizContent($bizcontent);//设置订单参数
        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $this->obj->ali_aop->pageExecute($this->obj->ali_request);
        return $response;
    }

    /*
     *note:支付设置预下单参数
     *auth:YW
     *date:2018/05/30
     * input $subject，$out_trade_no，$total
     */
    private function set_page_bizcontent($data)
    {
        $bizcontent = array(
            'timeout_express' => '30m',     //时间
            'product_code' => $data['product_code'],
            'total_amount' => $data['total'],
            'subject' => $data['title'],
            'out_trade_no' => $data['order_no'],
            'extend_params' => $data['extend_params'],      //分期信息
        );
        $bizcontent = json_encode($bizcontent);
        return $bizcontent;
    }

    /*************************************************[退款接口]华丽的分割线************************************************************/
    /*
     *note:统一收单交易退款接口
     *auth:YW
     *date:2018/05/30
     */
    public function alipay_trade_refund($data)
    {
        $this->obj = new Alipay();
        $this->obj->method = 'alipay.trade.refund';
        $this->obj->api = 'Alipay.request.AlipayTradeRefundRequest';
        $this->obj->notify_url = $this->notify_url;
        $this->obj->config = $this->config;
        $this->obj->ali_obj();
        $this->obj->AlipayTradeRefundRequest();
        return $this->alipay_trade_refund_launch($data);
    }

    /*
     *note:支付信息封装
     *auth:YW
     *date:2018/05/30
     */
    private function alipay_trade_refund_launch($data)
    {

        $bizcontent = $this->set_refund_bizcontent($data);
        $this->obj->ali_request->setNotifyUrl($this->obj->notify_url);//设置回调地址
        $this->obj->ali_request->setBizContent($bizcontent);//设置订单参数
        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $this->obj->ali_aop->execute($this->obj->ali_request);
        $responseNode = str_replace(".", "_", $this->obj->ali_request->getApiMethodName()) . "_response";
        $response = $response->$responseNode;
        return $response;
    }

    /*
     *note:支付设置预下单参数
     *auth:YW
     *date:2018/05/30
     * input $subject，$out_trade_no，$total
     */
    private function set_refund_bizcontent($data)
    {
        $bizcontent = array(
            'out_trade_no' => $data['order_no'],     //订单支付时传入的商户订单号,不能和 trade_no同时为空。
            'trade_no' => $data['trade_no'],
            'refund_amount' => $data['refund_amount'],
        );
        $bizcontent = json_encode($bizcontent);
        return $bizcontent;
    }

    /*************************************************华丽的分割线************************************************************/


    /*
     *note:可用渠道，用户只能在指定渠道范围内支付 当有多个渠道时用“,”分隔 注，与disable_pay_channels互斥
     *auth:YW
     *date:2018/05/30
     * input sys_service_provider_id[系统商签约协议的PID],hb_fq_num,hb_fq_seller_percent
     */
    private function extend_params($data)
    {

        $params = array(
            'sys_service_provider_id' => isset($data['sys_service_provider_id'])?$data['sys_service_provider_id']:'',        //系统商编号
            'hb_fq_num' => isset($data['hb_fq_num'])?$data['hb_fq_num']:'',      //分期数
            'hb_fq_seller_percent' => isset($data['hb_fq_seller_percent'])?$data['hb_fq_seller_percent']:'',   //代表卖家承担收费比例，商家承担手续费传入100，用户承担手续费传入0，仅支持传入100、0两种，其他比例暂不支持，传入会报错。
        );
        return json_encode($params);

    }

}