<?php
namespace app\uapi\controller\v3;
use think\Db;

class Aftersale extends Index{
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
     * 申请退款
     * 参数： order_no , refund_cause , refund_payway , uid,token
     * @return \think\Response
     */
    public function askrefund(){
    	$post = $this->request->post();
        $order_no=$post['order_no'];//订单号
        $refund_cause=$post['refund_cause'];//退款原因
        $refund_payway=$post['refund_payway'];//退款方式
        $uid=$post['uid'];//用户ID
        if ($order_no && $refund_payway && $uid && $refund_cause) {
            $obj=$this->obj;
            $where['uid']=$uid;//用户ID
            $where['order_no']=$order_no;//订单号
            $where['pay']=1;//订单为已支付状态
            $result=$obj->table('fwy_order')->where($where)->find();
            //获取当前申请的订单
            if ($result) {
                $data['order_no']=$result['order_no'];
                $data['refund_payway']=$refund_payway;
                $data['refund_cause']=$refund_cause;
                $data['uid']=$uid;
                $data['payway']=$result['payway'];
                $data['ask_time']=time();
                $data['total']=$result['total'];
                $data['payway']=$result['payway'];
                if (!empty($result['lid'])) {
                    $data['lid']=$result['lid'];
                }
                $data['out_refund_no'] = get_str_guid();
               // 开启事务
                Db::startTrans();
                try{
                	//添加退款记录
                	$aftersale_res=$obj->table('fwy_aftersale')->insert($data);
               		//更新原订单信息
               		$order_res=$obj->table('fwy_order')->where($where)->setField('refund','1');
                    //提交事务
                    Db::commit();
                    self::returnMsgAndToken('10000','申请退款成功');
                }catch (\PDOException $e) {
                    //回滚事务
                    Db::rollback();
                    self::returnMsgAndToken('10014','申请退款失败');
                }
            }else{
            	self::returnMsgAndToken('10015','没有找到退款订单！');
            }
        }else{
        	self::returnMsgAndToken('10004');
        }
    }


    /*
     *note:取消申请退款
     *auth:pt
     *date:2019/01/25
     *参数：order_no , uid , token
     */
    public function cancelrefund(){
        $post = $this->request->post();
        $order_no = $post['order_no'];//订单号
        $uid = $post['uid'];//用户ID
        if ($order_no && $uid) {
            $obj=$this->obj;
            $where['uid'] = $uid;
            $where['order_no'] = $order_no;
            $where['refund'] = 1;//申请退款中
            $res = $obj->table('fwy_order')->where($where)->find();
            if (!$res) {
                self::returnMsgAndToken('10001','没有找到退款订单！');
            }
            // 开启事务
            Db::startTrans();
            try{
               //修改为（3取消退款）
                $obj->table('fwy_order')->where($where)->setField('refund','3');
                unset($where['refund']);
                //删除退款申请记录
                $obj->table('fwy_aftersale')->where($where)->delete();
                //提交事务
                Db::commit();
                self::returnMsgAndToken('10000','取消退款成功');
            }catch (\PDOException $e) {
                //回滚事务
                Db::rollback();
                self::returnMsgAndToken('10014','取消退款失败');
            }
        }else{
            self::returnMsgAndToken('10004');
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
