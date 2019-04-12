<?php
namespace app\common\controller;
use think\Controller;
Vendor('jpush.autoload');
use JPush\Client as JPush;

/**
 * auth YW
 * note 推送
 * date 2018-08-06
 */
class Push extends Controller {

    private $client;
    private $key;
    private $secret;
    private $url = 'https://api.jpush.cn/v3/push';
    /**
     * auth YW
     * note 初始化
     * date 2019-01-02
     */
    public function __construct()
    {

    }

    public function __get($name)
    {
        if ($name)
        {
            return $this->$name;
        }
    }

    public function __set($name, $value)
    {
        if (!empty($name))
        {
            $this->$name = $value;
        }
    }
    /**
     * auth YW
     * note 极光推送
     * date 2019-01-07
     */
    public function pushs($data)
    {

        $this->client = new JPush($this->key, $this->secret);
        $alert = htmlspecialchars_decode($data['alert']);
        $extras = json_decode(htmlspecialchars_decode($data['extras']),true);
        $platform = ['ios', 'android'];         //设备
        $options = array(                       //参数设置
            'apns_production' => true ,
            'sendno' => 100,
        );
        $ios_notification = array(
            "sound" => "default",
            'extras' => $extras,
        );
        $android_notification = array(
            'extras' => $extras,
        );

        try {

            $response = $this->client->push()->setPlatform($platform)
                ->addAllAudience($data['alias'])
                ->iosNotification($alert, $ios_notification)
                ->androidNotification($alert, $android_notification)
                ->options($options)
                ->send();
            if ($response) {
                return "SUCCESS";
                exit(0);
            }else{

                return "FAIL";
                exit(0);
            }
        } catch (\JPush\Exceptions\JPushException $e) {
            return "FAIL";
            exit(0);
        }
    }


    /**
     * auth YW
     * note 空操作
     * date 2018-08-06
     */
    public function _empty(){
        $msg['code'] = '10103';
        $msg['msg'] = '操作不合法！';
        return $msg;
    }

}