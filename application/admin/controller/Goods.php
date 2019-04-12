<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use app\common\controller\Common;

/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：数据库管理
 */
class Goods extends Init
{
    private $status = array(
        '-1'        =>  '<span class="label label-danger radius">已删除</span>',
        '0'         =>  '<span class="label label-default radius">已下架</span>',
        '1'         =>  '<span class="label label-success radius">已上架</span>',
    );
    private $flag = array(
        array('id' => '1','title' => '普通'),
        array('id' => '2','title' => '推荐'),
        array('id' => '3','title' => '热销'),
    );

    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'].'goods';
    }

    public function _filter(&$map)
    {

        $table = $this->config['prefix'].'goods_type';
        $field = array(
            'id' => 'id',
            'key' => 'id',
            'pid' => 'iid',
            'title' => 'name',
            'status' => 'status'
        );
        $option = cateTreeHtml($this->obj,$table,$field);
        $this->assign('option',$option);
        $this->checkSearch($map);

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
        $map['where']['status'] = array('in','0,1');
        $this->_list('',$map);
        return view();
    }


    public function _after_list(&$list)
    {
        foreach ($list as $key => $value)
        {
            $str = '';
            $arr = explode(',',$value['flag']);
            $list[$key]['status_cn'] = $this->status[$value['status']];
            foreach ($this->flag as $ky => $vl)
            {
                foreach ($arr as $k => $v)
                {
                    if ($vl['id'] == $v)
                    {
                        $str .= $vl['title'].'-';
                    }
                }
            }
            $list[$key]['flag'] = trim($str,'-');
        }
        $this->assign('flag',$this->flag);
    }

    public function _before_add(&$list)
    {
        if($this->request->post())
        {
            if(isset($list['images']))
            {
                $list['images'] = serialize($list['images']);
            }
            $list['status'] = '0';
            $list['flag'] = !empty($list['flag'])?implode($list['flag'],','):'9';
            $list['add_time'] = time();
        }else{
            $table = $this->config['prefix'].'goods_type';
            $field = array(
                'id' => 'id',
                'key' => 'id',
                'pid' => 'iid',
                'title' => 'name',
                'status' => 'status'
            );
            $option = cateTreeHtml($this->obj,$table,$field);
            $this->assign('option',$option);
            $this->assign('flag',$this->flag);
        }
    }

    public function _before_edit()
    {

        if($this->request->post())
        {

        }else{
            $table = $this->config['prefix'].'goods_type';
            $field = array(
                'id' => 'id',
                'key' => 'id',
                'pid' => 'iid',
                'title' => 'name',
                'status' => 'status'
            );
            $option = cateTreeHtml($this->obj,$table,$field,0,0,$_REQUEST['id']);
            $this->assign('option',$option);
            $this->assign('flag',$this->flag);
        }
    }

    public function _after_edit(&$list)
    {
        if ($list['images'])
        {
            $list['url'] = $this->config['weburl'];
            $list['images'] = unserialize($list['images']);
        }
        $list['flag'] = !empty($list['flag'])?explode(',',$list['flag']):'0';

    }

    public function _before_update(&$list)
    {


        if (!empty($list['images']))
        {
            $list['images'] = serialize($list['images']);
        }

        $list['flag'] = implode(',',$list['flag']);
        $list['edit_time'] = time();

    }

}