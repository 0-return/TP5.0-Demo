<?php
namespace app\uapi\controller\v3;
use think\Request;
use think\Db;


class Home extends Index {

    /**
     * 初始化
     *
     * @return \think\Response
     */
    public function _initialize()
    {
        parent::_init();
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {

    }
    /**
     * note:获取广告
     * auth:PT
     * date:2019/02/20
     */
    public function indexTopAdv()
    {
        $post = $this->request->post();
        $where['flag'] = $post['flag'];
        $where['status'] = '1';
        $obj=$this->obj;
        //获取分类
        $id = $obj->table("fwy_advtype")->where($where)->find();unset($where);
        $where['location'] = $id['id'];       //广告类型
        $where['status'] = '1';         //状态

        //获取城市广告
        if (!empty($post['pro']))
        {
            $where['pro'] = $post['pro'];
        }
        //获取城市广告
        if (!empty($post['city']))
        {
            $where['city'] = $post['city'];
        }
        //获取城市广告
        if (!empty($post['area']))
        {
            $where['area'] = $post['area'];
        }
        //用户端和律师端
        if (isset($post['port']) && !empty($post['port']))
        {
            $where['port'] = array('in',$post['port']);
        }

        $res = $obj->table("fwy_adv")->where($where)->select();
        if ($res)
        {
            $res = $this->resultFormat($res);
            self::returnMsg('10000','',$res);
        }else{

            $where['location'] = $id;       //广告类型
            $where['status'] = '1';         //状态
            $res = $obj->table("fwy_adv")->where($where)->select();
            $res = $this->resultFormat($res);
            if ($res)
            {
                self::returnMsg('10000','',$res);
            }else{
                self::returnMsg('10001','没有找到相关数据！');
            }
        }
    }

        /**
     * note:获取法律讲堂数据
     * auth:PT
     * date:2019/02/20
     */
    public function classroom()
    {
        $post = $this->request->post();
        if (empty($post['tag'])) {
            $tag = 'f';
        }else{
            $tag = $post['tag'];
        }
        // 获取法律讲堂文章
        $condition['status']='1';
        $condition['sort']='2';
        //法律讲堂标识(f推荐，h热点)
        $condition['tag'] = $tag;
        $arr=$this->obj->table('fwy_content')->field('id,title,describe')->where($condition)->orderRaw('rand()')->limit('1')->find();
        if ($arr) {
            self::returnMsg('10000','',$arr);
        }else{
            self::returnMsg('10001');
        }
    }

    /*
     *note:首页法律讲堂换一换功能
     *auth:彭桃
     *date:2019/02/20
     */
    public function changeonce(){

        $post = $this->request->post();
        if (empty($post['tag'])) {
            $tag = 'f';
        }else{
            $tag = $post['tag'];
        }
        // 获取法律讲堂文章
        $condition['status']='1';
        $condition['sort']='2';
        //法律讲堂标识(f推荐，h热点)
        $condition['tag'] = $tag;
        $arr=$this->obj->table('fwy_content')->field('id,title,describe')->where($condition)->orderRaw('rand()')->limit('1')->find();
        $arr['describe'] = strip_tags($arr['describe']);
        if ($arr) {
            self::returnMsg('10000','',$arr);
        }else{
            self::returnMsg('10001');
        }

    }

    /*
     *note:获取政务栏目 默认显示北京
     *auth:彭桃
     *date:2019/02/20
     */
    public function governmentaffairs(){
        $post = $this->request->post();
        $w['city'] = isset($post['city'])?$post['city']:'';
        $w['status']=1;
        if (empty($post['page'])) {
            $p = 1;
        }else{
            $p = $post['page'];
        }

        $c = 5;
        $index_bottom_nav=$this->obj->table('fwy_index_bottom_page_navigation')->field('type,eight_pic,big_pic')->where($w)->order('weight asc')->page($p,$c)->select();
        /**/
        if(empty($index_bottom_nav)) {
            $w['city']=2;
            $index_bottom_nav=$this->obj->table('fwy_index_bottom_page_navigation')->field('type,eight_pic,big_pic')->where($w)->order('weight asc')->page($p,$c)->select();
        }
        $count = count($index_bottom_nav);
        if($index_bottom_nav) {
            for ($i=0; $i < $count; $i++) {
                if($index_bottom_nav[$i]['type'] == 1) {/*大图*/
                    unset($index_bottom_nav[$i]['eight_pic']);
                    $big_pic = json_decode($index_bottom_nav[$i]['big_pic'],true);
                    $big_pic['big_pic'] = $this->config['weburl'].$big_pic['big_pic'];
                    $index_bottom_nav[$i]['big_pic'] = $big_pic;
                }else {/*多图标*/
                    unset($index_bottom_nav[$i]['big_pic']);
                    $eight_pic = json_decode($index_bottom_nav[$i]['eight_pic'],true);
                    for ($j=0; $j < count($eight_pic); $j++) {
                        $eight_pic[$j]['icon'] = $this->config['weburl'].$eight_pic[$j]['icon'];
                    }
                    $index_bottom_nav[$i]['eight_pic'] = $eight_pic;
                }
            }
            self::returnMsg('10000','',$index_bottom_nav);
        }else{
            self::returnMsg('10001','没有找到相关数据！');
        }

    }


      /*
     *note:图片地址拼装
     *auth:PT
     *date:2019/1/25
     */
    public function resultFormat($result)
    {
        foreach ($result as $k => $v) {
          if (!empty($v['picture_path'])) {
              $a = unserialize($v['picture_path']);
              foreach ($a as $key => $value) {
                $a[$key] = $this->config['weburl'].$value;
              }
              $result[$k]['pic'] = $a;
              $result[$k]['picture_path'] = unserialize($v['picture_path']);
              $result[$k]['weburl'] = $this->config['weburl'];
          }

        }
        return $result;
    }

    /*
     *note:版本信息
     *auth:PT
     *date:2019/03/15
     */
    public function version()
    {
        $post = $this->request->post();
        if (isset($post['flag']))
        {
            $where['flag'] = $post['flag'];
        }else{
            self::returnMsg('10004');
        }
        //获取app版本信息
        $version = $this->obj->table('fwy_version')->where($where)->order('id desc')->limit('1')->select();
        if ($version) {
            self::returnMsg('10000','',$version);
        }else{
            self::returnMsg('10001');
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

        /**
     * note:进入时检测参数
     * auth:YW
     * date:2018/12/14
     * 会员id[uid]，token[token]，版本号[version]，系统[user_type[ios,android]]
     */
    public function tap()
    {
        $post = $this->request->post();
        /**
         * note:检查用户状态
         */
        $where['uid'] = $post['uid'];
        $where['status'] = '1';
        $res = $this->obj->table('fwy_member')->field('id,uid,status')->order('add_time desc')->limit(1)->where($where)->find();unset($where);
        if ($res)$data['user'] = $res;

        /**
         * note:检查正在进行的订单
         */
        $where['uid'] = $post['uid'];
        $where['status'] = '1';
        $where['deliver'] = array('neq','1');
        $res = $this->obj->table('fwy_order')->field('id,uid,order_no')->order('add_time desc')->limit(1)->where($where)->find();unset($where);
        if ($res)$data['order'] = $res;

        /**
         * note:版本号写入
         */
        $where['uid'] = $post['uid'];
        $where['status'] = '1';
        $temp['version'] = $post['version'];
        $temp['user_type'] = $post['user_type'];
        $temp['edit_time'] = time();
        $res = $this->obj->table('fwy_member')->where($where)->update($temp);unset($where,$temp);
        $data['vsi'] = $res?array('status' => '1'):array('status' => '0');

        $data = data2empty($data);
        if ($data)
        {

            $this->returnMsgAndToken('10000','',$data);
        }else{
            $this->returnMsgAndToken('10001');
        }
    }


    /*
     *note:快速咨询获取所有行业信息
     *auth:彭桃
     *date:2018/03/25
     */
    public function showAll()
    {
        // 返回所有的行业
        $w['name_en'] = array('like',"%quick%");
        $w['status']='1';
        $where['iid']=$this->obj->table('fwy_goods_type')->where($w)->value('id');
        $arr=$this->obj->table('fwy_goods_type')->where($where)->select();
        if ($arr) {
            foreach ($arr as $k => $v) {
                $arr[$k]['weburl']=$this->config['weburl'];
            }
            $this->returnMsg('10000','',$arr);
        }else{
            $this->returnMsg('10001');
        }
    }


         /*
     *note:（检测是否已经接单ajax）
     *auth:PT
     *date:2018/01/13
     */
    public function jiedanajax(){
        $w['order_no']=$this->request->post('order_no');//律师用户id
        $obj=$this->obj;
        $ustatus=$obj->table("fwy_order")->where($w)->value('ustatus');
        $status=$obj->table("fwy_order")->where($w)->value('status');
        if($ustatus == 3){
            self::returnMsgAndToken('10108','律师拒绝接单');
        }
        if($ustatus == 1){
            $msg['list']=[];
            $msg['list']=$obj->table("fwy_order")->where($w)->value('lid');
            $msg['username']=$obj->table("fwy_lawyer")->where("uid='{$msg['list']}'")->value('username');
            $msg['face'] = $obj->table("fwy_lawyer")->where("uid='{$msg['list']}'")->value('face');
            $msg['weburl'] = $this->config['weburl'];
            self::returnMsgAndToken('10000','接单成功',$msg);
        }else{
            self::returnMsgAndToken('10109','没有接单');
        }

    }


}
