<?php
namespace app\common\controller;

/*授权*/

class Aliauth extends Alipay
{
    private $obj;

    /*
     *note:初始化
     *auth:杨炜
     *date:2018/05/30
     */
    public function _init()
    {
        $where['type'] = 'alipay';
        $res = D('Interface')->where($where)->find();
        //获取配置文件
        $this->config = array('merchant'=>$res['merchant'],'gateway'=>$res['gateway'],'appid'=>$res['appid'],'rsaprivatekey'=>$res['rsaprivatekey'],'rsapublickey'=>$res['rsapublickey']);

    }

    /*************************************************华丽的分割线************************************************************/

    /*
     *note:授权
     *auth:杨炜
     *date:2018/05/30
     */
    public function set_auth($data,$config = '')
    {
        $this->obj = new AlipayController();
        $this->obj->method = 'alipay.open.auth.token.app';
        $this->obj->api = 'Alipay.request.AlipayOpenAuthTokenAppRequest';
        $this->obj->notify_url = 'http://www.baidu.com';
        $this->obj->config = $this->config;
        $this->obj->ali_obj();
        $this->obj->AlipayOpenAuthTokenAppRequest();
        return $this->set_auth_launch($data);
    }

    /*
     *note:授权信息封装
     *auth:杨炜
     *date:2018/05/30
     */
    private function set_auth_launch($data)
    {
        $bizcontent = $this->set_auth_bizcontent($data);
        $this->obj->ali_request->setNotifyUrl($this->obj->notify_url);//设置回调地址
        $this->obj->ali_request->setBizContent($bizcontent);//设置订单参数
        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $this->obj->ali_aop->execute($this->obj->ali_request);//执行
        return $response;
    }

    /*
     *note:设置参数
     *auth:杨炜
     *date:2018/05/30
     * input $grant_type[authorization_code或者refresh_token]，$code，$refresh_token
     */
    private function set_auth_bizcontent($data)
    {
        $bizcontent = array(
            'grant_type' => $data['grant_type'],     //时间
            'code' => $data['code'],
            'refresh_token' => $data['refresh_token'],
        );
        $bizcontent = json_encode($bizcontent);
        return $bizcontent;
    }

    /*************************************************华丽的分割线************************************************************/

    /*
     *note:授权查询
     *auth:杨炜
     *date:2018/05/30
     */
    public function get_auth()
    {
        $this->obj = new AlipayController();
        $this->obj->method = 'alipay.open.auth.token.app.query';
        $this->obj->api = 'Alipay.request.AlipayOpenAuthTokenAppQueryRequest';
        $this->obj->notify_url = 'http://www.baidu.com';
        $this->obj->config = $this->config;
        $this->obj->ali_obj();
        $this->obj->AlipayOpenAuthTokenAppQueryRequest();
        return $this->get_auth_launch();
    }

    /*
     *note:查询授权信息封装
     *auth:杨炜
     *date:2018/05/30
     */
    private function get_auth_launch($data)
    {
        $bizcontent = $this->get_auth_bizcontent($data);
        $this->obj->ali_request->setNotifyUrl($this->obj->notify_url);//设置回调地址
        $this->obj->ali_request->setBizContent($bizcontent);//设置订单参数
        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $this->obj->ali_aop->execute($this->obj->ali_request);//执行
        return $response;
    }

    /*
     *note:设置参数
     *auth:杨炜
     *date:2018/05/30
     * input $grant_type[authorization_code或者refresh_token]，$code，$refresh_token
     */
    private function get_auth_bizcontent($data)
    {
        $bizcontent = array(
            'app_auth_token' => $data['app_auth_token'],     //时间
        );
        $bizcontent = json_encode($bizcontent);
        return $bizcontent;
    }

    /*************************************************华丽的分割线************************************************************/


    public function _empty()
    {
        header('HTTP/1.1 404 Not Found');
    }
}