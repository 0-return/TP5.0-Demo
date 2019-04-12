<?php
namespace app\server\controller\v3;
use app\common\controller\Common;
use app\common\controller\Msg;
use app\common\controller\Im;
use think\Controller;
use think\Db;

set_time_limit(0);
/**
 * Create by .
 * Cser Administrator
 * Time 14:19
 * Note 自动执行类
 */
class Server extends Controller
{

    public $obj;
    public $db = 'DB_C1';
    private $sms_config;

    public function _initialize()
    {
        $this->obj = Db::connect(config("database.{$this->db}"));
        $where['status'] = '1';
        $where['type'] = 'Jhsms';
        $this->sms_config = $this->obj->table('fwy_sms_config')->where($where)->find();
    }


    /**
     * @auth YW
     * @date 2019-03-14
     * @purpose 定时短信
     * @return void
     */
    public function setInterval_sms()
    {

        $sms = $this->obj->table('fwy_config')->where('id=1')->value('sms');
        if ($sms == '1') {
            $w['status'] = '3';
            $res = $this->obj->table('fwy_sms')->where($w)->select();
            $config['prefix'] = 'fwy_';
            foreach ($res as $k => $v) {
                $tpl_id = Common::msgConf($this->obj, $config, $v);
                $phone = json_decode($v['target'], true);
                if ($phone) {
                    $count = count($phone);
                    $i = 0;
                    foreach ($phone as $key => $value) {
                        $sms = array(
                            'phone' => $value,
                            'tpl_id' => $tpl_id,
                        );
                        $send = Jhsms($sms, $this->sms_config);
                        if ($send === true) {
                            $i++;
                            $str[] = $value;
                        } else {
                            $str[] = $value;
                        }
                        sleep(1);
                    }
                    if ($count > 0 && $count == $i) {
                        $data['status'] = '1';
                    } elseif ($count - $i == 0) {
                        $data['status'] = '2';
                    } else {
                        $data['status'] = '0';
                    }
                    $where['id'] = $v['id'];
                    $this->obj->table('fwy_sms')->where($where)->update($data);
                }
            }
        }

    }

    /**
     * @auth YW
     * @date 2019-03-14
     * @purpose 定时删除临时订单
     * @return void
     */
    public function setInterval_ortmp()
    {
        $w['status'] = '-1';
        $res = $this->obj->table('fwy_ortemp')->where($w)->delete();
        if ($res) {
            // 日志表数据
            $data['uid'] = '0';
            $data['describe'] = "[系统]操作了删除操作-[编号：" . $res . "]-[说明：系统自动执行删除临时订单]";
            $data['username'] = 'system';
            $data['explain'] = '系统自动执行删除临时订单';
            $data['ip'] = '127.0.0.1';
            $data['content'] = '系统自动执行删除临时订单';
            $data['addtime'] = time();
            $data['status'] = '1';
            $this->obj->table('fwy_log_sys')->insert($data);
        }
    }

    /**
     * @auth YW
     * @date 2019-03-14
     * @purpose 定时拉取律师聊天记录
     * @return void
     */
    public function setInterval_chat_by_lawyer()
    {
        $chat = new Msg();
        $chat->key = '0d0eee6530276ace5a9713f0';
        $chat->secret = '7635652bbb7fe7ca61c3e324';
        $chat->obj = $this->obj;
        $config['num'] = 1000;
        $chat->jmsg($config);
    }

    /**
     * @auth YW
     * @date 2019-03-14
     * @purpose 定时拉取用户聊天记录
     * @return void
     */
    public function setInterval_chat_by_user()
    {
        $chat = new Msg();
        $chat->key = '0d0eee6530276ace5a9713f0';
        $chat->secret = '7635652bbb7fe7ca61c3e324';
        $chat->obj = $this->obj;
        $config['num'] = 1000;
        $chat->jmsg($config);
    }

    /**
     * @auth YW
     * @date 2019-03-15
     * @purpose 律师状态修改
     * @return void
     */
    public function setInterval_status_by_lawyer()
    {

        $im = new Im();
        $im->key = '29399beed8b26c070e45ea93';
        $im->secret = '0432478feb3ec8917e42d6f4';
        $im->obj = $this->obj;
        $im->checkLawyerStatus();
    }

}