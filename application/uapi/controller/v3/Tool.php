<?php
namespace app\uapi\controller\v3;
use think\Request;
use think\Db;


class Tool extends Index {

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
     *note:获取服务动态
     *auth:彭桃
     *date:2019/02/20
     */
    public function get_active(){

        $url = $this->config['weburl'];
        //获取律师入住信息
        $where['status'] = '2';
        $lawyer = $this->obj->table('fwy_lawyer')->field('id,uid,face,username,add_time')->order('edit_time desc')->limit(5)->where($where)->select();
        if ($lawyer)
        {
            foreach ($lawyer as $key => $value)
            {

                $lawyer[$key]['id'] = $value['id'];
                $lawyer[$key]['lid'] = $value['uid'];
                $lawyer[$key]['title'] = $value['username'];
                $lawyer[$key]['face'] = $value['face'];
                $lawyer[$key]['weburl'] = $url;
                $lawyer[$key]['showtime'] = $this->dayfast($value['add_time']);
                $str = '欢迎['.$value['username'].']律师入住平台成功！';
                $lawyer[$key]['showmsg'] = $str;
                unset($lawyer[$key]['add_time']);
            }
        }
        unset($where);


        //获取留言咨询
        $where['status'] = '2';
        $quest = $this->obj->table('fwy_question')->field('id,uid,lid,describe,add_time')->order('add_time desc')->where($where)->limit('10')->select();
        if ($quest)
        {

            foreach ($quest as $key => $value)
            {
            	$te = explode(',',trim($value['lid'],','));
                $lid = $te[count($te)-1];
                $where['uid'] = $lid;
                $where['status'] = '2';
                $lwy = $this->obj->table("fwy_lawyer")->field('username,face')->where($where)->find();
                if ($lwy)
                {
                    $qlw[$key]['id'] = $value['id'];
                    $qlw[$key]['lid'] = $lid;
                    $qlw[$key]['title'] = $value['describe'];
                    $qlw[$key]['face'] = $lwy['face'];
                    $qlw[$key]['weburl'] = $url;
                    $qlw[$key]['username'] = $lwy['username'];
                    $qlw[$key]['showtime'] = $this->dayfast($value['add_time']);
                    unset($where);
                    $where['uid'] = $value['uid'];
                    $nickname = $this->obj->table('fwy_member')->where($where)->find()['nickname'];
                    $qlw[$key]['showmsg'] = '回答了用户['.$nickname.']的问答咨询';
                }
            }
        }
        unset($where);

        //获取订单
        $where['status'] = array('in','3,4');
        //咨询模式第二个where条件
        $order = $this->obj->table('fwy_order')->field('id,goods_type,add_time,lid,title,gid')->where($where)->where("goods_type_en='quick'  OR  goods_type_en = 'doc'  OR  goods_type_en = 'itext'")->order('add_time desc')->limit('10')->select();
        unset($where);
        if ($order)
        {
            foreach ($order as $key => $value)
            {
                //查询律师
                $where['uid'] = $value['lid'];
                $lwy = $this->obj->table("fwy_lawyer")->field('username,face')->where($where)->find();
                $olw[$key]['id'] = $value['id'];
                $olw[$key]['lid'] = $value['lid'];
                $olw[$key]['title'] = $value['title'];
                $olw[$key]['face'] = $lwy['face'];
                $olw[$key]['weburl'] = $url;
                $olw[$key]['username'] = $lwy['username'];
                $olw[$key]['showtime'] = $this->dayfast($value['add_time']);
                $olw[$key]['showmsg']='完成了一次'.$value['title'].'方面的咨询';
            }
        }else{
            $lwy = $this->obj->table("fwy_lawyer")->field('uid,username,face')->limit('2')->select();
            for ($i=0; $i < count($lwy); $i++) {
                $olw[$i]['username']=$lwy[$i]['username'];
                $olw[$i]['lid']=$lwy[$i]['uid'];
                $olw[$i]['face']=$lwy[$i]['face'];
                $olw[$i]['weburl']=$url;
                if ($i%2==0) {
                    $olw[$i]['showtime']=$this->dayfast(time()-2800);
                    $olw[$i]['showmsg']='完成了一次交通事故方面的咨询';
                }else{
                    $olw[$i]['showtime']=$this->dayfast(time()-8000);
                    $olw[$i]['showmsg']='完成了一次合同纠纷方面的咨询';
                }
            }
        }
        if (empty($olw)) {
            $olw = array();
        }
        if (empty($lawyer)) {
            $lawyer = array();
        }
        if (empty($qlw)) {
            $qlw = array();
        }
        $temp = array_merge_recursive($olw,$lawyer,$qlw);
        shuffle($temp);
        // unset($olw,$lawyer,$temp,$where);
        if ($temp) {
            self::returnMsg('10000','',$temp);
        }else{
            self::returnMsg('10001','没有找到相关数据');
        }
    }

