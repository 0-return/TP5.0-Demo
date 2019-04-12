<?php
namespace app\uapi\controller\v3;
use think\Db;

class Help extends Index{
    protected $config;
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
     *note:法律援助发起订单
     *auth:PT
     *uid,token,title,lid
     *date:2019/03/16
     */
    public function helporder(){
        $post = $this->request->post();
        if (empty($post['uid']) || empty($post['title'])) {
            self::returnMsgAndToken('10004');
        }else{
            if ($post['uid'] ==$post['lid']) {
                self::returnMsgAndToken('10012','发起对象不能为自己！');
            }
            // 检查是否有未接订单
            $w['uid'] = $post['uid'];
            $w['lid'] = $post['lid'];
            $w['ustatus'] = '0';
            $w['status'] = '1';
            $w['create'] = '1';
            $order = $this->obj->table('fwy_order_help')->where($w)->find();
            if ($order) {
                $order['flag'] = 'help';
                self::returnMsgAndToken('10011','该律师已有您的未接订单',$order);
            }else{
                unset($post['token']);
                $post['describe'] = '法律援助';
                $post['order_no'] = $this->getOnlyCode();
                $post['add_time'] = time();
                $post['ustatus'] = '0';
                $post['deliver'] = '0';
                $post['receive'] = '0';
                $post['comment'] = '0';
                $post['create'] = '1';
                $res = $this->obj->table('fwy_order_help')->insert($post);
                if ($res) {
                    $post['flag'] = 'help';
                    self::returnMsgAndToken('10000','发单成功',$post);
                }else{
                    self::returnMsgAndToken('10014','发单失败');
                }
            }

        }
    }


    /**
     * note:生成订单编号
     * auth:YW
     * date:2018/05/29
     */
    private function getOnlyCode()
    {
        $code = get_str_guid();
        $map['order_no'] = $code;
        if ($this->obj->table('fwy_order_help')->where($map)->count() > 0) {
            $this->getOnlyCode();
        } else {
            return $code;
        }
    }


    /**
     * note:结束订单
     * auth:PT
     * uid , token , order_no , lid
     * date:2019/03/18
     */
    public function endorder()
    {
        $post = $this->request->post();
        if (empty($post['uid']) || empty($post['order_no']) || empty($post['lid'])) {

            self::returnMsgAndToken('10004');
        } else {
            // 拼装where条件
            $where['order_no'] = $post['order_no'];
            $where['uid'] = $post['uid'];
            $where['lid'] = $post['lid'];
            $where['status'] = '1';
            $where['ustatus'] = '1';
            $data['ustatus'] = '2';
            $data['receive'] = '1';
            $data['edit_time'] = time();
            $o = $this->obj->table('fwy_order_help')->where($where)->update($data);
            if ($o) {
                self::returnMsgAndToken('10000','结束成功');
            } else {
                self::returnMsgAndToken('10014','结束失败');
            }
        }
    }


    /**
     * note:获取订单列表
     * auth:PT
     * uid , token ,ustatus , page
     * date:2019/03/18
     */
    public function orderlist()
    {
        $post = $this->request->post();
        $p = $post['page'];
        $c = 10;
        if (empty($p)) {
            $p = '1';
        }
        if (!empty($post['ustatus'])) {
            $where['ustatus'] = $post['ustatus'];
        }
        $obj = $this->obj;
        $where['status'] = '1';
        $where['create'] = '1';
        $where['uid'] = $post['uid'];
        $arr = $obj->table('fwy_order_help')->where($where)->page($p, $c)->order('add_time desc')->select();
        if ($arr) {
            foreach ($arr as $k => $v) {
                $w['uid'] = $v['lid'];
                $lawyer = $obj->table('fwy_lawyer')->where($w)->find();
                $arr[$k]['username'] = $lawyer['username'];
                $arr[$k]['face'] =$this->config['weburl'].'/'.$lawyer['face'];
            }
            $msg['count'] = $obj->table('fwy_order_help')->where($where)->count();
            $msg['list'] = $arr;
            self::returnMsgAndToken('10000','',$msg);
        }else{
            self::returnMsgAndToken('10001','没有找到相关数据！');
        }
    }


    /**
     * note:获取聊天列表
     * auth:PT
     * uid , token ,ustatus , page
     * date:2019/03/18
     */
    public function chatlist()
    {
        $post = $this->request->post();

        if (!empty($post['ustatus'])) {
            $where['ustatus'] = $post['ustatus'];
        }
        $obj = $this->obj;
        $where['status'] = '1';
        $where['create'] = '1';
        $where['ustatus'] = array('neq','0');
        $where['uid'] = $post['uid'];
        $arr = $obj->table('fwy_order_help')->where($where)->order('add_time desc')->select();
        if ($arr) {
            foreach ($arr as $k => $v) {
                $w['uid'] = $v['lid'];
                $lawyer = $obj->table('fwy_lawyer')->where($w)->find();
                $arr[$k]['username'] = $lawyer['username'];
                $arr[$k]['face'] =$this->config['weburl'].'/'.$lawyer['face'];
            }
            $msg['count'] = $obj->table('fwy_order_help')->where($where)->count();
            $msg['list'] = $arr;
            self::returnMsgAndToken('10000','',$msg);
        }else{
            self::returnMsgAndToken('10001','没有找到相关数据！');
        }
    }


    /**
     * note:取消订单
     * auth:PT
     * date:2019/03/18
     * input order_no 订单编号 , uid , token
     * */
    public function cancelOrder()
    {
        $order = $this->request->post();
        if ($order) {
            $obj = $this->obj;
            $where['order_no'] = $order['order_no'];
            $where['uid'] = $order['uid'];
            $where['ustatus'] = '0';
            $where['status'] = '1';
            $where['create'] = '1';
            $res = $obj->table('fwy_order_help')->where($where)->find();
            if ($res['create'] == '-1') {
                self::returnMsgAndToken('10108','该订单已取消');
            } else {
                $data['end_time'] = time();
                $data['status'] = '-1';     //彻底删除
                $data['create'] = '-1';
                $r = $obj->table('fwy_order_help')->where($where)->update($data);
                if ($r) {
                    self::returnMsgAndToken('10000','订单取消成功');
                } else {
                    self::returnMsgAndToken('10001','订单取消失败');
                }
            }
        } else {
            self::returnMsgAndToken('10004');
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
        $ustatus=$obj->table("fwy_order_help")->where($w)->value('ustatus');
        $status=$obj->table("fwy_order_help")->where($w)->value('status');
        if($ustatus == 3){
            self::returnMsgAndToken('10108','律师拒绝接单');
        }
        if($ustatus == 1){
            $msg['list']=[];
            $msg['list']=$obj->table("fwy_order_help")->where($w)->value('lid');
            $msg['username']=$obj->table("fwy_lawyer")->where("uid='{$msg['list']}'")->value('username');
            $msg['face']=$obj->table("fwy_lawyer")->where("uid='{$msg['list']}'")->value('face');
            $msg['weburl'] = $this->config['weburl'];
            self::returnMsgAndToken('10000','接单成功',$msg);
        }else{
            self::returnMsgAndToken('10109','没有接单');
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
