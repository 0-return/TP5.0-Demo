<?php
namespace app\uapi\controller\v3;
use app\common\controller\Common;
use think\Db;

class Member extends Index{
    protected $config;
    private $assist;
    /**
     * 初始化
     * 检查请求类型，数据格式等
     */
    public function _initialize()
    {
        parent::_init();
        $this->assist = $this->obj->table('fwy_assist')->where('id = 1')->find();

    }
    /**
     * note:个人中心首页
     * auth:Y
     * date:2019/01/21
     */
    public function index()
    {
        $post = $this->request->post();
        $where['uid'] = $post['uid'];
        $where['status'] = '1';
        $res = $this->obj->table('fwy_member')->where($where)->find();

        $where['uid'] = $post['uid'];
        $wallet = Db::table('os_lawyer')->where($where)->find();
        unset($res['password'],$res['token']);
        $res = array_merge($res,$wallet);
        $res['weburl'] = $this->config['weburl'];
        if ($res)
        {
            self::returnMsgAndToken('10000','',$res);
        }else{
            self::returnMsgAndToken('10001');
        }
    }
    /**
     * note:我的律师
     * auth:YW
     * date:2019/01/21
     */
    public function myLawyer()
    {
        $post = $this->request->post();
        $page['page'] = isset($post['page']) && !empty($post['page'])?$post['page']:'1';
        $c = isset($post['count']) && !empty($post['count'])?$post['count']:5;
        $p = ($page['page']-1)*$c ;

        $where['uid'] = $post['uid'];
        $count = $this->obj->table('fwy_memlawyer')->where($where)->count();
        $res = $this->obj->table('fwy_memlawyer')->where($where)->limit($p,$c)->select();unset($where);

        if ($res) {
            $temp = '';
            foreach ($res as $key => $value) {
                //获取律师头像
                $where['uid'] = $value['lid'];
                $user = $this->obj->table('fwy_lawyer')->where($where)->field('face,province,city,area')->find();
                $res[$key]['weburl'] = $this->config['weburl'];
                $temp[] = array_merge($res[$key],$user);
            }
            $data['total'] = $count;
            $data['list'] = $temp;
            self::returnMsgAndToken('10000','',$data);
        } else {
            self::returnMsgAndToken('10001','没有找到相关数据！');
        }
    }
    /*********************************我的订单********************************************/
    /**
     * note:我的订单
     * auth:YW
     * date:2019/01/21
     */
    public function myOrder()
    {
        $post = $this->request->post();
        $page['page'] = isset($post['page']) && !empty($post['page'])?$post['page']:'1';
        $c = isset($post['count']) && !empty($post['count'])?$post['count']:10;
        $p = $page['page'];
        unset($post['token']);
        self::order($post,$c,$p);

    }

