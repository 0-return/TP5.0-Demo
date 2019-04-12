<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use app\common\controller\Common;

/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：短信消息管理
 */
class Smsmsg extends Init
{
    private $sms_config;
    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'] . 'sms';
        $where['status'] = '1';
        $where['type'] = 'Jhsms';
        $this->sms_config = $this->obj[1]->table($this->config['prefix'].'sms_config')->where($where)->find();
    }

    /**
     * @auth YW
     * @date 2018.11.19
     * @purpose 列表
     * @return void
     */
    public function index()
    {
        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $map['where']['status'] = array('gt','-1');
        $this->_list('',$map);
        return view();
    }

    public function _before_add(&$list)
    {
        if ($this->request->isPost())
        {

        }else{
            $res = $this->obj[1]->table($this->config['prefix'] . 'sms_jh_module')->select();
            $this->assign('list',$res);
        }
    }
    /**
     * @auth YW
     * @date 2019.03.07
     * @purpose 发送短信操作
     * @return void
     */
    public function _before_anything()
    {
        if($this->config['sms'] == '0')
        {
            echoMsg('10008',"提示：短信功能已关闭，请联系管理员开启！");
        }
        ini_set('max_execution_time','0');      //运行最大时间
        if ($this->request->isPost())
        {
            $post = $this->request->Post();
            $modult_code = Common::msgConf($this->obj[1],$this->config,$post);

            $where = '';
            switch ($post['send_type'])
            {
                case '1':           //用户端
                    if (isset($post['status'])) $where['status'] = $post['status'];
                    self::send_msg('member',$modult_code,$post,$where);
                    break;
                case '2':           //律师端
                    if (isset($post['status'])) $where['status'] = $post['status'];
                    self::send_msg('lawyer',$modult_code,$post,$where);
                    break;
                case '3':           //所有端
                    self::send_msg('member',$modult_code,$post);
                    self::send_msg('lawyer',$modult_code,$post);
                    break;
            }

        }
    }
    /**
     * @auth YW
     * @date 2019.03.07
     * @purpose 发送短信
     * @return void
     */
    private function send_msg($db,$modult_code,&$list = '',$where = '')
    {
        set_time_limit(0);
        if (isset($list['target']))
        {
            $phoneArr = explode(',',trim($list['target'],','));
        }

        if(isset($list['target']) && count($phoneArr) > 0)          //自定义发送
        {

            $count = count($phoneArr);
            $i = 0;
            foreach ($phoneArr as $value)
            {
                $list['type'] = '2';
                $sms = array(
                    'phone' => $value,
                    'tpl_id' => $modult_code,
                );

                $send = Jhsms($sms,$this->sms_config);
                //$send = true;
                if ($send === true)
                {
                    $i++;
                    $str[] = $value;
                }else{
                    $str[] = $value;
                }
                sleep(1);
            }
            if ($count > 0 && $count == $i)
            {
                $list['status'] = '1';
            }elseif($count - $i == 0){
                $list['status'] = '2';
            }else{
                $list['status'] = '0';
            }
            $list['target'] = json_encode($str);
            self::save_date($list);
            $range = $count - $i;
            $msg = json_encode($send);
            echoMsg('10000',"提示：{$i}条短信发送成功，{$range}条短信发送失败，{$msg}");

        }else{

            $list['type'] = '1';

            if ($where)
            {
                $res = $this->obj[1]->table($this->config['prefix'].$db)->where($where)->select();
            }else{
                $res = $this->obj[1]->table($this->config['prefix'].$db)->select();
            }

            $count = count($res);
            $i = 1;
            foreach ($res as $key => $value)
            {
                if ($list['send_type'] == '1') $client = 'username';
                if ($list['send_type'] == '2') $client = 'phone';

                $sms = array(
                    'phone' => $value[$client],
                    'tpl_id' => $modult_code,
                );

                $send = Jhsms($sms,$this->sms_config);
                //$send = true;
                if ($send === true)
                {
                    $str[] = $value[$client];
                    $i++;
                }else{
                    $str[] = $value[$client];
                }
            }

            if ($count > 0 && $count == $i)
            {
                $list['status'] = '1';
            }elseif($count - $i = 0){
                $list['status'] = '2';
            }

            $list['target'] = json_encode($str);
            self::save_date($list);
            $range = $count - $i;
            echoMsg('10000',"提示：{$i}条短信发送成功，{$range}条短信发送失败");

        }

    }
    /**
     * @auth YW
     * @date 2019.03.07
     * @purpose 存储短信发送记录
     * @return void
     */
    private function save_date(&$data)
    {
        $data['add_time'] = time();
        $res = $this->obj[1]->table($this->table)->insert($data);
        return $res?true:false;
    }


}