    /*
     *note:时间友好显示 $the_time时间戳
     *auth:彭桃
     *date:2018/01/26
     */
    function dayfast($the_time){
        $now_time = time();
        $show_time = $the_time;
        $dur = $now_time - $show_time;
        if ($dur < 0) {
            $the_time = date("Y-m-d",$the_time);
            return $the_time;
        }else{
            if ($dur < 60) {
                return $dur . '秒前';
            }else{
                if($dur < 3600){
                    return floor($dur / 60) . '分钟前';
                }else{
                    if ($dur < 86400) {
                        return floor($dur / 3600) . '小时前';
                    }else{
                        if($dur < 259200){//3天内
                            return floor($dur / 86400) . '天前';
                        }else{
                            $the_time = date("Y-m-d",$the_time);
                            return $the_time;
                        }
                    }
                }
            }
        }
    }


        /**
     * note:获取推荐律师
     * auth:PT
     * regionid
     * date:2019/02/20
     */
    public function getdata(){
        $post = $this->request->post();
        //获取省份
        $region = $this->obj->table('fwy_region')->field('id,region_name')->where('parent_id = 1')->select();
        //获取行业分类
        $industrys = $this->obj->table('fwy_goods_type')->where('iid = 58')->select();
        $msg['data']['region'] = $region;
        $msg['data']['industry'] = $industrys;

        $filed = 'id,uid,username,face,province,city,area,online,price,status,industryid,is_top,work_time,company,isshow,work_time as practicestartime,company as practicelaw,isshow as isshowwork,uid as lawid,is_receipt,praiserate';
        //获取地区推荐，获取全国推荐，获取全国

        if (!empty($post['regionid'])){
            $where['province'] = $post['regionid'];
        }
        $where['is_top'] = '1';
        $where['is_receipt'] = '1';
        $where['online'] = '1';
        $where['status'] = '2';
        if (empty($post['page'])) {
            $p = 1;
        }else{
            $p = $post['page'];
        }
        $c = 10;
        $count = $this->obj->table('fwy_lawyer')->where($where)->count();
        $res = $this->obj->table('fwy_lawyer')->field($filed)->where($where)->order('praiserate desc')->page($p,$c)->select();
        // return self::returnMsg('10000','获取成功',$res);
        //查询行业信息，拼装
        foreach ($res as $key => $value){
            if ($value['price'] == 0 || empty($value['price']))     //如果律师没有设置图文咨询费用，默认为系统设置图文咨询费用。
            {
                $res[$key]['price'] = $this->obj->table('fwy_assist')->where('id =1')->value('doc_price');
            }
            //获取律师行业信息
            $industrys = explode(',',trim($value['industryid'],','));
            foreach ($industrys as $ky => $vl)
            {
                $industry = $this->obj->table('fwy_goods_type')->where('id = '.$vl)->value('name');
                $res[$key]['industry'][$ky]['id'] = $vl;
                $res[$key]['industry'][$ky]['name'] = $industry;
            }
            //获取律师地区信息
            $res[$key]['province'] = $this->get_area_nostyle($value['province'])['region_name'];
            $res[$key]['city'] = $this->get_area_nostyle($value['city'])['region_name'];
            $res[$key]['area'] = $this->get_area_nostyle($value['area'])['region_name'];
            $res[$key]['face'] = $value['face'];
            $res[$key]['weburl'] = $this->config['weburl'];
            //获取律师附加信息

            $res[$key]['practicestartime'] = $value['practicestartime'];
            $res[$key]['practicelaw'] = $value['practicelaw'];
            $exp[$key] = $value['is_top'];
        }

        array_multisort($res);            //对数组进行排序

        $arr['list']=$res;
        $arr['total'] = $count;
        if ($arr) {
        	self::returnMsg('10000','',$arr);
        }else{
        	self::returnMsg('10001','没有找到相关数据');
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





}
