<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;

/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：数据库管理
 */
class Goodscate extends Init
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
        $this->table = $this->config['prefix'].'goods_type';
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
        $where['status'] = array('in','0,1');
        $map['where'] = $where;
        $this->_list('',$map,false,'',false,1000);
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

    public function _after_edit(&$list)
    {
        if ($list['images'])
        {
            $list['url'] = $this->config['weburl'];
            $list['images'] = unserialize($list['images']);
        }

    }

    public function _before_add(&$list)
    {
        if($this->request->post())
        {
            if(isset($list['images']))
            {
                $list['icon'] = $list['images'][0];
                unset($list['images']);
            }
            $list['add_time'] = time();
        }else{
            $field = array(
                'id' => 'id',
                'pid' => 'iid',
                'key' => 'id',
                'title' => 'name',
                'status' => 'status',
            );
            $option = cateTreeHtml($this->obj,$this->table,$field);
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
                'pid' => 'iid',
                'key' => 'id',
                'title' => 'name',
                'status' => 'status',
            );
            $option = cateTreeHtml($this->obj,$this->table,$field,0,0,$_REQUEST['id']);
            $this->assign('option',$option);
        }
    }

    public function _before_update(&$list)
    {

        if(isset($list['images']))
        {
            $list['icon'] = $list['images'][0];
            unset($list['images']);
        }
        $list['update_time'] = time();

    }

    public function _before_delete(&$post)
    {
        $where['goods_type'] = $post['id'];
        $res = $this->obj->table($this->config['prefix'].'goods')->where($where)->find();
        unset($where);
        if ($res)
        {
            echoMsg('10001',$this->message['del_fail']);
        }else{
            $where['id'] = $post['id'];
            $res = getFields($this->obj->table($this->config['prefix'].'goods_type'),$where,array('type' => 'find','fields' => 'id'));
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
    static public function getTree(&$data,$pid=0,$level=0,$str='|— '){
        $temp = array();
        foreach ($data as $v){
            if($v['iid'] == $pid){
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