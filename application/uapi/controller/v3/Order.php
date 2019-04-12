<?php

namespace app\uapi\controller\v3;
use app\common\controller\Common;
use app\common\controller\Aliapp;
use app\common\controller\Wxapp;
use think\Db;

class Order extends Index
{
    protected $config;
    private $assist;
    private $sms_config;
    private $table = 'fwy_order';
    /**
     * note:回调地址配置（新增按照格式即可）
     * auth:YW
     * date:2018/06/11
     */
    private $notify = array(
        'alipay_pay_notify' => 'index.php/Uapi/index/index/do/Alinotify.alipay_pay_notify',                             //支付宝支付回调
        'alipay_second_pay_notify' => 'index.php/Uapi/index/index/do/Alinotify.alipay_second_pay_notify',               //支付宝二次支付回调
        'alipay_rechar_notify' => 'index.php/Uapi/index/index/do/Alinotify.alipay_rechar_notify',                       //支付宝充值回调
        'wxpay_pay_notify' => 'index.php/Uapi/index/index/do/Wxnotify.wxpay_pay_notify',                                //微信支付回调
        'wxpay_second_pay_notify' => 'index.php/Uapi/index/index/do/Wxnotify.wxpay_second_pay_notify',                  //微信二次支付回调
        'wxpay_rechar_notify' => 'index.php/Uapi/index/index/do/Wxnotify.wxpay_rechar_notify',                          //微信充值回调
        'coin_pay_notify' => '',
        'coin_second_pay_notify' => '',
        'coin_pay_rechar_notify' => '',
        'wallet_pay_notify' => '',
        'wallet_second_pay_notify' => '',
        'wallet_pay_rechar_notify' => '',
    );

    public $pay_alipay_config;
    public $pay_wxpay_config;


