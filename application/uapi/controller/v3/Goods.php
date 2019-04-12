<?php
namespace app\uapi\controller\v3;
use think\Db;


class Goods extends Index {

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
     *note:获取合同商品类型列表并展示第一个类型的商品列表
     *auth:彭桃
     *date:2019/02/22
     */
    public function newgetgoods(){
        $w['name_en']='template';
        $w['status']='1';
        $who['iid']=$this->obj->table('fwy_goods_type')->where($w)->value('id');
        $who['status']=['>','0'];;
        // 获取合同类型
        $arr=$this->obj->table("fwy_goods_type")->where($who)->select();
        $types['status']='1';
        $types['goods_type']=$arr['0']['id'];
        // 获取第一个合同类型下的商品列表
        $result=$this->obj->table("fwy_goods")->field('id,goods_name,goods_type,selling_price')->where($types)->select();
        // echo "<pre>";
        // var_dump($result);exit;
        if($arr){
            if ($result) {
                foreach ($result as $key => $value) {
                    $where['gid']=$result[$key]['id'];
                    $result[$key]['img']=$this->obj->table("fwy_goods_images")->where($where)->value('images');
                    $result[$key]['weburl'] = $this->config['weburl'];
                    $goodstype['id']=$value['goods_type'];
                    $result[$key]['goods_type']=$arr[$key]['name'];
                    $return['goodstypeall']=$arr;
                    $return['list']=$result;
                }
            }else{
                $return['goodstypeall']=$arr;
                $return['list']='';
            }
            self::returnMsg('10000','',$return);
        }else{
            self::returnMsg('10001','没有找到相关数据！');
        }
    }

/*
     *note:根据合同商品类型id获取商品列表
     *auth:彭桃
     *date:2019/02/22
     */
    public function newgethtgoodsbyid(){
        $post = $this->request->post();
        $id=$post['id'];
        if (empty($id)) {
            self::returnMsg('10004');
        }
        $table=$this->obj;
        $types['goods_type']=$id;
        $types['status']='1';
        // 获取合同类型下的商品列表
        $result=$table->table("fwy_goods")->field('id,goods_name,goods_type,selling_price')->where($types)->select();
        if($result){
            foreach ($result as $key => $value) {
                $where['gid']=$value['id'];
                $result[$key]['img']=$table->table("fwy_goods_images")->where($where)->value('images');
                $result[$key]['weburl'] = $this->config['weburl'];
                $goodstype['id']=$value['goods_type'];
                // $result[$key]['goods_type']=$arr[$key]['name'];
            }
            self::returnMsg('10000','',$result);
        }else{
            self::returnMsg('10001','没有找到相关数据！');
        }
    }

    /*
     *note:根据合同商品id获取商品详情
     *auth:彭桃
     *date:2019/02/22
     */
    public function newgethtdetailbyid(){
        $id['id']=$this->request->post()['id'];
        if (empty($id['id'])) {
            self::returnMsg('10004','参数错误');
        }
        $id['status']='1';
        $table=$this->obj;
        //根据传递过来id获取合同数据
        $result=$table->table("fwy_goods")->field('id,goods_name,selling_price,goods_type,describe,click,detail')->where($id)->find();
        $where['gid']=$result['id'];
        $result['img']=$table->table("fwy_goods_images")->where($where)->value('images');
        $result['weburl'] = $this->config['weburl'];
        if (isset($this->request->post()['uid'])) {
            $uid=$this->request->post()['uid'];
            $map['uid']=$uid;
            $map['goods_id']=$result['id'];
            if ($this->obj->table('fwy_collection')->where($map)->find()) {
                $result['favor']=1;
            }else{
                $result['favor']=0;
            }
        }
        $oid['goods_type']=$result['goods_type'];
        $oid['id']=['<>',$this->request->post()['id']];
        $oid['status'] = '1';
        $result['other']=$table->table("fwy_goods")->field('id,goods_name')->where($oid)->select();
        if($result){
            // 访问接口一次添加一次阅读次数
            $table->table('fwy_goods')->where($id)->setInc('click','1');
            self::returnMsg('10000','',$result);
        }else{
            self::returnMsg('10001','没有找到相关数据！');
        }
    }

    /*
     *note:根据标识获取法务服务、法律培训、律师函、服务商城商品列表
     *auth:彭桃
     *type
     *date:2019/02/22
     */
    public function newgetgoodsbyid(){
        $table=$this->obj;
        $type = $this->request->post()['type'];
        $w['name_en']=['like','%'.$type.'%'];
        $w['status']='1';
        $who['iid']=$this->obj->table('fwy_goods_type')->where($w)->value('id');
        $who['status']='1';
        // 获取id
        $arr=$table->table("fwy_goods_type")->where($who)->select();
        if($arr){
            foreach ($arr as $key => $value) {
                $arr[$key]['weburl']=$this->config['weburl'];
            }
            self::returnMsg('10000','',$arr);
        }else{
            self::returnMsg('10001','没有找到相关数据！');
        }
    }

 /*
     *note:根据商品id获取法务服务、法律培训、律师函服务商城对应的详情
     *auth:彭桃
     *date:2019/02/22
     */
    public function newgetdetailbyid(){
        $table=$this->obj;
        $types['goods_type'] =$this->request->post()['id'];
        if (empty($types['goods_type'])) {
            self::returnMsg('10004','参数错误');
        }
        $types['status']='1';
        // 获取商品详情
        $arr=$table->table("fwy_goods")->where($types)->limit('1')->find();
        if($arr){
            $where['gid']= $id['id'] =$arr['id'];
            // 获取商品图片
            $arr['img']=$table->table("fwy_goods_images")->where($where)->value('images');
            $arr['weburl'] = $this->config['weburl'];
             // 访问接口一次添加一次阅读次数
            $table->table('fwy_goods')->where($id)->setInc('click','1');
            $wp['gid']=$this->request->post()['id'];
            //$wp['status']=array('in','1,2,3,4');
            $msg['sale']=$table->table('fwy_order')->where($wp)->count();
            if($msg['sale'] == '0'){
                $wpw['gid']=$arr['id'];
                $msg['sale']=$table->table('fwy_order')->where($wpw)->count();
            }
            self::returnMsg('10000','',$arr);
        }else{
            self::returnMsg('10001','没有找到相关数据！');
        }
    }

    /*
     *note:根据法务服务、法律培训、律师函服务商城列表获取到的iid获取全部商品
     *auth:彭桃
     *date:2019/02/22
     */
    public function newgetallgoods(){
        $table=$this->obj;
        $types['goods_type'] =$this->request->post()['id'];
        if($types){
            // $id=array_column($arr,'id');
            // $where['goods_type']= array('in',$id);
            // 获取商品名称
            $res=$table->table("fwy_goods")->field('id,goods_name,selling_price')->where($types)->select();
            self::returnMsg('10000','',$res);
        }else{
            self::returnMsg('10001','没有找到相关数据！');
        }
    }

    /*
     *note:获取文书服务详情
     *auth:彭桃
     *date:2019/02/22
     */
    public function newgetdoc(){
        $table=$this->obj;
        $w['name_en'] = ['like','%doc%'];
        $w['status'] = '1';
        $w['iid'] = '0';
        $id=$table->table("fwy_goods_type")->where($w)->value('id');
        $types['goods_type'] =$id;
        $types['status']='1';
        // 获取商品id
        $arr=$table->table("fwy_goods")->where($types)->find();

        if($arr){
            self::returnMsg('10000','',$arr);
        }else{
            self::returnMsg('10001','没有找到相关数据！');
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
