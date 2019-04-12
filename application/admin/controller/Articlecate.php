<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;

/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：律师文章分类
 */
class Articlecate extends Init
{
    private $status = array(
        '-1'        =>  '<span class="label label-danger radius">已删除</span>',
        '0'         =>  '<span class="label label-default radius">已禁用</span>',
        '1'         =>  '<span class="label label-success radius">使用中</span>',
    );
    /**
     * @auth YW
     * @date 2017.12.2
     * @purpose 初始化
     * @return void
     */
    function _initialize()
    {

        parent::_init();
        $this->table = $this->config['prefix'].'lawyer_content_type';
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

        $where['where'] = $map;
        $this->_list('',$where,'','sort');
        return view();
    }

    public function _after_list(&$list)
    {

        foreach ($list as $key => $value)
        {

            $list[$key]['status_cn'] = $this->status[$value['status']];

        }
        $list = self::getTree($list);
    }

    public function _before_add(&$list)
    {
        if($this->request->post())
        {
            $list['tid'] = '-1';

        }else{
            $field = array(
                'id' => 'id',
                'pid' => 'tid',
                'key' => 'id',
                'title' => 'name',
                'status' => 'status',
            );
            $option = cateTreeHtml($this->obj,$this->table,$field,'-1');
            $this->assign('option',$option);
        }
    }

    public function _before_edit()
    {
        if($this->request->post())
        {
            $list['update_time'] = time();
        }else{
            $field = array(
                'id' => 'id',
                'pid' => 'tid',
                'key' => 'id',
                'title' => 'name',
                'status' => 'status',
            );
            $option = cateTreeHtml($this->obj,$this->table,$field,'-1',0,$_REQUEST['id']);
            $this->assign('option',$option);
        }
    }


    public function _before_delete(&$post)
    {
        $where['type'] = is_string($post['id'])?$post['id']:array('in',implode(',',$post['id']));
        //$where['type'] = array('in',implode(',',$post['id']));
        $res = $this->obj->table($this->config['prefix'].'lawyer_content')->where($where)->find();
        unset($where);
        if ($res)
        {
            echoMsg('10001',$this->message['del_fail']);
        }else{
            $where['tid'] = array('in',implode(',',$post['id']));
            $res = getFields($this->obj->table($this->config['prefix'].'lawyer_content_type'),$where,array('type' => 'find','fields' => 'id'));
            if ($res)
            {
                echoMsg('10001',$this->message['del_fail']);
            }
        }
    }


    /**
     * note:目录数组装(无限级)
     * auth:YW
     * date:2018/01/08
     */
    static public function getTree(&$data,$pid = -1,$level=0,$str='|— '){

        $temp = array();
        foreach ($data as $v){

            if($v['tid'] == $pid){
                $v['level'] = $level + 1;
                $v['str'] = str_repeat($str,$level);
                $v['show'] = $v['str'].$v['name'];
                $temp[] = $v;

                $temp = array_merge($temp,self::getTree($data,$v['id'], $level+1,$str));
            }
        }

        return $temp;
    }

}