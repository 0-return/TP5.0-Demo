<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Db;

/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：律所管理
 */
class Office extends Init
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
        $this->table = $this->config['prefix'].'lawfirm';
    }

    public function index()
    {
        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $map['status'] = array('gt','0');
        $where['where'] = $map;
        $this->_list('',$where);
        return view();
    }

    public function _after_list(&$list)
    {
        foreach ($list as $key => $value)
        {
            $str = $value['province_cn'].'-'.$value['city_cn'].'-'.$value['area_cn'];
            $list[$key]['address'] = $str;
        }
    }

    public function _before_delete(&$post)
    {
        $where['lawfirm_id'] = $post['id'];
        $res = $this->obj->table($this->config['prefix'].'lawyer')->where($where)->find();
        if ($res)
        {
            echoMsg('10001',$this->message['lawfirm_delete_is_fail']);exit;
        }
    }

    /**
     * note:获取律师事务所
     * auth:杨炜
     * date:2018/05/18
     */
    public function getLasfirm()
    {
        $post = $this->request->Post();
        $where['name'] = array('like',"%{$post['reunite']}%");
        $lawfirm = $this->obj->table($this->config['prefix'].'lawfirm')->where($where)->select();
        if ($lawfirm)
        {
            echoMsg('10000',$this->message['get_success'],$lawfirm);
        }else{
            echoMsg('10001',$this->message['get_error']);
        }
    }
}