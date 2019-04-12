<?php
namespace app\uapi\controller\v3;
use think\Db;


class Question extends Index {

    /**
     * 初始化
     *
     * @return \think\Response
     */
    public function _initialize()
    {
        parent::_init();
    }

     /*
     *note:发布留言咨询
     *auth:PT
     *uid,pay_coin,token,industry_id,describe
     *date:2019/02/22
     */
    public function add(){
        $post = $this->request->post();
        //参数验证
        $validate = new \app\uapi\validate\Question;
        if(!$validate->check($post)){
            self::returnMsgAndToken('10004',$validate->getError());
        }
        unset($post['token']);
        $post['status'] = '1';
        $post['read_num'] = '0';
        $post['add_time'] = time();

		$lawyerdata['uid'] = $post['uid'];
		$coin=DB::table('os_lawyer')->where($lawyerdata)->value('coin');
		// var_dump($coin);exit;
		if ($coin < $post['pay_coin']) {
			self::returnMsgAndToken('10010','法币余额不足');
		}
        $res = $this->obj->table('fwy_question')->insert($post);
        if ($res){
			$r = DB::table('os_lawyer')->where($lawyerdata)->setDec('coin',$post['pay_coin']);
			if ($r) {
				self::returnMsgAndToken('10000','发布成功！');
			}else{
				self::returnMsgAndToken('10014','发布失败！');
			}
        }else{
            self::returnMsgAndToken('10014','发布失败！');
        }
    }

    /*
     *note:删除没有回答过的问答记录
     *auth:PT
     *id,uid,token
     *date:2019/02/22
     */
    public function delByid(){
        $post = $this->request->post();
        if (!empty($post['id'])){
            $where['id'] = $post['id'];
            // $where['status'] = '-1';（-1没有评论）
            $res = $this->obj->table('fwy_question')->where($where)->setField('status','-2');
            if ($res)
            {
            	self::returnMsgAndToken('10000','问题删除成功！');
            }else{
            	self::returnMsgAndToken('10014','问题删除失败！');
            }
        }else{
        	self::returnMsgAndToken('10004');
        }
    }



    /*
     *note:采纳回答
     *auth:PT
     *date:2019/02/22
     */
    public function editByid(){
        $post = $this->request->post();
        if ($post)
        {
            $where['id'] = $post['id'];
            $status = $this->obj->table('fwy_answer')->where($where)->value('status');
            if ($status == '2') {
            	self::returnMsg('10010','您已采纳该回答！');
            }
            $w['id']=$this->obj->table('fwy_answer')->where($where)->value('pid');
            // 开启事务
            $m = $this->obj;
            $m->startTrans();//开启事务
            DB::startTrans();//开启事务
            try{
            	$res = $this->obj->table('fwy_answer')->where($where)->setField('status','2');
	            $resault = $this->obj->table('fwy_question')->where($w)->setField('status','2');

	            //该问题的法币
	            $question=$this->obj->table('fwy_question')->where($w)->find();
	            $w_1['pid']=$question['id'];
	            $w_1['user_type']=0;
	            $w_1['status']=2;
	            //律师ID
	            $w_2['uid']=$this->obj->table('fwy_answer')->where($w_1)->value("uid");
	            $res_11=DB::table("os_lawyer")->where($w_2)->setInc("coin",sprintf("%.2f",$question['pay_coin']));
	             //提交事务
                Db::commit();
                $m->commit();
	            self::returnMsg('10000','采纳成功');
            }catch (\PDOException $e) {
                //回滚事务
                Db::rollback();
                $m->rollback();
                self::returnMsgAndToken('10014','采纳失败');
            }
        }else{
            self::returnMsg('10004');
        }
    }


