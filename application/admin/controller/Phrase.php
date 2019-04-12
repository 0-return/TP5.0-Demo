<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Db;
use Think\Exception;

/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：律师观点
 */
class Phrase extends Init
{
    private $sms_config;

    /**
     * @auth YW
     * @date 2017.12.2
     * @purpose 初始化
     * @return void
     */
    public function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'].'lawyer_shortxt';
        $where['status'] = '1';
        $where['type'] = 'Jhsms';
        $this->sms_config = $this->obj->table($this->config['prefix'].'sms_config')->where($where)->find();
    }

    public function index()
    {
        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $map['status'] = array('gt',-1);
        $where['where'] = $map;
        $this->_list('',$where);
        return view();
    }

    public function _filter(&$map)
    {
        $get = $this->request->get();
        if (!empty($get['begintime']) && !empty($get['endtime']))
        {
            $map['addtime'] = array('between',array(strtotime($get['begintime']),strtotime($get['endtime'])));
        }
        $this->checkSearch($map);
    }

    public function _after_list(&$list)
    {
        foreach ($list as $key => $value)
        {
            $where['uid'] = $value['uid'];
            $res = $this->obj->table($this->config['prefix'].'lawyer')->field('username')->where($where)->find();
            $list[$key]['username'] = $res['username'];
        }
    }

    public function _after_delete(&$id)
    {
        $where['sid'] = array('in',$id);
        $this->obj->table($this->config['prefix'].'article_main')->where($where)->delete();
        echoMsg('10000',$this->message['success']);
    }


    /**
     * @auth PT
     * @date 2019.3.1
     * @purpose 预览
     * @return void
     */

    public function preview()
    {
        $get = $this->request->request();
        $where['id'] = $get['id'];
        $res = $this->obj->table($this->table)->where($where)->find();
        $this->assign('vo', $res);
        return view();
    }
}