<?php
namespace app\lapi\controller\v3;

/**
 * auth YW
 * note 订单中心
 * date 2018-08-06
 */
class Order extends Index  implements Itf {
    private $assist;
    /**
     * auth YW
     * note 初始化
     * date 2018-08-06
     */
    public function _initialize()
    {
        parent::_init();
        $this->assist = $this->obj->table('fwy_assist')->find();
    }

    public function add()
    {
        // TODO: Implement add() method.
    }
    /**
     * auth YW
     * note 删除订单(支持批量删除)
     * date 2018-12-21
     * 订单id[id]批量删除用','拼接
     */
    public function del()
    {
        $post = $this->request->post();
        $ids = trim($post['id'],',');
        $where['id'] = array('in',$ids);

        $res = $this->obj->table('fwy_order')->where($where)->update(array('status' => '-2'));
        if ($res)
        {
            self::returnMsgAndToken('10000','删除成功！');
        }else{
            self::returnMsgAndToken('10011','删除失败！');
        }
    }

    public function delall()
    {
        // TODO: Implement delall() method.
    }
    /**
     * auth YW
     * note 订单状态便捷
     * date 2019-01-08
     *
     */
    public function edit($data = '',$where = '')
    {
        $res = $this->obj->table('fwy_order')->where($where)->save($data);
        if ($res)
        {
            return true;
        }else{
            return false;
        }
    }

    /**
     * auth YW
     * note 根据id获取订单
     * date 2018-12-21
     * 订单id[id]
     */
    public function show()
    {
        $post = $this->request->post();
        $where['id'] = $post['id'];
        $field = "id,uid,lid,create,pay,deliver,receive,status,ustatus,refund,title,describe,add_time,phone";
        $res = $this->obj->table('fwy_order')->field($field)->where($where)->find();

        if ($res)
        {
            self::returnMsgAndToken('10000','获取成功！',$res);
        }else{
            self::returnMsgAndToken('10011','暂无数据！');
        }
    }
    /**
     * auth YW
     * note 获取所有订单（已处理订单）
     * date 2018-12-21
     * 会员id[uid]，token[token]，分页基数[page]，每页数量[count]，是否发货[deliver[0,1]],是否完成[receive，[0,1]]，评价[comment[0,1]],退款订单[refund[1,2,3]]
     */
    public function showall()
    {
        $post = $this->request->post();
        $page['page'] = isset($post['page']) && !empty($post['page'])?$post['page']:'1';
        $page['count'] = isset($post['count']) && !empty($post['count'])?$post['count']:5;
        $page['page'] = ($page['page']-1)*$page['count'] ;

        $where['deliver'] = array('in','0,1,2,3,4');
        $where['status'] = array('in','-4,1');
        $where['_logic'] = 'and';
        $where['create'] = $where['pay'] = $where['receive'] = $where['comment'] = array('in','0,1') ;
        $where['_logic'] = 'or';
        $where['lid'] = $post['uid'];
        $where['_logic'] = 'and';
        $field = "id,order_no,price,total,gid,uid,lid,create,pay,deliver,receive,status,ustatus,refund,title,describe,comment,add_time,goods_type_en,phone";
        $res = self::order($where,$field,$page);
        if ($res)
        {
            foreach ($res['list'] as $key => $value)
            {
                $chat = array('quick','letter');
                if (in_array($value['goods_type_en'],$chat))
                {
                    $res['list'][$key]['active'] = $chat;
                }else{
                    $res['list'][$key]['active'] =[];
                }
            }
            self::returnMsgAndToken('10000','获取成功！',$res);
        }else{

            self::returnMsgAndToken('10000','暂无数据！');
        }
    }

