<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：短信模板管理
 */
class Smsmodule extends Init
{

    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'] . 'sms_jh_module';
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


}