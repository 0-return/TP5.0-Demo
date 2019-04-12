<?php
namespace app\common\controller;
use Iapi\Controller\IndexController;
use JMessage\JMessage;
use JMessage\IM\Report;
/**
 * auth YW
 * note 聊天，推送
 * date 2018-08-06
 */
class Msg extends IndexController {
    public $client;
    private $key;
    private $secret;
    private $url = 'https://report.im.jpush.cn/v2';
    private $report;

    /**
     * auth YW
     * note 初始化
     * date 2019-01-02
     */
    public function __construct()
    {
        parent::_init();

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
     * note 获取聊天记录
     * date 2019-01-02
     */
    public function jmsg($config = '',$isfirst = true,$data = '')
    {

        $this->client = new JMessage($this->key,$this->secret);
        $this->report = new Report($this->client);
        if ($isfirst)
        {
            $config['begin'] = !empty($config['begin'])?$config['begin']:date('Y-m-d H:i:s',strtotime(date('Y-m-d',time()))-86400);
            $config['end'] = !empty($config['end'])?$config['end']:date('Y-m-d H:i:s',strtotime(date('Y-m-d',time()))-1);

            $res = $this->report->getMessages($config['num'], $config['begin'], $config['end']);
            $total = $res['body']['total']; if ($total) $config['total'] = $total;
            $cursor = $res['body']['cursor']; if ($cursor) $config['cursor'] = $cursor;
            $page = ceil($total/$config['num']); if ($page) $config['page'] = $page;
            if (!empty($cursor))
            {
                self::jmsg($config,$isfirst = false,$res);
            }
        }else{
            if ($data)
            {

                self::write($data,$config);
                self::jmsg($config,$isfirst = false);
            }else{
                $res = $this->report->getNextMessages($config['cursor']);
                if ($res['body']['messages'])
                {
                    $config['cursor'] = $res['body']['cursor'];

                    self::write($res,$config);
                    self::jmsg($config,$isfirst = false);
                }
            }
        }
    }

    /**
     * auth YW
     * note 将记录写入数据库
     * date 2019-01-04
     */
    private function write($res,$config)
    {

        $count = $config['num'];
        $i = 0;
        foreach ($res['body']['messages'] as $key => $value)
        {
            if ($value['msg_body']['extras']['order']) $data['chat_no'] = $value['msg_body']['extras']['order'];
            $data['from_type'] = preg_replace('|[0-9/]+|','',$value['from_id']) == 'lsd'?'0':'1';
            $data['msgid'] = $value['msgid'];                             //信息id
            $data['fromId'] = preg_replace('|[a-zA-Z/]+|','',$value['from_id']);                          //发送者
            $data['fromTime'] = $value['create_time'];                    //发送时间
            $data['toId'] = preg_replace('|[a-zA-Z/]+|','',$value['target_id']);                          //接收者
            $data['toTime'] = $value['msg_ctime'];                        //接收时间
            $data['isRead'] = 1;
            $data['status'] = '1';
            $data['addTime'] = time();
            switch ($value['msg_type'])
            {
                case 'text':
                    $data['contentType'] = $value['msg_type'];                    //信息类型
                    $data['content'] = $value['msg_body']['text'];                //内容
                    break;

                case 'image':
                    $data['contentType'] = $value['msg_type'];                    //信息类型
                    $data['content'] = $value['msg_body']['media_id'];           //内容
                    break;

                case 'voice':
                    $data['contentType'] = $value['msg_type'];                    //信息类型
                    $data['content'] = $value['msg_body']['media_id'];           //内容
                    break;

                case 'video':
                    $data['contentType'] = $value['msg_type'];                    //信息类型
                    $data['content'] = $value['msg_body']['media_id'];           //内容
                    break;

                default:
                    $data['contentType'] = $value['msg_type'];                    //信息类型
                    $data['content'] = $value['msg_body']['extras']['text'];                //内容
                    break;

            }
            $res = $this->obj->table('fwy_chatlog')->add($data);
            if ($res)
            {
                $i++;
                file_put_contents("Logs/Jmsg/Jmsg_".date('Y-m-d',time()).".txt","id：{$data['msgid']}-写入成功".PHP_EOL,FILE_APPEND);
            }else{
                file_put_contents("Logs/Jmsg/Jmsg_".date('Y-m-d',time()).".txt","id：{$data['msgid']}-写入失败".PHP_EOL,FILE_APPEND);
            }
        }
    }
    /**
     * auth YW
     * note 下载媒体文件
     * date 2019-01-04
     */
    private function download()
    {

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