     /*
     *note:根据问题id获取第一条回答记录
     *auth:PT
     *date:2019/02/25
     */
    public function showByid(){
    	$post = $this->request->post();
        $where['pid'] = $w['id'] = $post['id'];
        $where['is_first_answer'] = '0';//是否是第一条回答(0第一  ,1不是第一)
        $where['user_type'] = '0';//用户类型（0律师,1用户）
        $list = $this->obj->table('fwy_answer')->where($where)->order('add_time desc')->select();
        if ($list){
            // 获取律师信息
            foreach ($list as $ky => $vl){
                //$wh['status']='2';        //过期或者失效律师是否要显示？？？？？？？？？？？？
                $wh['uid']=$vl['uid'];
                $lawyer=$this->obj->table('fwy_lawyer')->where($wh)->find();
                if ($lawyer) {
                    $list[$ky]['username'] = $lawyer['username'];
                    $list[$ky]['province'] = $this->$this->get_area_nostyle($lawyer['province'])['region_name'];
                    $list[$ky]['city'] = $this->$this->get_area_nostyle($lawyer['city'])['region_name'];
                    $list[$ky]['face'] = $lawyer['face'];
                    $list[$ky]['weburl'] = $this->config['weburl'];
                    // $list[$ky]['practicelaw'] = $this->obj->table('fwy_lawyercertification')->where('lawyer_id='.$lawyer['uid'])->value('practicelaw');
                }else{
                	self::returnMsg('10001');
                }
                 // 时间友好显示
                $list[$ky]['add_time']=$this->friendlyDate($vl['add_time']);
            }
            $this->obj->table('fwy_question')->where($w)->setinc('read_num','1');//浏览量加一
            self::returnMsg('10000','',$list);
        }else{
            self::returnMsg('10001','没有找到相关数据！');
        }
    }

