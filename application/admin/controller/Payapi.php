<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;

/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：支付接口管理
 */
class Payapi extends Init
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
        $this->table = $this->config['prefix'].'payapi';
    }

    public function index()
    {
        $post = $this->request->Post();
        if ($post)
        {
            $where['id'] = $post['id'];
            $res = $this->obj[1]->table($this->table)->where($where)->find();
            if ($res)
            {
                $res = $this->obj[1]->table($this->table)->where($where)->update($post);
            }else{
                $res = $this->obj[1]->table($this->table)->add($post);
            }

            if ($res)
            {
                echoMsg('10000',$this->message['success']);
            }else{
                echoMsg('10001',$this->message['error']);
            }
        }else{
            $where['type'] = 'alipay';
            $alipay = $this->obj[1]->table($this->table)->where($where)->find();

            $where['type'] = 'wxpay';
            $wxpay = $this->obj[1]->table($this->table)->where($where)->find();

            $sms = array('alipay' => $alipay,'wxpay'=> $wxpay);
            $this->assign('vo', $sms);
            return view();
        }
    }





}