<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Db;

class Question extends Init{

	 private $u_type = array(
        '0'=>'律师',
        '1'=>'用户',
    );
    private $status = array(
        '-1' => '删除',
        '0' => '未启用',
        '1' => '启用',
        '2' => '采纳',
    );

    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'].'question';
        $this->answer = $this->config['prefix'].'answer';
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
        $map['field'] = 'o.*,u.username,u.nickname';
        $map['where']['o.status'] = array('gt','-1');
        $this->_list('',$map,'','o.add_time',false);
        return view();
    }

    public function _filter(&$map){
        $get = $this->request->get();
        if (!empty($get['begintime']) && !empty($get['endtime']))
        {
            $map['o.add_time'] = array('between',array(strtotime($get['begintime']),strtotime($get['endtime'])));
        }
        $this->checkSearch($map);
    }


    public function show(){
    	$id = $this->request->get('id');
    	// 获取第一条所有回答
        $where['pid'] = $id;
        $where['is_first_answer']='0';//是否是第一条回答(0第一  ,1不是第一)
        $where['user_type'] = '0';//用户类型（0律师,1用户）
        $res = $this->obj->table($this->answer)->where($where)->order('add_time asc')->select();
        if ($res) {
        	foreach ($res as $key => $value) {
        		 //获取提问者,回答者信息（0律师,1用户）
                if ($value['user_type']=='0') {
                    $whe['uid']=$value['uid'];
                    $l = $this->obj->table($this->config['prefix'].'lawyer')->where($whe)->find();
  					$res[$key]['amember'] = $l['username'];
                }else{
                    $whe['status']='1';
                    $whe['uid']=$value['uid'];
                    $member=$this->obj->table($this->config['prefix'].'member')->where($whe)->find();
                    //获取提问者
                    $res[$key]['amember'] = $member['nickname'].($member['username']);
                }

        		// 获取追问追答数据
	            $www['pid'] = $id;//问题id
	            $www['aid'] = $value['id'];//第一条回答id
	            $www['is_first_answer'] = '1';//是否是第一条回答(0第一  ,1不是第一)
	            $a = $this->obj->table($this->answer)->where($www)->order('add_time asc')->select();
	            if ($a) {
	            	foreach ($a as $k => $v) {
	            		$a[$k]['add_time'] = $this->friendlyDate($v['add_time']);
	            		//获取提问者,回答者信息（0律师,1用户）
		                if ($v['user_type']=='0') {
		                    $wher['uid']=$v['uid'];
		                    $l = $this->obj->table($this->config['prefix'].'lawyer')->where($wher)->find();
		  					$a[$k]['amember'] = $l['username'];
		                }else{
		                    $wher['uid']=$v['uid'];
		                    $member=$this->obj->table($this->config['prefix'].'member')->where($wher)->find();
		                    //获取提问者
		                    $a[$k]['amember'] = $member['nickname'].'('.$member['username'].')';
		                }
	            	}
	            }
	            $res[$key]['zhui'] = $a;
	            // 追问追答数据结束
	            $res[$key]['add_time'] = $this->friendlyDate($value['add_time']);
        	}
        }
        // 获取问题
        $w['id'] = $id;
        $question = $this->obj->table($this->table)->where($w)->find();
        $member = $this->obj->table($this->config['prefix'].'member')->where("uid={$question['uid']}")->find();
        $question['add_time'] = $this->friendlyDate($question['add_time']);
        // echo "<pre>";
        // var_dump($res);exit;
        $this->assign('list',$res);
        $this->assign('question',$question);
        $this->assign('member',$member);
        return view();

    }

    /**
 * 友好的时间显示
 *
 * @param int    $sTime 待显示的时间
 * @param string $type  类型. normal | mohu | full | ymd | other
 * @param string $alt   已失效
 * @return string
 */
	function friendlyDate($sTime,$type = 'normal',$alt = 'false') {
	    if (!$sTime)
	        return '';
	    //sTime=源时间，cTime=当前时间，dTime=时间差
	    $cTime      =   time();
	    $dTime      =   $cTime - $sTime;
	    $dDay       =   intval(date("z",$cTime)) - intval(date("z",$sTime));
	    //$dDay     =   intval($dTime/3600/24);
	    $dYear      =   intval(date("Y",$cTime)) - intval(date("Y",$sTime));
	    //normal：n秒前，n分钟前，n小时前，日期
	    if($type=='normal'){
	        if( $dTime < 60 ){
	            if($dTime < 10){
	                return '刚刚';    //by yangjs
	            }else{
	                return intval(floor($dTime / 10) * 10)."秒前";
	            }
	        }elseif( $dTime < 3600 ){
	            return intval($dTime/60)."分钟前";
	        //今天的数据.年份相同.日期相同.
	        }elseif( $dYear==0 && $dDay == 0  ){
	            //return intval($dTime/3600)."小时前";
	            return '今天'.date('H:i',$sTime);
	        }elseif($dYear==0){
	            return date("m月d日 H:i",$sTime);
	        }else{
	            return date("Y-m-d H:i",$sTime);
	        }
	    }elseif($type=='mohu'){
	        if( $dTime < 60 ){
	            return $dTime."秒前";
	        }elseif( $dTime < 3600 ){
	            return intval($dTime/60)."分钟前";
	        }elseif( $dTime >= 3600 && $dDay == 0  ){
	            return intval($dTime/3600)."小时前";
	        }elseif( $dDay > 0 && $dDay<=7 ){
	            return intval($dDay)."天前";
	        }elseif( $dDay > 7 &&  $dDay <= 30 ){
	            return intval($dDay/7) . '周前';
	        }elseif( $dDay > 30 ){
	            return intval($dDay/30) . '个月前';
	        }
	    //full: Y-m-d , H:i:s
	    }elseif($type=='full'){
	        return date("Y-m-d , H:i:s",$sTime);
	    }elseif($type=='ymd'){
	        return date("Y-m-d",$sTime);
	    }else{
	        if( $dTime < 60 ){
	            return $dTime."秒前";
	        }elseif( $dTime < 3600 ){
	            return intval($dTime/60)."分钟前";
	        }elseif( $dTime >= 3600 && $dDay == 0  ){
	            return intval($dTime/3600)."小时前";
	        }elseif($dYear==0){
	            return date("Y-m-d H:i:s",$sTime);
	        }else{
	            return date("Y-m-d H:i:s",$sTime);
	        }
	    }
	}



}