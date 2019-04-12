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
        $res = $this->obj->table('fwy_order')->where($where)->update($data);
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
        $field = "id,oid,order_no,uid,lid,create,pay,deliver,receive,status,ustatus,refund,title,describe,add_time,phone";
        $res = $this->obj->table('fwy_order')->field($field)->where($where)->find();

        if ($res)
        {
            self::returnMsgAndToken('10000','',$res);
        }else{
            self::returnMsgAndToken('10011','没有找到相关数据！');
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

        $where['status'] = array('in','-4,1');
        if (isset($post['pay'])) $where['pay'] = intval($post['pay'])?$post['pay']:'0';
        if (isset($post['deliver'])) $where['deliver'] = array('in',$post['deliver']?$post['deliver']:'0');
        if (isset($post['receive'])) $where['receive'] = intval($post['receive'])?$post['receive']:'0';
        if (isset($post['comment'])) $where['comment'] = intval($post['comment'])?$post['comment']:'0';
        if (isset($post['refund'])) $where['refund'] = array('in',$post['refund']?$post['refund']:'1');
        if (isset($post['deliver'])) $where['refund'] = array('eq' ,'0');



        $where['lid'] = $post['uid'];
        $field = "id,oid,order_no,price,total,gid,uid,lid,create,pay,deliver,receive,status,ustatus,refund,title,describe,comment,add_time,goods_type_en,phone";

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
            self::returnMsgAndToken('10000','',$res);
        }else{

            self::returnMsgAndToken('10000','没有找到相关数据！');
        }
    }


    /**
     * auth YW
     * note 获取订单
     * date 2018-12-19
     */
    public function order($where = '',$field = '',$page)
    {
        $count = $this->obj->table('fwy_order')->where($where)->count();
        $res = $this->obj->table('fwy_order')->field($field)->where($where)->limit($page['page'],$page['count'])->order('add_time desc')->select();unset($where);

        if ($res)
        {
            foreach ($res as $key => $value)
            {
                //获取名称
                $where['uid'] = $value['uid'];
                $user = $this->obj->table('fwy_member')->where($where)->field('nickname,face')->find();unset($where);
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
            $res[$key]['goods_type_cn'] = $this->obj->table('fwy_goods_type')->where($where)->value('name');unset($where);
            $where['uid'] = $value['uid'];
            $where['status'] = '1';
            $user = $this->obj->table('fwy_member')->where($where)->field('nickname, face')->find();unset($where);
            $res[$key]['username'] = $user['nickname'];
            $res[$key]['face'] = $user['face'];
            $res[$key]['weburl'] = $this->config['weburl'];
            $res[$key]['port'] = 'fwy';
        }
        $data['list'] = !empty($res)?$res:[];
        if ($res != false)
        {
            self::returnMsgAndToken('10000','',$res);
        }else{
            self::returnMsgAndToken('10001','');
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
            self::returnMsgAndToken('10004');
        }
        /*限制未认证律师接单*/
        $where['status'] = '2';
        $where['uid'] = $post['uid'];
        $res = $this->obj->table('fwy_lawyer')->where($where)->find(); unset($where);
        if (!$res)
        {
            self::returnMsgAndToken('10010','请认证相关资格后再来接单吧！');
        }

        $where['order_no'] = $post['order_no'];
        $where['status'] = 0 ;
        $res = $this->obj->table("fwy_ortemp")->where($where)->find();
        if ($res)
        {
            $this->obj->startTrans();
            $ort = $this->obj->table("fwy_ortemp")->where($where)->delete();unset($where,$data);

            //更新订单正式表
            $data['status'] = '1';
            $data['lid'] = $post['uid'];    //写入律师
            $data['ustatus'] = '1';         //改为已接
            $data['begin_time'] = time();       //记录咨询开始时间
            $dt = false;//self::checkOrderType($res);
            $data['deliver'] = $dt['deliver']?$dt['deliver']:'0';

            $where['create'] = $where['pay'] = 1;
            $where['order_no'] = $post['order_no'];
            $where['status'] = '1';
            $ord = $this->obj->table("fwy_order")->where($where)->update($data);
            if ($ort && $ord)
            {
                $this->obj->commit();

                self::returnMsgAndToken('10000','接单成功！',$res);
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
     * note 律师自动抢单（注意区分快速咨询和服务类订单服务流程）
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
            self::returnMsgAndToken('10010','请认证相关资格后再来接单吧！');
        }
        if ($post)
        {
            if (isset($post['province_cn'])) $where['province_cn'] = $post['province_cn'];
            if (isset($post['city_cn'])) $where['city_cn'] = $post['city_cn'];
            if (isset($post['area_cn'])) $where['area_cn'] = $post['area_cn'];
            $where['status'] = '0';
            $res = $this->obj->table('fwy_ortemp')->where($where)->find(); unset($where);
            if ($res)
            {
                $this->obj->startTrans();
                //更新临时表
                $where['id'] = $res['id'];
                $ort = $this->obj->table("fwy_ortemp")->where($where)->delete(); unset($where,$data);
                //更新订单正式表
                $data['status'] = '1';
                $data['lid'] = $post['uid'];        //写入律师
                $data['begin_time'] = time();       //记录咨询开始时间
                $data['ustatus'] = '1';             //改为已接
                $dt = false;//self::checkOrderType($res);
                $data['deliver'] = $dt['deliver']?$dt['deliver']:'0';

                $where['create'] = $where['pay'] = 1;
                $where['ustatus'] = 0;
                $where['order_no'] = $res['order_no'];
                $ord = $this->obj->table("fwy_order")->where($where)->update($data); unset($where,$data);

                if ($ort && $ord)
                {
                    $where['uid'] = $res['uid'];
                    $user = $this->obj->table('fwy_member')->where($where)->field('face,nickname')->find();

                    $res['face'] = $user['face'];
                    $res['nickname'] = $user['nickname'];
                    $res['weburl'] = $this->config['weburl'];

                    $this->obj->commit();
                    self::returnMsgAndToken('10000','抢单成功！',$res);
                }else{
                    $this->obj->rollback();
                    self::returnMsgAndToken('10012','订单异常，请联系客服！');
                }
            }else{
                self::returnMsgAndToken('10011');
            }

        }else{
            self::returnMsgAndToken('10004');
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
            self::returnMsgAndToken('10004');
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

            self::returnMsgAndToken('10001','没有找到该订单！');
        }

    }
    /**
     * auth YW
     * note 即时咨询类订单接单时发货状态区分
     * date 2019-04-03
     * 会员id[uid]，token[token]，订单id[order_no]，
     */
    private function checkOrderType($data)
    {
        /**即时咨询类订单接单时发货状态*/
        $validate = array('quick','letter','quickdoc');
        if (in_array($data['goods_type_en'],$validate))
        {
            $data['deliver'] = '1';
        }else{
            $data['deliver'] = '0';
        }
        return $data;
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
            self::returnMsgAndToken('10004');
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
        $where['order_no'] = $post['order_no'];
        $where['lid'] = $post['uid'];
        $res = $this->obj->table('fwy_order')->where($where)->find();
        $this->obj->startTrans();
        //将父订单状态修改为完结待评价状态
        $data['deliver'] = '1';                                    /*20181218 启用新订单状态*/
        $data['receive'] = '1';                                    /*20181218 启用新订单状态*/
        $save = $this->obj->table('fwy_order')->where($where)->update($data);

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
        if(!$validate->check($res)){
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
        $where['order_no'] = $post['order_no'];
        $where['status'] = '1';
        $data['deliver'] = '1';                                    /*20181218 启用新订单状态*/
        $res = $this->edit($data,$where);
        if ($res)
        {
            self::returnMsgAndToken('10000','服务结束成功！');
        }else{
            self::returnMsgAndToken('10010','服务结束失败！');
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

    public function serch()
    {
        // TODO: Implement serch() method.
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