    public function serch()
    {
        // TODO: Implement serch() method.
    }
    /**
     * auth YW
     * note 获取订单
     * date 2018-12-19
     */
    public function order($where = '',$field = '',$page)
    {
        $count = $this->obj->table('fwy_order')->where($where)->count();
        $res = $this->obj->table('fwy_order')->field($field)->where($where)->limit($page['page'],$page['count'])->select();unset($where);

        if ($res)
        {
            foreach ($res as $key => $value)
            {
                //获取名称
                $where['uid'] = $value['uid'];
                $user = $this->obj->table('fwy_member')->where($where)->field('nickName,face')->find();unset($where);
                $res[$key]['nickname'] = $user['nickname'];
                $res[$key]['face'] = $user['face'];
                $res[$key]['weburl'] = $this->config['weburl'];
                $res[$key]['port'] = 'fwy';
            }

            return array('count' => $count,'list' => $res);
        }else{
            return false;
        }
    }
    /**
     * auth YW
     * note 未接单列表
     * date 2018-12-24
     * 会员id[uid]，token[token]，分页基数[page]，每页数量[count]，订单类型[type,[0快速咨询；1找律师]]，订单类别[goods_type_en,参数见后台]
     * 高级功能：地区
     */
    public function tporder()
    {
        $_validata = array('template');
        $post = $this->request->post();
        /*限制未认证律师*/
        $where['status'] = '2';
        $where['uid'] = $post['uid'];
        $res = $this->obj->table('fwy_lawyer')->where($where)->find(); unset($where);
        if (!$res)
        {
            self::returnMsgAndToken('10011','请认证相关资格后再来接单吧！');
        }
        $page['page'] = isset($post['page']) && !empty($post['page'])?$post['page']:'1';
        $page['count'] = isset($post['count']) && !empty($post['count'])?$post['count']:5;
        $page['page'] = ($page['page']-1)*$page['count'] ;

        if (isset($post['type'])) $where['type'] = $post['type'];
        if (isset($post['goods_type_en'])) $where['name_en'] = $post['goods_type_en'];     //根据分类显示要接的订单
        if (isset($post['pro_cn'])) $where['pro_cn'] = $post['pro_cn'];
        if (isset($post['city_cn'])) $where['city_cn'] = $post['city_cn'];
        if (isset($post['area_cn'])) $where['area_cn'] = $post['area_cn'];
        $where['status'] = '0';
        $where['goods_type_en'] = array('not in',$_validata);
        $field = "*";
        $data['count'] = $this->obj->table('fwy_ortemp')->where($where)->count();
        $res = $this->obj->table('fwy_ortemp')->field($field)->where($where)->limit($page['page'],$page['count'])->select();
        unset($where);
        foreach ($res as $key => $value)
        {
            $where['name_en'] = array('like',"%{$value['goods_type_en']}%");
            $res[$key]['goods_type_cn'] = $this->obj->table('fwy_goods_type')->where($where)->getField('name');unset($where);
            $where['uid'] = $value['uid'];
            $where['status'] = '1';
            $user = $this->obj->table('fwy_member')->where($where)->field('nickName,headPortrait as face')->find();unset($where);
            $res[$key]['username'] = $user['nickname'];
            $res[$key]['face'] = $user['face'];
            $res[$key]['weburl'] = $this->config['weburl'];
            $res[$key]['port'] = 'fwy';
        }
        $data['list'] = !empty($res)?$res:[];
        if ($res != false)
        {
            self::returnMsgAndToken('10000','获取成功！',$res);
        }else{
            self::returnMsgAndToken('10011','暂无数据！');
        }
    }
    /**
     * auth YW
     * note 律师抢单
     * date 2018-12-24
     * 会员id[uid]，token[token]，订单id[order_no]
     */
    public function receipt()
    {
        $post = $this->request->post();
        if (!isset($post['order_no']) || empty($post['order_no']))
        {
            self::returnMsgAndToken('10004','缺少参数！');
        }
        /*限制未认证律师接单*/
        $where['status'] = '2';
        $where['uid'] = $post['uid'];
        $res = $this->obj->table('fwy_lawyer')->where($where)->find(); unset($where);
        if (!$res)
        {
            self::returnMsgAndToken('10011','请认证相关资格后再来接单吧！');
        }

        $where['order_no'] = $post['order_no'];
        $where['status'] = 0 ;
        $res = $this->obj->table("fwy_ortemp")->where($where)->find();

        if ($res)
        {
            $this->obj->startTrans();
            $ort = $this->obj->table("fwy_ortemp")->where($where)->delete();unset($where,$data);

            //更新订单正式表
            $data['lid'] = $post['uid'];    //写入律师
            $data['ustatus'] = '1';         //改为已接
            $data['begin_time'] = time();       //记录咨询开始时间

            $where['create'] = $where['pay'] = 1;
            $where['order_no'] = $post['order_no'];
            $where['status'] = '1';
            $ord = $this->obj->table("fwy_order")->where($where)->save($data);
            if ($ort && $ord)
            {
                $this->obj->commit();

                self::returnMsgAndToken('10011','接单成功！');
            }else{
                $this->obj->rollback();
                self::returnMsgAndToken('10011','这个订单可能已经被接了，换别的试试！');
            }
        }else{
            self::returnMsgAndToken('10012','来晚了,换别的试试吧！');
        }
    }
    /**
     * auth YW
     * note 律师自动抢单
     * date 2019-01-11
     * 会员id[uid]，token[token]，订单id[order_no]
     */
    public function autoreceipt()
    {
        set_time_limit(0);
        $post = $this->request->post();
        /*限制未认证律师接单*/
        $where['status'] = '2';
        $where['uid'] = $post['uid'];
        $res = $this->obj->table('fwy_lawyer')->where($where)->find(); unset($where);
        if (!$res)
        {
            self::returnMsgAndToken('10011','请认证相关资格后再来接单吧！');
        }
        if ($post)
        {
            if (isset($post['pro_cn'])) $where['pro_cn'] = $post['pro_cn'];
            if (isset($post['city_cn'])) $where['city_cn'] = $post['city_cn'];
            if (isset($post['area_cn'])) $where['area_cn'] = $post['area_cn'];
            $where['status'] = '0';
            $res = $this->obj->table('fwy_ortemp')->where($where)->find(); unset($where);
            if ($res)
            {
                $this->obj->startTrans();
                //更新临时表
                $data['status'] = '1';
                $where['id'] = $res['id'];
                $ort = $this->obj->table("fwy_ortemp")->where($where)->save($data); unset($where,$data);
                //更新订单正式表
                $data['lid'] = $post['uid'];    //写入律师
                $data['ustatus'] = '1';         //改为已接
                $data['begin_time'] = time();       //记录咨询开始时间

                $where['create'] = $where['pay'] = 1;
                $where['ustatus'] = 0;
                $where['order_no'] = $res['order_no'];
                $ord = $this->obj->table("fwy_order")->where($where)->save($data);

                if ($ort && $ord)
                {
                    $this->obj->commit();
                    self::returnMsgAndToken('10000','抢单成功！');
                }else{
                    $this->obj->rollback();
                    self::returnMsgAndToken('10012','下手慢了一点，再试试吧！');
                }
            }else{
                self::returnMsgAndToken('10011','没有订单，换个条件再试试吧！');
            }


        }else{

            self::returnMsgAndToken('10004','缺少参数！');
        }

    }
    /**
     * auth YW
     * note 律师拒绝接单
     * date 2018-12-24
     * 会员id[uid]，token[token]，订单id[order_no]，
     */
    public function refuse()
    {
        $post = $this->request->post();
        if (!isset($post['order_no']) || empty($post['order_no']))
        {
            self::returnMsgAndToken('10004','缺少参数！');
        }

        $where['status'] = '0';
        $where['order_no'] = $post['order_no'];
        $res = $this->obj->table('fwy_ortemp')->where($where)->find(); unset($where);
        if ($res)
        {
            $this->obj->startTrans();
            $where['status'] = '1';
            $where['create'] = '1';
            $where['pay'] = '1';
            $where['ustatus'] = '0';
            $where['order_no'] = $res['order_no'];

            $data['ustatus'] = '3';         //新版
            $data['begintime'] = time();
            $data['lid'] = $post['uid'];
            $save = $this->obj->table('fwy_order')->where($where)->save($data); unset($where);
            if ($save)
            {
                //退款
                $where['ustatus'] = '3';
                $where['order_no'] = $res['order_no'];
                $res = $this->obj->table('fwy_order')->field('uid,lid,payway,price,total')->where($where)->find();
                if ($res && $this->wallet($res,$this->assist) && $this->obj->table('fwy_ortemp')->where($where)->delete())
                {
                    $this->obj->commit();
                    self::returnMsgAndToken('10000','拒绝成功，退款成功！');
                }else{
                    $this->obj->rollback();
                    self::returnMsgAndToken('10011','拒绝成功，退款失败！');
                }
            }else{

                self::returnMsgAndToken('10010','拒绝失败！');
            }
        }else{

            self::returnMsgAndToken('10010','没有找到该订单！');
        }

    }
    /**
     * auth YW
     * note 找律师(用户端)
     * date 2018-12-24
     * 会员id[uid]，token[token]，订单id[order_no]
     */
    public function oto()
    {
        $post = $this->request->post();
        if (!isset($post['order_no']) || empty($post['order_no']))
        {
            self::returnMsgAndToken('10004','缺少参数！');
        }
        /*限制未认证律师接单*/
        $where['status'] = '2';
        $where['uid'] = $post['uid'];
        $res = $this->obj->table('fwy_lawyer')->where($where)->find(); unset($where);
        //if ($res)
        if (!$res)
        {
            self::returnMsgAndToken('10011','该律师认证信息已失效，换一个律师吧！');
        }

        $where['order_no'] = $post['order_no'];
        $where['status'] = 0 ;
        $where['_string'] = "goods_type_en = 'letter' or goods_type_en = 'doc'";
        $res = $this->obj->table("fwy_ortemp")->where($where)->find();

        if ($res)
        {
            $this->obj->startTrans();
            //更新临时表
            $data['status'] = '1';
            $ort = $this->obj->table("fwy_ortemp")->where($where)->save($data);unset($where,$data);

            //更新订单正式表
            $data['lid'] = $res['uid'];         //写入律师
            $data['ustatus'] = '1';             //改为已接
            $data['begintime'] = time();        //记录咨询开始时间

            $where['create'] = $where['pay'] = 1;
            $where['ustatus'] = 0;
            $where['order_no'] = $post['order_no'];
            $where['status'] = '1';
            $ord = $this->obj->table("fwy_order")->where($where)->save($data);

            //获取用户信息

            if ($ort && $ord)
            {
                $this->obj->commit();
                self::returnMsgAndToken('10000','接单成功！');
            }else{
                $this->obj->rollback();
                self::returnMsgAndToken('10011','这个订单可能已经被接了，换别的试试！');
            }
        }else{
            self::returnMsgAndToken('10012','来晚了,换别的试试吧！');
        }
    }