    /**
     * note:订单显示
     * auth:YW
     * date:2019/01/21
     */
    public function show()
    {
        $post = $this->request->post();
        $where['id'] = $post['id'];
        $res = $this->obj->table('fwy_order')->where($where)->find();unset($where);
        if ($res) {
            //获取商品类型
            $where['id'] = $res['goods_type'];
            $res['order_type_cn'] = $this->obj->table('fwy_goods_type')->where($where)->value('name');
            //获取商品名称
            self::returnMsgAndToken('10000','',$res);
        } else {
            self::returnMsgAndToken('10001','没有找到相关数据！');
        }
    }
    /**
     * note:我的订单
     * auth:YW
     * date:2019/01/21
     */
    private function order($condition,$c,$p)
    {
        $pks = array_keys($condition);
        foreach ($pks as $key => $value)
        {
            $where[$value] = array('in',$condition[$value]);
            if ($value == 'deliver') {
                $where['refund'] = '0';
            }
        }
        $where['status'] = array('in','-2,1');
        $where['create'] = '1';
        $where['uid'] = $condition['uid'];
        unset($where['page']);
        $count = $this->obj->table('fwy_order')->where($where)->count();
        $res = $this->obj->table('fwy_order')->where($where)->page($p,$c)->select();
        foreach ($res as $key => $value) {
            if (!empty($value['lid'])) {
                $l['uid'] = $value['lid'];
                $res[$key]['lawyer'] = $this->obj->table('fwy_lawyer')->where($l)->value('username');
                $res[$key]['phone'] = $this->obj->table('fwy_lawyer')->where($l)->value('phone');
            }else{
                $res[$key]['lawyer'] = '';
                $res[$key]['phone'] = '';
            }
            $m['uid'] = $value['uid'];
            $res[$key]['membername'] = $this->obj->table('fwy_member')->where($m)->value('nickname');
            $res[$key]['face'] = $this->obj->table('fwy_member')->where($m)->value('face');
            $res[$key]['weburl'] = $this->config['weburl'];
        }
        $data['total'] = $count;
        $data['list'] = $res;
        if ($res)
        {
            self::returnMsgAndToken('10000','',$data);
        }else{
            self::returnMsgAndToken('10001','没有找到相关数据！');
        }
    }
    /**
     * note:确认订单
     * auth:YW
     * date:2019/01/21
     */
    public function endOrder()
    {
        $post = $this->request->post();
        $where['status'] = $where['ustatus'] = 1;
        $where['order_no'] = $post['order_no'];
        $order = $this->obj->table('fwy_order')->where($where)->find();

        if ($order['urgent'] == '1') $order['total'] = sprintf("%.2f",$order['total'] += $order['urgenttotal']);

        if ($order)
        {
            /**更新订单状态*/
            Db::startTrans();$this->obj->startTrans();
            $data['end_time'] = time();
            $data['receive'] = '1';
            $data['ustatus'] = '2';
            $data['deliver'] = '1';
            $res = $this->obj->table('fwy_order')->where($where)->update($data);

            /**金额结算*/
            $obj = new Common();
            $this->assist['user_type'] = 'lid';
            $wallet = $obj->wallet($order,$this->assist);

            if ($res && $wallet)
            {
                // 增加律师接单数
                if (!empty($order['lid'])) {
                    $ww['uid'] = $order['lid'];
                    $this->obj->table('fwy_lawyer')->where($ww)->setInc('history_order_count','1');
                }
                Db::commit();$this->obj->commit();
                self::returnMsgAndToken('10000','订单确认成功');
            }else{
                Db::rollback();$this->obj->rollback();
                self::returnMsgAndToken('10011','订单确认失败');
            }
        }else{
            self::returnMsgAndToken('10001','没有找到要确认的订单');
        }

    }
    /**
     * note:取消订单（不涉及金额）
     * auth:YW
     * date:2019/01/21
     */
    public function cancelOrder()
    {
        $post = $this->request->post();
        $where['status'] = '1';
        $where['ustatus'] = '0';
        $where['create'] = '1';
        $where['order_no'] = $post['order_no'];
        $where['uid'] = $post['uid'];
        $res = $this->obj->table('fwy_order')->where($where)->find();
        if ($res)
        {
            $data['end_time'] = time();
            $data['status'] = '-1';     //彻底删除
            $data['create'] = '-1';
            // 开启事务
            $m = $this->obj;
            $m->startTrans();//开启事务
            try{
                $res = $m->table('fwy_order')->where($where)->update($data);
                $w['order_no'] = $post['order_no'];
                $res2 = $m->table('fwy_ortemp')->where($w)->delete();
                $m->commit();
                self::returnMsgAndToken('10000','订单取消成功');
            }catch (\PDOException $e) {
                //回滚事务
                $m->rollback();
                self::returnMsgAndToken('10011','订单取消失败');
            }

        }else{
            self::returnMsgAndToken('10001','没有找到要取消的订单');
        }
    }