    /*
     *note:根据问题id获取所有问题及回答记录(id,uid)
     *auth:PT
     *date:2019/02/25
     */
    public function showallByid(){
        $post = $this->request->post();
        $w['id'] = $post['id'];
        // $w['uid']=I('post.uid');
        // 获取问题详情
        $arr=$this->obj->table('fwy_question')->where($w)->order('add_time desc')->find();
        if ($arr) {
            $wh['status']='1';
            $wh['uid']=$arr['uid'];
            $member=$this->obj->table('fwy_member')->where($wh)->find();
            //获取提问者
            $arr['nickname'] = $member['nickname'];
            $arr['face'] = $member['face'];
            $arr['weburl'] = $this->config['weburl'];
			#用户未设置地区
			if($member['province'] && $member['city']) {
				$arr['pro'] = $this->get_area_nostyle($member['province'])['region_name'];
				$arr['city'] = $this->get_area_nostyle($member['city'])['region_name'];
			}else {
				$arr['pro'] = '北京市';
				$arr['city'] = '市辖区';
			}

            // 时间友好显示
            $arr['add_time']=$this->friendlyDate($arr['add_time']);

            // 获取第一条所有回答
            $where['pid'] = $post['id'];
            $where['is_first_answer']='0';//是否是第一条回答(0第一  ,1不是第一)
            $where['user_type'] = '0';//用户类型（0律师,1用户）
            $res = $this->obj->table('fwy_answer')->where($where)->order('add_time desc')->select();
            if ($res){
                foreach ($res as $key => $value){
                    // 时间友好显示
                    $res[$key]['add_time']=$this->friendlyDate($value['add_time']);
                    //获取提问者,回答者信息（0律师,1用户）
                    if ($value['user_type']=='0') {
                        // $whe['status']='2';
                        $whe['uid']=$value['uid'];
                        $lawyer = $this->obj->table('fwy_lawyer')->where($whe)->find();
                        // $lawyer['uid'] = $l['uid'];
                        // $lawyer['username'] = $l['username'];
                        // $lawyer['city'] = $l['city'];
                        // $lawyer['province'] = $l['province'];
                        // $lawyer['face'] = $l['face'];
                        if ($lawyer) {
                            $res[$key]['username'] = $lawyer['username'];
                            $res[$key]['company'] = $lawyer['company'];
							#用户未设置地区
							if($lawyer['province'] && $lawyer['city']) {
								$res[$key]['province'] = $this->get_area_nostyle($lawyer['province'])['region_name'];
								$res[$key]['city'] = $this->get_area_nostyle($lawyer['city'])['region_name'];
							}else {
								$res[$key]['province'] = '北京市';
								$res[$key]['city'] = '市辖区';
							}

                            $res[$key]['face'] = $lawyer['face'];
                            $res[$key]['weburl'] = $this->config['weburl'];
                            $res[$key]['lid'] = $value['uid'];
                            // $res[$key]['practicelaw'] = $this->obj->table('fwy_lawyercertification')->where('lawId='.$value['uid'])->value('practicelaw');
                        }else{
                        	self::returnMsg('10014','获取失败！');
                        }
                    }else{
                        $whe['status']='1';
                        $whe['uid']=$value['uid'];
                        $member=$this->obj->table('fwy_member')->where($whe)->find();
                        //获取提问者
                        $res[$key]['nickname'] = $member['nickname'];
                        $res[$key]['face'] = $member['face'];
                        $res[$key]['weburl'] = $this->config['weburl'];
						#用户未设置地区
						if($member['province'] && $member['city']) {
							$res[$key]['pro'] = $this->get_area_nostyle($member['province'])['region_name'];
							$res[$key]['city'] = $this->get_area_nostyle($member['city'])['region_name'];
						}else {
							$res[$key]['pro'] = '北京市';
							$res[$key]['city'] = '市辖区';
						}

                    }
                    // 获取追问追答数据
                    $www['pid'] = $post['id'];//问题id
                    $www['aid'] = $value['id'];//第一条回答id
                    $www['is_first_answer'] = '1';//是否是第一条回答(0第一  ,1不是第一)
                    $a = $this->obj->table('fwy_answer')->where($www)->order('add_time asc')->select();
                    foreach ($a as $k => $v) {
                        // 时间友好显示
                        $a[$k]['add_time']=$this->friendlyDate($v['add_time']);
                        //获取提问者,回答者信息（0律师,1用户）
                        if ($v['user_type']=='0') {
                            $whee['uid']=$v['uid'];
                            $l=$this->obj->table('fwy_lawyer')->where($whee)->find();
                            if ($l) {
                                $a[$k]['username'] = $l['username'];
                                $a[$k]['company'] = $lawyer['company'];
								#用户未设置地区
								if($l['province'] && $l['city']) {
									$a[$k]['province'] = $this->get_area_nostyle($l['province'])['region_name'];
									$a[$k]['city'] = $this->get_area_nostyle($l['city'])['region_name'];
								}else {
									$a[$k]['province'] = '北京市';
									$a[$k]['city'] = '市辖区';
								}

                                $a[$k]['face'] = $l['face'];
                                $a[$k]['weburl'] = $this->config['weburl'];
                                $a[$k]['lid'] = $v['uid'];
                                // $a[$k]['practicelaw'] = $this->obj->table('fwy_lawyercertification')->where('lawId='.$v['uid'])->value('practicelaw');
                            }else{
                                self::returnMsg('10014','获取失败！');
                            }

                        }else{
                            $whe3['status']='1';
                            $whe3['uid']=$v['uid'];
                            $member=$this->obj->table('fwy_member')->where($whe3)->find();
                            //获取提问者
                            $a[$k]['nickname'] = $member['nickname'];
                            $a[$k]['face'] = $member['face'];
                            $a[$k]['weburl'] = $this->config['weburl'];
							#用户未设置地区
							if($member['province'] && $member['city']) {
								$a[$k]['pro'] = $this->get_area_nostyle($member['province'])['region_name'];
								$a[$k]['city'] = $this->get_area_nostyle($member['city'])['region_name'];
							}else {
								$a[$k]['pro'] = '北京市';
								$a[$k]['city'] = '市辖区';
							}

                        }
                    }
                    $res[$key]['zhui'] = $a;
                }
                // 获取回复数
                $who['pid']=$post['id'];
                $who['is_first_answer']='0';//是否是第一条回答(0第一  ,1不是第一)
                $who['user_type'] = '0';//用户类型（0律师,1用户）
                $arr['answer_num']= $this->obj->table('fwy_answer')->where($who)->count();
                $array['question']=$arr;
                $array['answer']=$res;
                $this->obj->table('fwy_question')->where($w)->setinc('read_num','1');//浏览量加一
                self::returnMsg('10000','',$array);
            }else{
				$arr['answer_num']= 0;
				$array['question']=$arr;
				$array['answer']=array();
				self::returnMsg('10000','',$array);
			}
        }else{
            self::returnMsg('10001','没有找到相关数据！');
        }
    }

