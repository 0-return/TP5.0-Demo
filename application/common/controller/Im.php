<?php
namespace app\common\controller;
use JMessage\IM\User;
use JMessage\JMessage;

set_time_limit(0);
/**
 * Create by .
 * Cser Administrator
 * Time 11:48
 * Note 律师，用户状态监听
 */
class Im{

    public $client;
    private $key;
    private $secret;
    private $url = 'https：//api.im.jpush.cn';
    private $report;
    private $obj;

    public function __construct()
    {
        vendor('jmessage.src.JMessage.IM');
        vendor('jmessage.src.JMessage.Http');
        vendor('jmessage.src.JMessage.JMessage');
        vendor('jmessage.src.JMessage.IM.User');
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
     * note 修改律师状态（耗费大量资源，建议间隔5分钟轮询一次）
     * date 2019-03-15
     */
    public function checkLawyerStatus($data = '',$i = 1)
    {


        $prefix = 'lsd';
        $this->client = new JMessage($this->key,$this->secret);
        $user = new User($this->client);
        //$res = $user->stat('lsd140');           //测试
        $condition['where']['status'] = '2';
        $condition['field'] = 'uid';
        $data = self::getData('fwy_lawyer',$condition); unset($condition);
        if ($data)
        {
            foreach ($data as $key => $value)
            {
                $res = $user->stat($prefix.$value['uid']);
                if (isset($res['body']['error']))
                {
                    $condition['where']['uid'] = $value['uid'];
                    $condition['data']['value'] = '0';
                    self::editData('fwy_lawyer',$condition);
                }else{
                    if ($res['body']['online'] === true)
                    {
                        $condition['where']['uid'] = $value['uid'];
                        $condition['data']['value'] = '1';
                        self::editData('fwy_lawyer',$condition);
                    }else{
                        $condition['where']['uid'] = $value['uid'];
                        $condition['data']['value'] = '0';
                        self::editData('fwy_lawyer',$condition);
                    }
                }
            }
        }


    }
    /**
     * auth YW
     * note 修改用户状态（耗费大量资源）
     * date 2019-03-15
     */
    public function checkUserStatus()
    {

    }

    private function getData($table = '',$condition = '')
    {
        $condition['where'] = isset($condition['where'])?$condition['where']:$condition['where']['status'] = '1';
        $res = $this->obj->table($table)->field($condition['field'])->where($condition['where'])->select();
        return $res?$res:false;
    }

    private function editData($table = '',$condition = '')
    {
        $condition['where'] = isset($condition['where'])?$condition['where']:$condition['where']['status'] = '1';
        $data['online'] = $condition['data']['value'];
        $res = $this->obj->table($table)->where($condition['where'])->update($data);
        return $res?true:false;
    }
}