    /******************************************************法律培训，律师函，法务服务版块流程变更*********************************************************/

    /**
     * note:律师联系用户
     * auth:YW
     * date:2018/09/19
     * input uid[用户id]，order_no[订单编号]
     */
    public function contactUser()
    {
        $post = $this->request->post();
        if (empty($post['order_no']))
        {
            self::returnMsgAndToken('10004','缺少参数！');
        }

        $where['order_no'] = $post['order_no'];
        $where['ustatus'] = '1';
        $data['deliver'] = '2';                                    /*20181218 启用新订单状态*/
        $res = $this->edit($data,$where);
        if ($res)
        {
            self::returnMsgAndToken('10000','电话联系成功！');
        }else{
            self::returnMsgAndToken('10010','电话联系失败！');
        }
    }

    /**
     * note:生成第二订单并发送给用户
     * auth:YW
     * date:2018/09/17
     * input uid[用户id]，order_no[订单编号]，total[二次议价金额]
     */
    public function secondOrder()
    {

        $post = $this->request->post();
        $where['id'] = $post['id'];
        $where['lid'] = $post['uid'];
        $res = $this->obj->table('fwy_order')->where($where)->find();
        $this->obj->startTrans();
        //将父订单状态修改为完结待评价状态
        $data['deliver'] = '1';                                    /*20181218 启用新订单状态*/
        $data['receive'] = '1';                                    /*20181218 启用新订单状态*/
        $save = $this->obj->table('fwy_order')->where($where)->save($data);

        //重新给用户生成一个订单自定义金额
        $res['oid'] = $res['id'];
        $res['payway'] = 'wallet';
        $res['order_no'] = $this->getOnlyCode();
        $res['total'] = $post['total'];
        $res['price'] = $post['total'];
        $res['status'] = '0';
        $res['add_time'] = time();
        $res['pay'] = '0';
        $res['deliver'] = '0';
        $res['receive'] = '0';
        $res['comment'] = '0';
        $res['refund'] = '0';
        unset($res['id']);


        $validate = new \app\uapi\validate\Order;
        if(!$validate->check($post)){
            return self::returnMsgAndToken('10004',$validate->getError());
        }

        $res = $this->obj->table('fwy_order')->insert($res);
        if ($res && $save)
        {
            $this->obj->commit();
            self::returnMsgAndToken('10000','议价订单发送成功！');
        }else{

            $this->obj->rollback();
            self::returnMsgAndToken('10010','议价订单发送失败！');
        }
    }
    /**
     * note:律师对服务进行结束
     * auth:YW
     * date:2018/09/17
     * input uid[用户id]，order_no[订单编号]
     */
    public function endOrder()
    {
        $post = $this->request->post();
        $where['id'] = $post['id'];
        $where['status'] = '1';
        $data['deliver'] = '1';                                    /*20181218 启用新订单状态*/
        $res = $this->edit($data,$where);
        if ($res)
        {
            self::returnMsgAndToken('10000','服务结束成功！');
        }else{
            self::returnMsgAndToken('10011','服务结束失败！');
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
        if ($this->obj->table('fwy_order')->where($map)->count() > 0) {
            $this->getOnlyCode();
        } else {
            return $code;
        }
    }
    /**
     * auth YW
     * note 空操作
     * date 2018-08-06
     */
    public function _empty(){
        $msg['code'] = '10103';
        $msg['msg'] = '操作不合法！';
        $msg['data'] = [];
        return $msg;
    }

}