    /**
     * note:评价订单（支持批量评价）
     * auth:YW
     * date:2019/01/21
     */
    public function rateOrder()
    {
        $post = $this->request->post();
        $where['id'] = array('in',trim($post['id'],','));
        $where['comment'] = '0';
        $data['comment'] = '1';
        $res = $this->obj->table('fwy_order')->where($where)->update($data);

        if ($res)
        {
            self::returnMsgAndToken('10000','评价成功');
        }else{
            self::returnMsgAndToken('10010','评价失败');
        }
    }
    /**
     * note:我的订单（支持批量）
     * auth:YW
     * date:2019/01/21
     */
    public function delOrder()
    {
        $post = $this->request->post();
        $where['id'] = array('in',trim($post['id'],','));
        $data['status'] = '-3';
        $res = $this->obj->table('fwy_order')->where($where)->update($data);
        if ($res)
        {
            self::returnMsgAndToken('10000','删除成功');
        }else{
            self::returnMsgAndToken('10010','删除失败');
        }
    }
    /**
     * note:申请退款
     * auth:YW
     * date:2019/01/21
     */
    public function refund()
    {
        $post = $this->request->post();
        $where['id'] = $post['id'];
        $where['uid'] = $post['uid'];
        $res = $this->obj->table('fwy_order')->where($where)->find();
        if ($res)
        {
            $data['uid'] = $res['uid'];
            $data['ask_time'] = time();                          //申请时间
            $data['order_no'] = $res['order_no'];
            $data['uid'] = $res['uid'];
            $data['refund_cause'] = $post['refund_cause'];        //退款原因
            $data['payway'] = $res['payway'];                   //支付方式（coin：法币，wallet：余额）
            $data['total'] = $res['total'];                     //余额，法币支付金额
            $this->obj->startTrans();
            $refund = $this->obj->table('fwy_aftersale')->data($data)->insert();
            $_data['refund'] = '1';
            $res = $this->obj->table('fwy_order')->where($where)->update($_data);
            if($refund && $res)
            {
                $this->obj->commit();
                self::returnMsgAndToken('10000','退款申请成功');
            }else{
                $this->obj->rollback();
                self::returnMsgAndToken('10010','退款申请失败');
            }
        }else{
            self::returnMsgAndToken('10001','没有找到要退款的订单');
        }
    }

    /**
     * note:确认电话联系
     * auth:YW
     * date:2019/02/27
     */
    public function confirmContactOrder()
    {
        $post = $this->request->post();
        $where['order_no'] = $post['order_no'];
        $where['deliver'] = '2';
        $res = $this->obj->table('fwy_order')->where($where)->find();
        if ($res)
        {
            $data['deliver'] = '3';
            $res = $this->obj->table('fwy_order')->where($where)->update($data);
            if ($res)
            {
                self::returnMsgAndToken('10000','确认成功，等待律师发送报价订单');
            }else{
                self::returnMsgAndToken('10011','确认失败，请联系客服！');
            }
        }else{
            self::returnMsgAndToken('10001','没有找到要确认的订单');
        }
    }


    /*********************************我的订单********************************************/

    /*********************************密码修改********************************************/
    /**
     * note:重置密码
     * auth:PT
     * date:2019/01/21
     * 参数：oldpassword，newpassword,phone，checkcode（验证码）
     */
    public function forget()
    {
        $post = $this->request->post();
        // 判断验证码是否正确
        if (!isset($post['checkcode']) && $post['checkcode'] != session($this->request->module().'_code')) {
            self::returnMsg('10104','验证码错误');
        }
        $where['username'] = $post['username'];

        if ($post['is_login'] == 'true')          //登录状态下修改密码
        {
            $where['password'] = md5($post['oldpassword']);
            $res = $this->obj->table('fwy_member')->where($where)->find();
            self::forget_islogin($res,$post,$where);
        }else{                                  //未登录下修改密码
            $res = $this->obj->table('fwy_member')->where($where)->find();
            self::forget_islgout($res,$post,$where);
        }

    }
    /**
     * note:登陆中修改密码
     * auth:YW
     * date:2019/02/19
     *
     */
    private function forget_islogin($res,$post,$where)
    {

        // 验证原始密码
        if ($res['password'] != md5($post['oldpassword'])) {
            self::returnMsgAndToken('10010','原始密码错误');
        }

        if ($res) {
            $post['password'] = md5($post['password']);
            try {
                // 修改主数据库密码
                $this->obj->table('fwy_member')->where($where)->setField('password', $post['password']);
                self::returnMsgAndToken('10000','密码修改成功');
            } catch (\PDOException $e) {
                self::returnMsgAndToken('10010','密码修改失败');
            }
        } else {
            self::returnMsgAndToken('10010','密码修改失败');
        }
    }
    /**
     * note:未登录修改密码
     * auth:YW
     * date:2019/02/19
     *
     */
    private function forget_islgout($res,$post,$where)
    {

        if ($res) {
            $post['password'] = md5($post['password']);
            try {
                // 修改主数据库密码
                $this->obj->table('fwy_member')->where($where)->setField('password', $post['password']);
                self::returnMsg('10000','密码修改成功');
            } catch (\PDOException $e) {
                self::returnMsg('10010','密码修改失败');
            }
        } else {
            self::returnMsg('10010','密码修改失败');
        }
    }
    /*********************************密码修改********************************************/