    /*
     *note:根据uid获取回答记录
     *auth:PT
     *date:2019/02/25
     */
    public function showanswer(){
    	$post = $this->request->post();
        $where['uid'] = $post['uid'];
        $list = $this->obj->table('fwy_question')->where($where)->order('add_time desc')->select();
        if (empty($list)) {
            self::returnMsg('10001','没有回答记录');
        }
        $id=array_column($list,'id');
        $who['pid']=array('in',$id);
        $who['user_type']='0';//用户类型（0律师,1用户）
        $arr=$this->obj->table('fwy_answer')->where($who)->order('add_time desc')->select();
        // var_dump($arr);exit;
        if ($arr){
            foreach ($arr as $k => $v) {
                $ww['uid']=$v['uid'];
                $arr[$k]['username']=$this->obj->table('fwy_lawyer')->where($ww)->value('username');
                $arr[$k]['add_time']=$this->friendlyDate($v['add_time']);
            }
            self::returnMsgAndToken('10000','',$arr);
        }else{
            self::returnMsgAndToken('10001','没有找到相关数据！');
        }
    }

    /*
     *note:根据问题id、律师id获取问题及当前律师所有回答记录(id,lid)
     *auth:PT
     *date:2019/02/25
     */
    public function showallBylid(){
    	$post = $this->request->post();
        $w['id'] = $post['id'];
        // 获取问题详情
        $arr=$this->obj->table('fwy_question')->where($w)->find();

        if ($arr) {
            $wh['status']='1';
            $wh['uid']=$arr['uid'];
            $member=$this->obj->table('fwy_member')->where($wh)->find();
            //获取提问者
            $arr['nickname'] = $member['nickname'];
            $arr['face'] = $member['face'];
            $arr['weburl'] = $this->config['weburl'];
			#用户未设置地区
			if($member['province'] && $member['city']) {
				$arr['pro'] = $this->get_area_nostyle($member['province'])['region_name'];
				$arr['city'] = $this->get_area_nostyle($member['city'])['region_name'];
			}else {
				$arr['pro'] = '北京市';
				$arr['city'] = '市辖区';
			}

            // 时间友好显示
            $arr['add_time']=$this->friendlyDate($arr['add_time']);

            // 获取第一条所有回答
            $where['pid'] = $post['id'];
            $where['uid'] = $post['lid'];
            $where['is_first_answer']='0';//是否是第一条回答(0第一  ,1不是第一)
            $where['user_type'] = '0';//用户类型（0律师,1用户）
            $res = $this->obj->table('fwy_answer')->where($where)->order('add_time desc')->select();

            if ($res){
                foreach ($res as $key => $value){
                    // 时间友好显示
                    $res[$key]['add_time']=$this->friendlyDate($value['add_time']);
                    //获取提问者,回答者信息（0律师,1用户）
                    if ($value['user_type']=='0') {
                        $whe1['uid']=$value['uid'];
                        $lawyer['uid']=$this->obj->table('fwy_lawyer')->where($whe1)->value('uid');
                        $lawyer['username']=$this->obj->table('fwy_lawyer')->where($whe1)->value('username');
                        $lawyer['city']=$this->obj->table('fwy_lawyer')->where($whe1)->value('city');
                        $lawyer['province']=$this->obj->table('fwy_lawyer')->where($whe1)->value('province');
                        $lawyer['face']=$this->obj->table('fwy_lawyer')->where($whe1)->value('face');
                        if($lawyer) {
                            $res[$key]['username'] = $lawyer['username'];

							#用户未设置地区
							if($lawyer['province'] && $lawyer['city']) {
								$res[$key]['province'] = $this->get_area_nostyle($lawyer['province'])['region_name'];
								$res[$key]['city'] = $this->get_area_nostyle($lawyer['city'])['region_name'];
							}else {
								$res[$key]['province'] = '北京市';
								$res[$key]['city'] = '市辖区';
							}

                            $res[$key]['face'] = $lawyer['face'];
                            $res[$key]['weburl'] = $this->config['weburl'];
                            // $res[$key]['practicelaw'] = $this->obj->table('fwy_lawyercertification')->where('lawId='.$value['uid'])->value('practicelaw');
                        }else{
                        	self::returnMsg('10014','获取失败！');
                        }
                    }else{
                        $whe['status']='1';
                        $whe['uid']=$value['uid'];
                        $member=$this->obj->table('fwy_member')->where($whe)->find();
                        //获取提问者
                        $res[$key]['nickname'] = $member['nickname'];
                        $res[$key]['face'] = $member['face'];
                        $res[$key]['weburl'] = $this->config['weburl'];
						#用户未设置地区
						if($member['province'] && $member['city']) {
							$res[$key]['pro'] = $this->get_area_nostyle($member['province'])['region_name'];
							$res[$key]['city'] = $this->get_area_nostyle($member['city'])['region_name'];
						}else {
							$res[$key]['pro'] = '北京市';
							$res[$key]['city'] = '市辖区';
						}

                    }
                    // 获取追问追答数据
                    $www['pid'] = $post['id'];//问题id
                    $www['aid'] = $value['id'];//第一条回答id
                    $www['is_first_answer'] = '1';//是否是第一条回答(0第一  ,1不是第一)
                    $a = $this->obj->table('fwy_answer')->where($www)->order('add_time asc')->select();
                    foreach ($a as $k => $v) {
                        // 时间友好显示
                        $a[$k]['add_time']=$this->friendlyDate($v['add_time']);
                        //获取提问者,回答者信息（0律师,1用户）
                        if ($v['user_type']=='0') {
                            // $whe['status']='2';
                            $whee['uid']=$v['uid'];
                            $l=$this->obj->table('fwy_lawyer')->where($whee)->find();
                            if ($l) {
                                $a[$k]['username'] = $l['username'];
								#用户未设置地区
								if($l['province'] && $l['city']) {
									$a[$k]['province'] = $this->get_area_nostyle($l['province'])['region_name'];
									$a[$k]['city'] = $this->get_area_nostyle($l['city'])['region_name'];
								}else {
									$a[$k]['province'] = '北京市';
									$a[$k]['city'] = '市辖区';
								}

                                $a[$k]['face'] = $l['face'];
                                $a[$k]['weburl'] = $this->config['weburl'];
                                // $a[$k]['practicelaw'] = $this->obj->table('fwy_lawyercertification')->where('lawId='.$v['uid'])->value('practicelaw');
                            }else{
                                self::returnMsg('10014','获取失败！');
                            }
                        }else{
                            $whe3['status']='1';
                            $whe3['uid']=$v['uid'];
                            $member=$this->obj->table('fwy_member')->where($whe3)->find();
                            //获取提问者
                            $a[$k]['nickname'] = $member['nickname'];
                            $a[$k]['face'] = $member['face'];
                            $a[$k]['weburl'] = $this->config['weburl'];
							#用户未设置地区
							if($member['province'] && $member['city']) {
								$a[$k]['pro'] = $this->get_area_nostyle($member['province'])['region_name'];
								$a[$k]['city'] = $this->get_area_nostyle($member['city'])['region_name'];
							}else {
								$a[$k]['pro'] = '北京市';
								$a[$k]['city'] = '市辖区';
							}

                        }
                    }
                    $res[$key]['zhui'] = $a;
                }
                // 获取回复数
                $who['pid']=$post['id'];
                $who['is_first_answer']='0';//是否是第一条回答(0第一  ,1不是第一)
                $who['user_type'] = '0';//用户类型（0律师,1用户）
                $arr['answer_num']= $this->obj->table('fwy_answer')->where($who)->count();
                $array['question']=$arr;
                $array['answer']=$res;
                $this->obj->table('fwy_question')->where($w)->setinc('read_num','1');//浏览量加一
                self::returnMsg('10000','',$array);
            }
        }else{
           self::returnMsg('10001','没有找到律师回答记录');
        }
    }



