<?php

namespace app\uapi\controller\v3;
use app\common\controller\Common;
use think\Db;

class Lawyer extends Index
{

    /**
     * 初始化
     * 检查请求类型，数据格式等
     */
    public function _initialize()
    {
        parent::_init();

    }


    /*
     *note:获取所有信息（根据地区，行业，进行筛选，多种排序方式）(2.0)
     *auth:杨炜
     *date:2018/05/17 07/04更新
     */
    public function showAll()
    {
        // TODO: Implement showAll() method.
        //根据条件获取律师信息
        $post = $this->request->post();
        $regionid = isset($post['regionid']) ? $post['regionid'] : '';          //地区
        $industryid = isset($post['industryid']) ? $post['industryid'] :'';      //行业
        $work_time = isset($post['work_time']) ? $post['work_time'] : '';            //从业年限
        // $type = $post['type'];                  //排序
        $page = isset($post['page']) ? $post['page'] : '1';                  //起始页
        $count = '10';                //每页数值
        //附加信息只获取一次
        if ($page == 1)
        {
            $w['parent_id']='1';
            //获取省份
            $region = $this->obj->table('fwy_region')->field('id,region_name')->where($w)->select();
            //获取行业分类
            $ww['name_en']=['like','%letter%'];
            $id['iid']=$this->obj->table("fwy_goods_type")->where($ww)->value('id');
            $industrys = $this->obj->table('fwy_goods_type')->where($id)->select();
            $arr['region'] = $region;
            $arr['industry'] = $industrys;
            $arr['year'] = array('1-3','3-5','5-10','10-100');
        }

        $where['is_receipt'] = '1'; //是否接单(1是，0否)
        $where['status'] = '2';  //状态（-1删除，0未上传，1审核中，2认证通过，3认证未通过）
        $filed = 'id,uid,username,face,province,city,area,online,price,status,industryid,is_top,work_time,company,isshow,work_time as practicestartime,company as practicelaw,isshow as isshowwork,uid as lawid,is_receipt,praiserate';
        //区间查询
        if (!empty($regionid)){
            $where['province'] = $regionid;
        }
        // 行业筛选
        if (!empty($industryid)){
            $where['industryid'] = array('like','%'.$industryid.'%');
         }
        // 从业年限筛选
        if ($work_time){
            $where['work_time'] = array('between',$work_time);
            $num = $this->obj->table('fwy_lawyer')->where($where)->count();
            $res = $this->obj->table('fwy_lawyer')->field($filed)->where($where)->order('online desc,work_time desc')->page($page,$count)->select();

        }else{
            $num = $this->obj->table('fwy_lawyer')->where($where)->count();
            $res = $this->obj->table('fwy_lawyer')->field($filed)->where($where)->order('online desc')->page($page,$count)->select();

        }

        //查询行业信息，拼装
        if ($res) {
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
            $arr['list']=$res;
            $arr['total'] = $num;
            self::returnMsg('10000','',$arr);
        }else{
            self::returnMsg('10001','没有找到相关数据');
        }
    }

    /*
     *note:根据ID获取我的星级
     *auth:周杨
     *date:2018/01/15
     */
    public function getmystarbyid(){
        $lawyerid=$this->request->post('lawyer_id');
        $table=$this->obj;
        //根据传递过来的广告类型来获取广告数据
        //$result=$table->table("fwy_member")->where("lawyerid='{$lawyerid}'")->select();

        $data['velocity']=$table->table("fwy_lawyerreview")->where("lawyer_id='{$lawyerid}'")->avg('velocity');
        $data['attitude']=$table->table("fwy_lawyerreview")->where("lawyer_id='{$lawyerid}'")->avg('attitude');
        $data['major']=$table->table("fwy_lawyerreview")->where("lawyer_id='{$lawyerid}'")->avg('major');
        $data['count']=$table->table("fwy_lawyerreview")->where("lawyer_id='{$lawyerid}'")->avg('count');
        $data['star']=$table->table("fwy_lawyerreview")->where("lawyer_id='{$lawyerid}'")->avg('star');
        if($data){
            self::returnMsg('10000','',$data);
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