    /*********************************手机换绑********************************************/
    /**
     * note:1,检测手机号
     * auth:YW
     * date:date:2019/02/19
     */
    public function checkPhone()
    {
        $post = $this->request->post();

        // 判断验证码是否正确
        if ($post['checkcode'] != session($this->request->module().'_code')) {
            self::returnMsgAndToken('10104','验证码错误');
        }
        unset($post['checkcode'],$post['token']);

        //检测是否和原账号是否一致
        $where['uid'] = $post['uid'];
        $where['username'] = $post['username'];
        // 判断验证码是否正确
        if ($post['checkcode'] != session($this->request->module().'_code')) {
            self::returnMsgAndToken('10104','验证码错误');
        }
        $user = $this->obj->table('fwy_member')->where($where)->find();unset($where);
        if ($user)
        {
            //检测是否有律师信息
            $where['uid'] = $post['uid'];
            $where['phone'] = $post['username'];
            $lawyer = $this->obj->table('fwy_lawyer')->where($where)->find();unset($where);
            if ($lawyer)
            {
                self::returnMsgAndToken('10000','修改手机号会同时修改律师端账号信息，确认修改嘛？');
            }else{
                self::returnMsgAndToken('10000');
            }
        }else{
            self::returnMsgAndToken('10011','账号不存在');
        }

    }

    /**
     * note:2,换绑新手机
     * auth:YW
     * date:date:2019/02/19
     */
    public function binding()
    {

        $post = $this->request->post();

        // 判断验证码是否正确
        if ($post['checkcode'] != session($this->request->module().'_code')) {
            self::returnMsgAndToken('10104','验证码错误');
        }
        unset($post['checkcode'],$post['token']);

        //检测主表是否有信息
        $where['id'] = $post['uid'];
        $mast = Db::table('os_user')->where($where)->find();unset($where);

        //检测是否有用户信息
        $where['uid'] = $post['uid'];
        $user = $this->obj->table('fwy_member')->where($where)->find();unset($where);
        if (!$mast || !$user)
        {
            self::returnMsgAndToken('10010','这个账号异常，无法修改手机号！');
        }
        Db::startTrans();$this->obj->startTrans();
        $mast = self::binding_mast($post);       //修改主表
        $user = self::binding_user($post);       //修改字表

        if (isset($post['is_lawyer']) && $post['is_lawyer'] == 'true')
        {
            $lawyer = self::binding_lawyer($post); //修改律师表
            if ($lawyer && $mast && $user)
            {
                Db::commit();$this->obj->commit();
                self::returnMsgAndToken('10000','手机号码修改成功，请退出重新登录！');
            }else{
                Db::rollback();$this->obj->rollback();
                self::returnMsgAndToken('10010','手机号码修改失败！');
            }
        }else{
            if ($mast && $user)
            {
                Db::commit();$this->obj->commit();
                self::returnMsgAndToken('10000','手机号码修改成功，请退出重新登录！');
            }else{
                Db::rollback();$this->obj->rollback();
                self::returnMsgAndToken('10010','手机号码修改失败！');
            }
        }
    }
    /**
     * note:3,修改主表电话号码
     * auth:YW
     * date:date:2019/02/19
     */
    private function binding_mast($data)
    {
        $where['id'] = $data['uid'];
        $_data['username'] = $data['username'];
        $_data['edit_time'] = time();
        $res = Db::table('os_user')->where($where)->update($_data);
        return $res?true:false;
    }