    /*
     *note:问题列表展示，带分页
     *auth:PT
     *date:2019/02/25
     */
    public function showquestion(){
    	$post = $this->request->post();
        $where['status'] = array('gt','-2');
        if (isset($post['page'])) {
            $page = $post['page'];
        }else{
            $page = '1';
        }
        $count = '10';
        if ($post){
            if (isset($post['province_cn']) && !empty($post['province_cn'])) {
                $where['province_cn'] = $post['province_cn'];
            }
            if (isset($post['city_cn']) && !empty($post['city_cn'])) {
                $where['city_cn'] = $post['city_cn'];
            }
            if (isset($post['area_cn']) && !empty($post['area_cn'])) {
                $where['area_cn'] = $post['area_cn'];
            }
            if (isset($post['goods_type_id']) && !empty($post['goods_type_id'])) {
                $where['goods_type_id'] = $post['goods_type_id'];
            }
            if (isset($post['keywords']) && !empty($post['keywords'])) {
                $where['describe'] = ['like','%'.$post['keywords'].'%'];
            }
            if (isset($post['status']) && !empty($post['status'])) {
                $where['status'] = $post['status'];
            }

            $res = $this->obj->table('fwy_question')->where($where)->order('add_time desc')->page($page,$count)->select();
        }else{
            $res = $this->obj->table('fwy_question')->where($where)->order('add_time desc')->page($page,$count)->select();
        }
        if ($res){
            foreach ($res as $k => $v) {
                // $wh['status']='1';
                $wh['uid']=$v['uid'];
                $member=$this->obj->table('fwy_member')->where($wh)->find();
                //获取提问者
                $res[$k]['nickname'] = $member['nickname'];
                $res[$k]['face'] = $member['face'];
                $res[$k]['weburl'] = $this->config['weburl'];
                $res[$k]['pro'] = $this->get_area_nostyle($member['province'])['region_name'];
                $res[$k]['city'] = $this->get_area_nostyle($member['city'])['region_name'];
                $ty['id'] = $v['goods_type_id'];
                $res[$k]['type_cn'] = $this->obj->table('fwy_goods_type')->where($ty)->value('name');

				if(empty($res[$k]['pro'])){
					$res[$k]['pro']='北京市';
				}
				if(empty($res[$k]['city'])){
					$res[$k]['city']='市辖区';
				}
                $w['pid']=$v['id'];
                $w['is_first_answer']='0';//是否是第一条回答(0第一  ,1不是第一)
                $w['user_type'] = '0';//用户类型（0律师,1用户）
                $res[$k]['answer'] = $this->obj->table('fwy_answer')->where($w)->count();
                // 时间友好显示
                $res[$k]['add_time']=$this->friendlyDate($v['add_time']);
            }
            $list['list'] = $res;
            $list['total'] = $this->obj->table('fwy_question')->where($where)->count();
            // var_dump($this->sysconfig);exit;
            self::returnMsg('10000','',$list);
        }else{
            self::returnMsg('10001','没有找到相关数据！');
        }
    }


