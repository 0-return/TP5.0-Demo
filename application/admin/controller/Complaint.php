<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use app\common\controller\Common;

/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：投诉建议
 */
class Complaint extends Init
{
    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'].'complaint';
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

    public function _after_list(&$list)
    {
        foreach ($list as $key => $value)
        {

            $where['uid'] = $value['uid'];
            $data['type'] = 'value';
            $data['fields'] = 'username';
            $list[$key]['uname'] = getFields($this->obj[1]->table($this->config['prefix'].'member'),$where,$data);
        }
    }

    public function _after_edit(&$list)
    {

        $imgArr = json_decode($list['images']);
        $url = $this->config['weburl'];
        if (is_array($imgArr))
        {
            $temp = '';
            foreach ($imgArr as $key => $value)
            {
                $temp[$key]['images'] = $value?$value:'';
                $temp[$key]['weburl'] = $url;
            }
        }else{
            $new['images'] = $list['images']?$list['images']:'';
            $new['weburl'] = $this->config['weburl'];
            $temp[] = $new;
        }
        $list['images'] = $temp;
        $this->assign('list',$list);
    }
}