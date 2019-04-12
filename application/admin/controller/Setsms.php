<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Db;

/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：短信管理
 */
class Setsms extends Init
{
    /**
     * @auth YW
     * @date 2017.12.2
     * @purpose 初始化
     * @return void
     */
    public function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'] . 'sms_config';
    }

    public function index()
    {

        $post = $this->request->Post();
        if ($post)
        {
            if ($post['status'] == '1')                                             //如果有有修改状态的，则要把其他状态全部重置
            {
                $where['status'] = array('gt','-1');
                $data['status'] = '0';
                $this->obj->table($this->table)->where($where)->update($data);      //将其他全部设置为关闭
                unset($where);
            }

            $where['id'] = $post['id'];
            $res = $this->obj->table($this->table)->where($where)->find();
            if ($res)
            {
                $res = $this->obj->table($this->table)->where($where)->update($post);
            }else{
                $res = $this->obj->table($this->table)->add($post);
            }

            if ($res)
            {
                echoMsg('10000',$this->message['success']);
            }else{
                echoMsg('10001',$this->message['error']);
            }
        }else{
            $where['type'] = 'Alsms';
            $sms = $this->obj->table($this->table)->where($where)->find();

            $where['type'] = 'Jhsms';
            $jh_sms = $this->obj->table($this->table)->where($where)->find();

            $where['type'] = 'Clysms';
            $cly_sms = $this->obj->table($this->table)->where($where)->find();

            $sms = array('sms' => $sms,'jh'=> $jh_sms,'cly' => $cly_sms);
            $this->assign('vo', $sms);
            return view();
        }
    }




}