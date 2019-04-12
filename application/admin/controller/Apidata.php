<?php
namespace app\admin\controller;

use app\admin\common\controller\Init;
use think\Db;

/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：法条管理
 */
class Apidata extends Init
{

    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'] . 'counter';
    }
    /**
     * @auth PT
     * @date 2019.3.1
     * @purpose 列表
     * @return void
     */
    public function index()
    {
        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $map['is_success'] = '0';
        $where['where'] = $map;
// var_dump($where);exit;
        $this->_list('', $where);
        return view();
    }

    public function _filter(&$map)
    {
        $get = $this->request->get();
        if (!empty($get['type'])) {
            $map['interface_name'] = $get['type'];
        }else{
            $map['interface_name'] = 'SMS';
        }
        if (!empty($get['begintime'])) {
            $str = strtotime($get['begintime']).",".strtotime($get['endtime']);
            $map['add_time'] = array('between',$str);
        }
        $this->checkSearch($map);
    }


    /**
     * @auth PT
     * @date 2019.3.1
     * @purpose 列表展示前反序列化图片
     * @return void
     */
    public function _after_list(&$list)
    {
        // 短信成功接口次数
        $sms_s_where['is_success'] = '1';
        $sms_s_where['interface_name'] = 'SMS';
        $sms_s_count =  $this->obj->table($this->table)->where($sms_s_where)->count();
        // 短信失败接口次数
        $sms_e_where['is_success'] = '0';
        $sms_e_where['interface_name'] = 'SMS';
        $sms_e_count =  $this->obj->table($this->table)->where($sms_e_where)->count();

        // 聊天成功接口次数
        $chat_s_where['is_success'] = '1';
        $chat_s_where['interface_name'] = 'chat';
        $chat_s_count =  $this->obj->table($this->table)->where($chat_s_where)->count();
        // 聊天失败接口次数
        $chat_e_where['is_success'] = '0';
        $chat_e_where['interface_name'] = 'chat';
        $chat_e_count =  $this->obj->table($this->table)->where($chat_e_where)->count();

        // 支付成功接口次数
        $pay_s_where['is_success'] = '1';
        $pay_s_where['interface_name'] = 'PAY';
        $pay_s_count =  $this->obj->table($this->table)->where($pay_s_where)->count();
        // 支付失败接口次数
        $pay_e_where['is_success'] = '0';
        $pay_e_where['interface_name'] = 'PAY';
        $pay_e_count =  $this->obj->table($this->table)->where($pay_e_where)->count();

        $this->assign('all_res', $list);
        $this->assign('sms_s_count', '成功：' . $sms_s_count);
        $this->assign('sms_e_count', '失败：' . $sms_e_count);
        $this->assign('chat_s_count', '成功：' . $chat_s_count);
        $this->assign('chat_e_count', '失败：' . $chat_e_count);
        $this->assign('pay_s_count', '成功：' . $pay_s_count);
        $this->assign('pay_e_count', '失败：' . $pay_e_count);
    }



}