    /**
     * note:4,修改用户表电话号码
     * auth:YW
     * date:date:2019/02/19
     */
    private function binding_user($data)
    {
        $where['uid'] = $data['uid'];
        $_data['username'] = $data['username'];
        $_data['edit_time'] = time();
        $res = $this->obj->table('fwy_member')->where($where)->update($_data);
        return $res?true:false;
    }
    /**
     * note:5,修改律师表电话号码
     * auth:YW
     * date:date:2019/02/19
     */
    private function binding_lawyer($data)
    {
        $where['uid'] = $data['uid'];
        $_data['phone'] = $data['username'];
        $_data['edit_time'] = time();
        $res = $this->obj->table('fwy_lawyer')->where($where)->update($_data);
        return $res?true:false;
    }
    /*********************************手机换绑********************************************/

    /*********************************修改个人资料********************************************/
    /**
     * note:修改个人资料（支持所有）
     * auth:YW
     * date:date:2019/02/19
     */
    public function edit()
    {
        $post = $this->request->post();
        if ($post)
        {
            $post = paramFormart($post);
            $pks = array_keys($post);
            foreach ($pks as $key => $value)
            {
                $fields = array(
                    'nickname',
                    'email',
                    'sex',
                    'province',
                    'city',
                    'area',
                    'detail',
                );
                if (in_array($value,$fields))
                {
                    if (isset($post[$value]) && $post[$value] != '')
                    {
                        //新版本
                        $data[$value] = $post[$value];
                        if (isset($post['password']) && !empty($post['password'])) $data['password'] = md5($post['password']);
                    }
                }
            }
            $data['edit_time'] = time();
            if ($_FILES)
            {
                //保存路径
                $path = ROOT_PATH.$this->config['upload'].DS."face";
                //图片名称
                $obj = new Common();
                $this->config['field'] = 'face';
                $res = $obj->upload($path , $format = 'empty', $maxSize = '52428800', $this->config ,false);
                $data['face'] = $res;                    //头像
            }
            /*更新子表信息*/
            $where['uid'] = $post['uid'];
            $res = $this->obj->table('fwy_member')->where($where)->update($data);unset($where);

            if ($res)
            {
                self::returnMsgAndToken('10000','修改成功');
            }else{
                self::returnMsgAndToken('10010','修改失败');
            }
        }else{
            self::returnMsgAndToken('10004');
        }
    }
    /*********************************修改个人资料********************************************/

    /*********************************我的收藏********************************************/

    /*
     *note:获取我的收藏接口
     *auth:彭桃
     *date:2018/02/26
     */
    public function getcollection()
    {
        $post = $this->request->post();
        $uid['b.uid'] = $post['uid'];
        // 获取收藏商品
        $goods = $this->obj->table('fwy_goods')->alias('a')->join('fwy_collection b' ,'a.id=b.goods_id')->field('a.id,a.goods_name,a.selling_price,b.goods_type')->where($uid)->select();
        if ($goods) {
            foreach ($goods as $key => $value) {
                $goods[$key]['goods_img'] = $this->obj->table('fwy_goods_images')->where("gid='{$value['id']}'")->value('images');
                $goods[$key]['weburl'] = $this->config['weburl'];
            }
            $msg['goods'] = $goods;
        } else {
            $msg['goods'] = '';
        }

        // 获取收藏法条

        $law = $this->obj->table('fwy_content')->alias('a')->join('fwy_collection b' ,'a.id=b.law_id')->field('a.id,a.title,a.section,a.add_time,b.goods_type')->where($uid)->select();
        if ($law) {
            $msg['law'] = $law;
        } else {
            $msg['law'] = '';
        }

        // 获取收藏长文章
        $long_article = $this->obj->table('fwy_lawyer_content')->alias('a')->join('fwy_collection b ' ,'a.id=b.content_id')->field('a.id,a.title,a.click,a.thumbnail,b.goods_type')->where($uid)->select();
        if ($long_article) {
            foreach ($long_article as $k => $v) {
                $data[$k]['weburl'] = $this->config['weburl'];
            }
            $msg['long_article'] = $long_article;
        } else {
            $msg['long_article'] = '';
        }

        // 获取收藏短文章
        $short_article= $this->obj->table('fwy_lawyer_shortxt')->alias('a')->join('fwy_collection b' ,'a.id=b.article_id')->field('a.*,b.goods_type')->where($uid)->select();
        if ($short_article) {
            $msg['short_article'] = $short_article;
        } else {
            $msg['short_article'] = '';
        }

        if (empty($goods) && empty($law) && empty($long_article) && empty($short_article)) {
            self::returnMsgAndToken('10001','暂无数据');
        }else{
            self::returnMsgAndToken('10000','',$msg);
        }

    }

