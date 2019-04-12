<?php
namespace app\uapi\common\controller;
use think\Controller;
/*
 * 短信
 * */
class Jhsms extends Controller {

    private $url = 'http://v.juhe.cn/sms/send';
    private $params;

    /**
     * @User YW
     * @date 20181205
     * @param $mobile 接收短信的手机号码
     * @param $tpl_id 短信模板ID，请参考个人中心短信模板设置
     * @param $tpl_value 变量名和变量值
     * @param $key 秘钥
     * @param $dtype    返回数据的格式,xml或json，默认json
     * @return bool|Exception
     */
    public function __construct(&$config)
    {
        $this->params = array(
            'key' => $config['appid'],
            'dtype' => 'json',
        );
    }
    /**
     * @User YW
     * @date 20181205
     * @note 发送短信
     * @return bool|Exception
     */
    public function Jh_send($data)
    {
        $params = array_merge($data,$this->params);
        $paramstring = http_build_query($params);
        $content = Curl($this->url, $paramstring);
        //$content = true;
        if ($content) {
            return true;
        } else {
            return false;
        }
    }



}