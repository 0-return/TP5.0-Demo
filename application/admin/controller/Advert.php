<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Db;

class Advert extends Init{

	public $tables= array(
        'reg'                   =>'用户注册',
        'other'                 =>'站外跳转',
    );

	public $groups = array(
	    '1' => 'APP端',
        '2' => 'PC端',
    );

    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'].'advert';
    }

    public function index()
    {
        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $map['status'] = array('in','0,1');
        $where['where'] =  $map;
        $this->_list('',$where);
        return view();
    }

    public function _filter(&$map){

        $this->checkSearch($map);
    }

    public function _after_list(&$list){

       	foreach ($list as $key => $value){
            $list[$key]['location']  = $this->obj[1]->table($this->config['prefix'].'advert_cate')->where('id = '.$value['location'])->value('title');
            if ($value['province_cn'] == '')
            {
                $list[$key]['province_cn'] = '全国';
                $list[$key]['city_cn'] = '';
                $list[$key]['area_cn'] = '';
            }
            if ($value['images']){
	            $list[$key]['images'] = unserialize($value['images'])['0'];
	        }else{
	        	$list[$key]['images'] = '';
	        }
            $list[$key]['weburl'] = $this->config['weburl'];
        }

        $get = $this->request->get();
        if (empty($get['province_cn'])) {
            $get['province_cn'] = '';
        }
        if (empty($get['city_cn'])) {
            $get['city_cn'] = '';
        }
        if (empty($get['area_cn'])) {
            $get['area_cn'] = '';
        }
        $this->assign('condition',$get);

    }

       /**
     * @auth PT
     * @date 2019.3.1
     * @purpose 添加前
     * @return void
     */
     public function _before_add(&$list)
    {
        if ($this->request->post()){


            $list['title'] = $this->tables[$list['tag']];
            $list['images'] = isset($list['images'])?serialize($list['images']):'';
            unset($list['reunite']);
        }else{
            $option = cate_tree_html($this->obj[1], $this->config['prefix'].'advert_cate', array('pid' => 'aid', 'status' => 'status', 'title' => 'title', 'id' => 'id'));
            $this->assign('groups',$this->groups);
            $this->assign('table',$this->tables);
            $this->assign('option', $option);
        }

    }

     public function _before_edit(&$list)
    {
        if($this->request->post())
        {

        }else{

            $where['id'] = $list['id'];
            $res = $this->obj[1]->table($this->table)->where($where)->find();
            $res['url'] = $this->config['weburl'];
            $option = cate_tree_html($this->obj[1], $this->config['prefix'].'advert_cate', array('pid' => 'aid', 'status' => 'status', 'title' => 'title', 'id' => 'id'),$parentid = '0', $count = 0,$res['location']);

            //获取广告管理的图片
            $this->assign('table',$this->tables);
            $this->assign('option', $option);
            $this->assign('groups',$this->groups);
            $this->assign('vo',$res);
        }
    }

    public function _after_edit(&$list)
    {
        if ($list['images'])
        {
            $list['images'] = unserialize($list['images']);
        }
    }

    public function _before_update(&$list)
    {
        if (!empty($list['images']))
        {
            $list['images'] = serialize($list['images']);
        }

        $list['title'] = $this->tables[$list['tag']];
        unset($list['reunite']);
    }



	/**
     * note:获取跳转的参数
     * auth:YW
     * date:2018/01/08
     */
    public function getJumpData()
    {
        $obj = $this->obj[1];
        //获取跳转参数
        $post = $this->request->post();
        if (!empty($post))
        {
            //将数组的键名转换成键值相同的数组
            $dbfields = $obj->table($post['table'])->getDbFields();

            if (in_array('goods_name',$dbfields))
            {
                $fields = 'id,goods_name as title';
                $where['goods_name'] = array('like',"%{$post['reunite']}%");
                $tag = $post['table'];
            }

            if (in_array('username',$dbfields))
            {
                $fields = 'uid as id,username as title';
                $where['username'] = array('like',"%{$post['reunite']}%");
                $tag = $post['table'];
            }

            if (in_array('title',$dbfields))
            {
                $fields = 'id,title';
                $where['title'] = array('like',"%{$post['reunite']}%");
                $tag = $post['table'];
            }

            $res = $obj->table($post['table'])->field($fields)->where($where)->select();

            if ($res)
            {
                $msg['code'] = '12100';
                $msg['tag'] = $tag;
                $msg['data'] = $res;
                return $msg;
            }else{
                $msg['code'] = '12101';
                return $msg;
            }

        }
    }





}