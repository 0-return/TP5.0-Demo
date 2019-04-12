<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Db;

class Forum extends Init{

	public $tag = array(
        'f' => '推荐',
        'h' => '热点',
    );

    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'].'forum';
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

    protected function _after_list(&$list)
    {
    	foreach ($list as $key => $value) {
    		$list[$key]['tag_cn'] = "";
            $list[$key]['tag'] = explode(',',$value['tag']);
            $list[$key]['content'] = mb_substr($value['content'],0,50).'...';
            foreach ($list[$key]['tag'] as $ky => $vl)
            {
                $list[$key]['tag_cn'] .= $this->tag[$vl].',';
            }
        }
    	

    }

    /**
     * @auth PT
     * @date 2018.03.06
     * @purpose 添加用户
     * @return void
     */
    public function _before_add(&$list){
    	if ($this->request->post()){
        	$list['add_time'] = time();
	        $list['status'] = '0';
	        $list['tag'] = implode(',',$list['tag']);
	        $list['content'] = strip_tags(htmlspecialchars_decode($list['content']));
        }
    }


    public function _after_edit(&$list)
    {
    	$list['tag'] = explode(',', $list['tag']);
        
    }

    public function _before_update(&$list){
    	$list['tag'] = implode(',',$list['tag']);
        if (empty($list['release_time'])) {
            $list['release_time'] = time();
        }else{
            $list['release_time'] = strtotime($list['release_time']);
        }
        $list['content'] = strip_tags(htmlspecialchars_decode($list['content']));	
       
    }


    /**
     * @auth PT
     * @date 2019.3.1 
     * @purpose 预览
     * @return void
     */
    
    public function preview(){
        $get = $this->request->request('id');
        $where['id'] = $get;
        $res = $this->obj->table($this->table)->where($where)->find();
        $this->assign('vo',$res);
        return view();
    }





}