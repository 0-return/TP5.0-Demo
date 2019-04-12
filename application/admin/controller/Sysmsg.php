<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;

/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：系统消息管理
 */
class Sysmsg extends Init
{

    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'] . 'sys_msg';
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

    public function _before_add(&$post)
    {
        if ($this->request->isPost())
        {
            $post['status'] = '1';
            $post['add_time'] = time();
            $post[$post['toPort']] = $post['content'];
            unset($post['toPort'],$post['content']);
        }
    }

    public function _after_list(&$list)
    {
        foreach ($list as $key => $value)
        {
            if (!empty($value['user']) && !empty($value['lawyer']))
            {
                $list[$key]['toport'] = '所有端';
                $list[$key]['content'] = $value['user'];
            }elseif(!empty($value['user']) && empty($value['lawyer']))
            {
                $list[$key]['toport'] = '用户端';
                $list[$key]['content'] = $value['user'];
            }elseif(empty($value['user']) && !empty($value['lawyer']))
            {
                $list[$key]['toport'] = '律师端';
                $list[$key]['content'] = $value['lawyer'];
            }else{
                $list[$key]['toport']='未知';
                $list[$key]['content']='未知';
            }
        }
    }
}