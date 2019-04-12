<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use app\common\controller\Common;
use app\common\controller\Jhsms;
use think\Db;
/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：会员管理
 */
class Member extends Init
{
    private $sms_config;
    private $status = array(
        '-1'        =>  '<span class="label label-danger radius">已删除</span>',
        '0'         =>  '<span class="label label-default radius">已禁用</span>',
        '1'         =>  '<span class="label label-success radius">使用中</span>',
    );

    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'].'member';
        $where['status'] = '1';
        $where['type'] = 'Jhsms';
        $this->sms_config = $this->obj[1]->table($this->config['prefix'].'sms_config')->where($where)->find();
    }


    /**
     * @auth YW
     * @date 2018.11.29
     * @purpose 列表
     * @return void
     */
    public function index()
    {

        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $map['status'] = array('gt','-1');
        $where['where'] = $map;
        $this->_list('',$where  );
        return view();
    }

    private function _filter(&$map)
    {

        $get = $this->request->get();
        if (!empty($get['begintime']) && !empty($get['endtime']))
        {
            $map['add_time'] = array('between',array(strtotime($get['begintime']),strtotime($get['endtime'])));
        }

        $this->checkSearch($map);
    }

    protected function _after_list(&$list)
    {
        foreach ($list as $key => $value)
        {
            $list[$key]['status_cn'] = $this->status[$value['status']];

        }

    }

    public function showDetail()
    {
        $get = $this->request->get();
        $fields = 'id,uid,face,username,nickname,sex,province_cn,city_cn,area_cn,detail,qq,wechat,add_time,status,email,vipdie_time,isvip,isfenpeilayer';
        $res = $this->obj[1]->table($this->config['prefix'].'member')->field($fields)->where('id', '=', $get['id'])->find();
        if($res)
        {
            $res['area'] = $res['province_cn'].$res['city_cn'].$res['area_cn'];
            $res['weburl'] = $this->config['weburl'];
        }
        $this->assign('vo',$res);
        return view('detail');
    }


    public function sendMsg()
    {

        if ($this->request->isPost())
        {
            if($this->config_sub['sms'] == '0')
            {
                echoMsg('10008',"提示：短信功能已关闭，请联系管理员开启！");
            }

            $post = $this->request->Post();
            $sms = array(
                'phone' => $post['target'],
                'tpl_id' => 120832,
            );

            $send = Jhsms($sms,$this->sms_config);
            //$send = true;
            $i = '1';

            if ($send === true)
            {
                $post['status'] = '1';
                $post['target'] = json_encode($post['target']);
                self::save_date($post);
                echoMsg('10000',"提示：{$i}条短信发送成功");
            }else{
                $post['status'] = '0';
                $post['target'] = json_encode($post['target']);
                self::save_date($post);
                $msg = json_encode($send);
                echoMsg('10001',"提示：{$i}条短信发送失败，{$msg}");
            }
        }else{
            $get = $this->request->get();
            $res = $this->obj[1]->table($this->config['prefix'] . 'sms_jh_module')->select();
            $this->assign('v',$get);
            $this->assign('list',$res);
            return view('sms');
        }
    }

    public function _before_update(&$post)
    {
        if ($this->request->isPost())
        {
            if (isset($post['password']) && $post['password'] != '')
            {
                $post['password'] = md5($post['password']);
            }
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
        $this->table = $this->config['prefix'] . 'sms';
        $data['add_time'] = time();
        $res = $this->obj[1]->table($this->table)->insert($data);
        return $res?true:false;
    }


}