<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Db;

class Adv extends Init{

	public $tables= array(
        'reg'                   =>'用户注册',
        'other'                 =>'站外跳转',
        'fwy_lawyer'            =>'律师专题',
        /*'fwy_goodsmanagement'   =>'商品详情',*/
        'fwy_content'     =>'内容详情',
    );

    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'].'adv';
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
        if (!empty($this->request->get('province_cn'))) {
            $map['pro'] = $this->request->get('province_cn');
        }
        if (!empty($this->request->get('city_cn'))) {
            $map['city'] = $this->request->get('city_cn');
        }
        if (!empty($this->request->get('area_cn'))) {
            $map['area'] = $this->request->get('area_cn');
        }
        $this->checkSearch($map);
    }

    public function _after_list(&$list){
       	foreach ($list as $key => $value){
            $list[$key]['location']  = $this->obj->table($this->config['prefix'].'advtype')->where('id = '.$value['location'])->value('title');
            if ($value['pro'] == '')
            {
                $list[$key]['pro'] = '全国';
                $list[$key]['city'] = '';
                $list[$key]['area'] = '';
            }
            if ($value['picture_path']){
	            $list[$key]['picture_path'] = unserialize($value['picture_path'])['0'];
	        }else{
	        	$list[$key]['picture_path'] = '';
	        }
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
        $this->assign('url', $this->config['weburl']);
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
            unset($list['reunite']);
            unset($list['file']);
            $list['title'] = $this->tables[$list['table']];
            unset($list['table']);
            if(isset($list['images']))
            {
                $list['picture_path'] = serialize($list['images']);
            }
            $list['pro'] = $list['province_cn'];
            $list['city'] = $list['city_cn'];
            $list['area'] = $list['area_cn'];
            unset($list['images']);
            unset($list['province_cn']);
            unset($list['city_cn']);
            unset($list['area_cn']);
        }else{
            $option = cate_tree_html($this->obj, $this->config['prefix'].'advtype', array('pid' => 'aid', 'status' => 'status', 'title' => 'title', 'id' => 'id'));
            $this->assign('table',$this->tables);
            $this->assign('option', $option);
        }

    }

     public function _before_edit(&$list)
    {
        if($this->request->post())
        {

        }else{
            $where['id'] = $_REQUEST['id'];
            $res = $this->obj->table($this->table)->where($where)->find();
            $option = cate_tree_html($this->obj, $this->config['prefix'].'advtype', array('pid' => 'aid', 'status' => 'status', 'title' => 'title', 'id' => 'id'),$parentid = '0', $count = 0,$res['location']);
            $res['url'] = $this->config['weburl'];
            //获取广告管理的图片
            $this->assign('table',$this->tables);
            $this->assign('url', $this->config['weburl']);
            $this->assign('option', $option);

        }
    }

    public function _after_edit(&$list)
    {
        if ($list['picture_path'])
        {
            $list['images'] = unserialize($list['picture_path']);
        }
    }

    public function _before_update(&$list)
    {

        if (!empty($list['images']))
        {
            $list['picture_path'] = serialize($list['images']);
        }
        unset($list['images']);
        $list['title'] = $this->tables[$list['table']];
       	unset($list['table']);
        unset($list['reunite']);
        $list['pro'] = $list['province_cn'];
        $list['city'] = $list['city_cn'];
        $list['area'] = $list['area_cn'];
        unset($list['province_cn']);
        unset($list['city_cn']);
        unset($list['area_cn']);
    }



	/*
     *note:获取跳转的参数
     *auth:YW
     *date:2018/01/08
     */
    public function getJumpData()
    {
        $obj = $this->obj;
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