<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Db;

class Agreement extends Init{

    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'].'agreement';
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