    /*
     *note:问答展示（含第一条回答），带分页
     *auth:PT
     *date:2019/02/25
     */
    public function showAll(){
        $post = $this->request->post();
        $where['status'] = array('gt','-2');
        if ($post)
        {
            if (isset($post['province_cn'])) {
                $where['province_cn'] = $post['province_cn'];
            }
            if (isset($post['city_cn'])) {
                $where['city_cn'] = $post['city_cn'];
            }
            if (isset($post['area_cn'])) {
                $where['area_cn'] = $post['area_cn'];
            }
            if (isset($post['goods_type_id'])) {
                $where['goods_type_id'] = $post['goods_type_id'];
            }
            if (isset($post['keywords'])) {
                $where['describe'] = ['like','%'.$post['keywords'].'%'];
            }
            if (isset($post['page'])) {
            	$page = $post['page'];
            }else{
            	$page = '1';
            }
            $count = '10';
            $res = $this->obj->table('fwy_question')->order('add_time desc')->page($page,$count)->select();
        }else{
            $res = $this->obj->table('fwy_question')->order('add_time desc')->page($page,$count)->select();
        }

        if ($res)
        {
            unset($where);
            //数据处理
            foreach ($res as $key => $value)
            {
                //获取提问者
                $res[$key]['user_name'] = get_name($this->obj->table('fwy_member'),"uid = {$value['uid']}",'nickname');
                $ty['id'] = $v['goods_type_id'];
                $res[$k]['type_cn'] = $this->obj->table('fwy_goods_type')->where($ty)->value('name');
                //获取回答者
                $where['pid'] = $w['id'] = $value['id'];
                $where['is_first_answer'] = '0';//是否是第一条回答(0第一  ,1不是第一)
                $where['user_type'] = '0';//用户类型（0律师,1用户）
                $list = $this->obj->table('fwy_answer')->where($where)->order('add_time desc')->select();
                foreach ($list as $ky => $vl)
                {
                    $list[$ky]['lawyer_name'] = get_name($this->obj->table('fwy_lawyer'),"uid = {$vl['uid']}");
                }
                $res[$key]['answer'] = $list;
                // 时间友好显示
                $res[$key]['add_time']=$this->friendlyDate($value['add_time']);
            }
            $this->obj->table('fwy_question')->where($w)->setinc('read_num','1');//浏览量+1
            $msg['list'] = $res;
            $msg['total'] = $this->obj->table('fwy_question')->count();
            self::returnMsg('10000','',$msg);
        }else{
            self::returnMsg('10001','没有找到相关数据！');
        }
    }


