<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Db;
use think\Config;
/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：会员分类
 */
class Membercate extends Init
{
    private $status = array(
        '-1' => '<span class="label label-danger radius">已删除</span>',
        '0' => '<span class="label label-default radius">已禁用</span>',
        '1' => '<span class="label label-success radius">使用中</span>',
    );

    /**
     * @auth YW
     * @date 2018.12.7
     * @purpose 初始化
     * @return void
     */
    function _initialize()
    {
        parent::_initialize();
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
        if($this->obj->post())
        {
            $list['addtime'] = time();
        }else{
            $field = array(
                'id' => 'id',
                'pid' => 'iid',
                'key' => 'id',
                'title' => 'title',
                'status' => 'status',
            );
            $option = cateTreeHtml($this->table,$field);
            $this->assign('option',$option);
        }
    }

    public function _before_update(&$list)
    {
        $list['edittime'] = time();
    }
    public function _before_edit()
    {
        if($this->obj->post())
        {
            $list['edittime'] = time();
        }else{
            $field = array(
                'id' => 'id',
                'pid' => 'iid',
                'key' => 'id',
                'title' => 'title',
                'status' => 'status',
            );
            $option = cateTreeHtml($this->table,$field,0,0,$_REQUEST['id']);
            $this->assign('option',$option);
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
                $v['show'] = $v['str'].$v['title'];
                $temp[] = $v;
                $temp = array_merge($temp,self::getTree($data,$v['id'], $level+1,$str));
            }
        }
        return $temp;
    }



}