    /*********************************我的收藏结束********************************************/

    /*********************************删除收藏开始********************************************/
    /*
     *note:删除收藏
     *auth:彭桃
     *uid/token
     *date:2018/02/26
     */

     public function delcollect(){
        $post = $this->request->post();
        if (!isset($post['uid'])) {
            self::returnMsgAndToken('10001','参数错误');
        }
        if (!isset($post['goods_id']) && !isset($post['law_id']) && !isset($post['content_id']) && !isset($post['article_id'])) {
            self::returnMsgAndToken('10001','参数错误');
        }
        if (isset($post['goods_id'])) {
            $goods_id=explode(',',$post['goods_id']);
            $w['goods_id'] = array('in',$goods_id);
            $w['uid'] = $post['uid'];
            $res = $this->obj->table('fwy_collection')->where($w)->delete();
        }
        if (isset($post['law_id'])) {
            $law_id=explode(',',$post['law_id']);
            $wh['law_id'] = array('in',$law_id);
            $wh['uid'] = $post['uid'];
            $res = $this->obj->table('fwy_collection')->where($wh)->delete();
        }
        if (isset($post['content_id'])) {
            $content_id=explode(',',$post['content_id']);
            $whe['content_id'] = array('in',$content_id);
            $whe['uid'] = $post['uid'];
            $res = $this->obj->table('fwy_collection')->where($whe)->delete();
        }
        if (isset($post['article_id'])) {
            $article_id=explode(',',$post['article_id']);
            $where['article_id'] = array('in',$article_id);
            $where['uid'] = $post['uid'];
            $res = $this->obj->table('fwy_collection')->where($where)->delete();
        }
        if ($res) {
            self::returnMsgAndToken('10000','删除成功');
        } else {
            self::returnMsgAndToken('10004','删除失败');
        }
    }

    /*********************************删除收藏结束********************************************/


    /*********************************我的关注开始********************************************/
    /*
     *note:获取我的关注接口
     *auth:彭桃
     *uid/token
     *date:2018/02/26
     */
    public function getguanzhu(){
        $post = $this->request->post();
        $where['a.uid'] = $post['uid'];
        $res = $this->obj->table('fwy_fans')->alias('a')->join('fwy_lawyer b','a.lid = b.uid')->field('b.username,b.face,b.company,b.province_cn,b.city_cn,b.area_cn')->where($where)->select();
        if ($res) {
            foreach ($res as $key => $value) {
                $res[$key]['weburl'] = $this->config['weburl'];
            }
            self::returnMsgAndToken('10000','',$res);
        }else{
            self::returnMsgAndToken('10001','暂无数据');
        }
    }
    /*********************************我的关注结束********************************************/

    /**
     * note:重新分配律师接口
     * auth:YW
     * date:2018/09/15
     * input order_no[订单编号]，
     */
    public function getLawyer()
    {
        $post = $this->request->post();
        $where['order_no'] = $post['order_no'];
        $where['ustatus'] = '1';
        $where['status'] = '1';

        $data['ustatus'] = '0';
        $data['status'] = '1';
        $data['lid'] = '';
        $data['create'] = '1';                                    /*20181218 启用新订单状态*/
        $data['pay'] = '1';                                    /*20181218 启用新订单状态*/
        $data['deliver'] = '0';                                    /*20181218 启用新订单状态*/
        $data['receive'] = '0';                                    /*20181218 启用新订单状态*/
        $data['comment'] = '0';                                    /*20181218 启用新订单状态*/
        $data['refund'] = '0';                                    /*20181218 启用新订单状态*/
        $res = $this->obj->table('fwy_order')->where($where)->update($data);
        if ($res)
        {
            self::returnMsgAndToken('10000','等待律师接单！');
        }else{
            self::returnMsgAndToken('10001','重新寻找律师失败！');
        }

    }



}