    /*
     *note:获取追问追答数据接口
     *auth:PT
     *date:2019/02/25
     */
    public function showother(){
    	$post = $this->request->post();
        if ($post['id']){
            //获取追问追答数据
            $where['pid'] = $post['id'];//问题id
            $where['aid'] = $post['aid'];//第一条回答id
            $where['is_first_answer'] = '1';//是否是第一条回答(0第一  ,1不是第一)
            $list = $this->obj->table('fwy_answer')->where($where)->order('add_time desc')->select();
            if ($list) {
            	self::returnMsg('10000','',$list);
            }else{
            	self::returnMsg('10014');
            }
        }else{
           	self::returnMsg('10001','没有找到相关数据！');
        }
    }

    /*
     *note:追问律师接口
     *auth:PT
     *date:2019/02/25
     */
    public function questionagain(){
        $data=$this->request->post();//id(问题id),uid(用户id),content(追问内容),aid(第一条回答id),token
        if ($data){
            $data['user_type']='1';//1用户
            $data['is_first_answer'] = '1';////是否是第一条回答(0第一  ,1不是第一)
            $data['add_time'] =  time();//追问时间
            $data['status'] =  '1';//追问时间
            $data['pid'] = $data['id'];
            $where['id'] = $data['id'];
            $uid = $this->obj->table('fwy_question')->where($where)->value('uid');
            if ($uid == $data['uid']) {
                self::returnMsgAndToken('10004');
            }
            unset($data['id']);
            unset($data['token']);
            $res = $this->obj->table('fwy_answer')->data($data)->insert();
            if ($res) {
                self::returnMsgAndToken('10000','追问成功');
            }else{
            	self::returnMsgAndToken('10014','追问失败！');
            }
        }else{
        	self::returnMsgAndToken('10004');
        }
    }


    /*
 *note:获取，省份，城市，县城（无样式）
 *auth:杨炜
 * input $id 查询的id
 * return array
 */
	function get_area_nostyle($id = 0)
	{
	    $where['id'] = $id;
	    $res = $this->obj->table('fwy_region')->field('id,region_code,region_name,parent_id')->where($where)->find();
	    return $res;
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

    /**
     * auth YW
     * note 空操作
     * date 2018-08-06
     */
    public function _empty(){
        self::returnMsg('10107','操作不合法');
    }




}
