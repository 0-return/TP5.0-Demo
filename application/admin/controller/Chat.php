<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Db;

class Chat extends Init{

    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'].'order';
    }

   public function index()
    {
        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $map['where'] = $map;
        $map['alias'] = 'o';
        $map['join'] = [[$this->config['prefix'].'member u','o.uid = u.uid']];
        $map['field'] = 'o.*,u.username';
        $map['where']['o.ustatus'] = array('in','1,2');
        $this->_list('',$map,'','o.add_time',false);
        return view();
    }

    public function _filter(&$map){
        $this->checkSearch($map);
    }


    /**
     * @auth PT
     * @date 2019.3.5
     * @purpose 处理数据
     * @return void
     */
    public function _after_list(&$list)
    {

    	foreach ($list as $key => $value) {
    		$w['chat_no'] = $value['order_no'];
    		$list[$key]['chatnum'] = $this->obj->table($this->config['prefix'].'chatlog')->where($w)->count();
    	}
  
    }

	/**
     * @auth PT
     * @date 2019.3.5
     * @purpose 查看聊天记录
     * @return void
     */
    
    public function showlog(){
    	$uid  = $w['from_id'] = $_REQUEST['uid'];//当前用户uid
		$chat_no = $w['chat_no'] = $_REQUEST['order_no'];
        $toid = $this->obj->table($this->config['prefix'].'chatlog')->where($w)->value('toid');//接收用户uid
    	//获取两个用户所有聊天记录(数据集为倒序，发送前端需要倒置)
        $result=$this->obj->query("select id,add_time,content_type,content,from_id,toid from ".$this->config['prefix'].'chatlog'." WHERE (toid ='{$toid}' and from_id='{$uid}') or (toid ='{$uid}' and from_id='{$toid}') order by add_time desc,id desc");
        if($result){
            //分配记录
            for ($i=0; $i < count($result); $i++) { 
                $map['uid']=$uid;
                if ($result[$i]['from_id']==$uid) {
                    # 当前用户--右
                    $result[$i]['mark']=1;//增加标记
                }else{
                    # 对方用户--左
                    $result[$i]['mark']=2;//增加标记
                }
            } 
        }
		for($i=0;$i<count($result);$i++) {
			$pic=strstr ($result[$i]['content'],'[img]');
			if($pic) {
				$pic=substr($pic,5);
				$result[$i]['content']=$pic;
				$result[$i]['contenttype']='pic';
			}
		}
        $this->assign('list',array_reverse($result));
        return view();
    }


}