    /**
     * 初始化
     * 检查请求类型，数据格式等
     */
    public function _initialize()
    {
        parent::_init();
        $this->assist = $this->obj->table('fwy_assist')->find();
        $this->sms_config = $this->obj->table('fwy_sms')->find();

        $where['type'] = 'alipay';
        $res = $this->obj->table('fwy_payapi')->where($where)->find(); unset($where);
        //获取支付宝支付配置文件
        $this->pay_alipay_config = array('merchant'=>$res['merchant'],'gateway'=>$res['gateway'],'app_id'=>$res['app_id'],'rsaprivatekey'=>$res['rsaprivatekey'],'rsapublickey'=>$res['rsapublickey']);

        $where['type'] = 'wxpay';
        $res = $this->obj->table('fwy_payapi')->where($where)->find();
        //获取微信支付配置文件
        $this->pay_wxpay_config = array('merchant'=>$res['merchant'],'app_id'=>$res['app_id'],'appkey' => $res['app_key'],'appsecret'=>$res['app_secret']); unset($where);

    }
    /**
     * note:订单提交
     * auth:YW
     * date:2019/01/21
     */
    public function add()
    {
        $post = $this->request->post();
        unset($post['token']);
        /**检测金额*/
        if ($post['payway'] == 'coin' || $post['payway'] == 'wallet')
        {
            $wallet = self::checkWallet($post);

            if ($wallet == false) {
                self::returnMsgAndToken('10010','支付金额不足，请充值！');
            }
        }
        /**检测订单数量*/
        $count = self::checkOrder($post,'quick,quickdoc,letter,doc');
        if ($count == false)
        {
            self::returnMsgAndToken('10011','发布订单过多，请处理未完结的订单！');
        }

        /**保存订单*/
        $post = self::saveOrder($post);
        if ($post != false)
        {

            if ($post['payway'] == 'coin' || $post['payway'] == 'wallet')
            {
                $jdk = $this->payOrder($post);
            }else{
                $notify_url = $this->config['weburl'].$this->notify[$post['payway'].'_pay_notify'];
                $str = "pay_".$post['payway']."_config";
                $config['notify_url'] = $notify_url;
                $this->$str = array_merge($this->$str,$config);
                $jdk = $this->payOrder($post);
            }
            self::returnMsgAndToken('10000','',$jdk);
        }else{
            self::returnMsgAndToken('10012','订单提交失败！');
        }

    }
    /**
     * note:重新支付
     * auth:YW
     * date:2019/02/21
     */
    public function repay()
    {
        $post = $this->request->post();
        unset($post['token']);

        /**查找订单*/
        $where['id'] = $post['id'];
        $where['pay'] = '0';
        $where['status'] = '0';
        $res = $this->obj->table($this->table)->where($where)->find();
        if (!$res)
        {
            self::returnMsgAndToken('10001','没有找到要支付的订单！');
        }

        /**是否加急*/
        $res['urgenttotal'] = isset($res['urgent']) && $res['urgent'] == 1 && isset($res['urgenttotal']) ? $res['urgenttotal'] : 0;


        /**加载新的支付方式*/
        $res['mark'] = 'repay';
        $data['payway'] = $res['payway'] = $post['payway'];!empty($post['payway'])?$post['payway']:$res['payway'];

        /**检测金额*/
        if ($post['payway'] == 'coin' || $post['payway'] == 'wallet')
        {
            $wallet = self::checkWallet($res);
            if ($wallet == false) {
                self::returnMsgAndToken('10011','支付金额不足，请充值！');
            }
        }
        unset($res['mark']);
        /**更新数据库订单信息*/
        $upd = $this->obj->table('fwy_order')->where($where)->update($data);
        if ($upd)
        {
            if ($post['payway'] == 'coin' || $post['payway'] == 'wallet')
            {
                $jdk = $this->payOrder($res);
            }else{
                $notify_url = $this->config['weburl'].$this->notify[$res['payway'].'_pay_notify'];
                $str = "pay_".$res['payway']."_config";
                $config['notify_url'] = $notify_url;
                $this->$str = array_merge($this->$str,$config);
                $jdk = $this->payOrder($res);
            }
            self::returnMsgAndToken('10000','',$jdk);
        }else{
            self::returnMsgAndToken('10012','重新支付失败！');
        }
    }
    /**
     * note:充值
     * auth:YW
     * date:2019/02/21
     */
    public function recharge()
    {
        $post = $this->request->post();
        if (!isset($post['total']) || $post['total'] == '')
        {
            self::returnMsgAndToken('10010','请输入有效充值金额！');
        }

        //订单生成成功后，对订单进行支付操作
        $post['order_no'] = get_str_guid();
        $post['title'] = '余额充值';
        $post['body']['total'] = $post['total'];
        $post['body']['uid'] = $post['uid'];

        $data['order_no'] = $post['order_no'];
        $data['uid'] = $post['uid'];
        $data['total'] = $post['total'];
        $data['payway'] = $post['payway'];
        $data['status'] = '0';
        $data['paystatus'] = '0';
        $data['content'] = json_encode($post);
        $data['add_time'] = time();
        $res = $this->obj->table('fwy_rechargelog')->insert($data);
        if ($res)
        {
            $notify_url = $this->config['weburl'].$this->notify[$post['payway'].'_rechar_notify'];
            $str = "pay_".$post['payway']."_config";
            $config['notify_url'] = $notify_url;
            $this->$str = array_merge($this->$str,$config);
            $jdk = $this->payOrder($post);
            self::returnMsgAndToken('10000','',$jdk);
        }else{
            self::returnMsgAndToken('10011','充值提交失败！');
        }
    }
    /**
     * note:检测订单个数
     * auth:YW
     * date:2019/01/21
     */
    private function checkOrder($post, $orderSort, $num = 1)
    {
        $where['uid'] = $post['uid'];
        $where['goods_type_en'] = array('in', $orderSort);
        $where['ustatus'] = '1';
        $count = $this->obj->table($this->table)->where($where)->count();
        if ($count < $num) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * note:检测金额
     * auth:YW
     * date:2019/01/21
     */
    private function checkWallet($data)
    {
        $goods = $this->getGoods($data);
        $where['uid'] = $data['uid'];
        $wallet = Db::table('os_lawyer')->where($where)->value($data['payway']);

        return $wallet < $data['total']?false:true;
    }
    /**
     * note:订单保存
     * auth:YW
     * date:2019/01/21
     */
    private function saveOrder($post)
    {

        $goods = $this->getGoods($post);
        if (isset($post['urgent']) && $post['urgent'] == 1 && isset($post['urgenttotal'])) {
            $urgenttotal = $post['urgenttotal'];
        }else{
            $urgenttotal = 0;
        }
        $post['total'] = $goods['total'] + $urgenttotal;
        //数据封装，开始写入数据库
        $post['order_no'] = $this->getOnlyCode();
        $post['uid'] = $post['uid'];
        $post['add_time'] = time();
        $post['create'] = '1';                                     /*20181218 启用新订单状态*/
        $post['pay'] = '0';
        $post['deliver'] = '0';
        $post['receive'] = '0';
        $post['comment'] = '0';
        $post['refund'] = '0';

        $where['uid'] = $post['uid'];
        //临时表信息
        $data_['gid'] = $post['gid'];
        $data_['order_no'] = $post['order_no'];
        $data_['goods_type_en'] = $post['goods_type_en'];
        $data_['status'] = '-1';
        $data_['uid'] = $post['uid'];
        $data_['username'] = $this->obj->table('fwy_lawyer')->where($where)->value('username');;
        $data_['title'] = isset($post['title']) ? $post['title'] : '';
        $data_['describe'] = isset($post['describe']) ? $post['describe'] : '';
        $data_['add_time'] = time();

        $validate = new \app\uapi\validate\Order;
        if(!$validate->check($post)){
            self::returnMsgAndToken('10004',$validate->getError());
        }

        $this->obj->startTrans();
        $order_id = $this->obj->table($this->table)->insertGetId($post);
        $data_['order_id'] = $order_id;
        $tp = $this->obj->table('fwy_ortemp')->insert($data_);
        if ($order_id && $tp)
        {
            $this->obj->commit();
            if (isset($order['lid']) && !empty($order['lid']))
            {
                $where['uid'] = $order['lid'];
                $data['phone'] = $this->obj->table('fwy_lawyer')->where($where)->getField('phone');
                $data['tpl_id'] = '119527';
                //JhSms($data);
            }
            $post['order_id'] = $order_id;
            return $post;
        }else{
            $this->obj->rollback();
            return false;
        }


    }
    /**
     * note:支付
     * auth:YW
     * date:2019/01/21
     */
    private function payOrder($data)
    {

        if (isset($data['mark']) && $data['mark'] != 'repay')                                    //第一次支付
        {
            $goods = $this->getGoods($data);
            $data['total'] = $goods['total'];
        }

        if ($data['payway'] == 'wallet' || $data['payway'] == 'coin') {
            return $this->walletPay($data);
        } else {
            return $this->olinePay($this->payObj($data['payway']), $data);
        }
    }
    /**
     * note:余额支付
     * auth:YW
     * date:2019/01/21
     */
    private function walletPay($data)
    {
        $obj = new Common();
        Db::startTrans();
        $this->assist['user_type'] = 'uid';
        $wallet = $obj->wallet($data,$this->assist,'setDec');
        $order_id = $data['order_id'];unset($data['order_id']);
        $data['status'] = '1';
        $data['pay'] = '1';
        $this->obj->startTrans();
        $where['id'] = $order_id;
        $res = self::edit($data,$where);unset($where);
        $where['order_id'] = $order_id;
        $tp = $this->obj->table('fwy_ortemp')->where($where)->update(['status' => '0']);
        if ($wallet && $res && $tp)
        {
            Db::commit();$this->obj->commit();
            self::returnMsgAndToken('10000','订单提交成功',$data);
        }else{
            Db::rollback();$this->obj->rollback();
            self::returnMsgAndToken('10013','订单提交失败');
        }
    }
    /**
     * note:在线支付
     * auth:YW
     * date:2019/01/21
     */
    private function olinePay($obj, $data)
    {

        if ($data['payway'] == 'alipay')
        {
            return $this->alipay($type = 1, $obj, $data);
        }

        if ($data['payway'] == 'wxpay')
        {
            return $this->wxpay($type = 1, $obj , $data);
        }
    }

    /**
     * note:支付宝支付/退款
     * auth:YW
     * date:2018/06/11
     * input $payObj支付对象，$data数据， $type[1支付，0查询]，$payway 支付方式
     */
    private function alipay($type = 1, $obj, $data)
    {
        return $res = $type ? $obj->alipay_trade_app_pay($data, $this->pay_alipay_config) : $obj->alipay_trade_refund($data, $this->pay_alipay_config);
    }

    /**
     * note:微信支付/退款
     * auth:YW
     * date:2018/01/17
     * input input $payObj支付对象，$data数据, $type[1支付，0查询]，$payway 支付方式
     */
    private function wxpay($type = 1, $obj, $data, $notify_url = '')
    {

        return $res = $type ? $obj->unifiedorder($data, $this->pay_wxpay_config) : $obj->unifiedorder($data, $this->pay_wxpay_config);
    }

    /**
     * note:支付实例化
     * auth:YW
     * date:2018/03/01
     */
    private function payObj($data)
    {
        switch ($data) {
            //支付宝
            case 'alipay':
                $payObj = new Aliapp();
                break;
            //微信
            case 'wxpay':
                $payObj = new Wxapp();
                break;
        }
        return $payObj;
    }
    /**
     * note:获取商品信息
     * auth:YW
     * date:2019/01/21
     */
    private function getGoods($data)
    {

        if ($data['goods_type_en'] == 'letter')                     //获取律师设置的价格
        {
            $where['uid'] = $data['lid'];
            $data['total'] = $this->obj->table('fwy_lawyer')->field('price')->where($where)->value('price');
            if ($data['total'] == '0')
            {
                $data['total'] = $this->assist[$data['goods_type_en'].'_price'];     //获取后台统一设置价格
            }
        }else{

            $where['id'] = $data['gid'];
            $data['total'] = $this->obj->table('fwy_goods')->where($where)->value('selling_price');
            if (!$data['total']) {
               if ($data['payway'] == 'coin'){
                    if (isset($data['urgenttotal'])) {

                        $data['total'] = ($data['urgenttotal'] + $this->assist[$data['goods_type_en'].'_price']) * $this->assist['expcoin'];
                    }else{
                        $data['total'] = ($this->assist[$data['goods_type_en'].'_price']) * $this->assist['expcoin'];
                    }
                }else{
                    if (isset($data['urgenttotal'])) {
                        $data['total'] = ($data['urgenttotal'] + $this->assist[$data['goods_type_en'].'_price']);
                    }else{
                        $data['total'] = ($this->assist[$data['goods_type_en'].'_price']);
                    }
                }
            }
        }

        return $data?$data:false;
    }

    /**
     * note:编辑订单
     * auth:YW
     * date:2019/01/21
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
     * note:生成订单编号
     * auth:YW
     * date:2018/05/29
     */
    private function getOnlyCode()
    {
        $code = get_str_guid();
        $where['order_no'] = $code;
        if ($this->obj->table('fwy_order')->where($where)->count() > 0) {
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
        self::returnMsg('10107','操作不合法');
    }


}