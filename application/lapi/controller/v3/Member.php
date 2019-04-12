<?php
namespace app\lapi\controller\v3;
use app\common\controller\Common;
use think\Db;

/**
 * auth YW
 * note 用户管理
 * date 2018-08-06
 */
class Member extends Index implements Itf
{
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

    public function del()
    {
        // TODO: Implement del() method.
    }

    public function delall()
    {
        // TODO: Implement delall() method.
    }

    public function edit()
    {
        // TODO: Implement edit() method.
    }

    public function show()
    {
        // TODO: Implement show() method.
    }

    public function showall()
    {
        // TODO: Implement showall() method.
    }

    public function serch()
    {
        // TODO: Implement serch() method.
    }
    /**
     * note:分享回调
     * auth:YW
     * date:date:2019/04/10
     * paramry uid,
     */
    public function share()
    {
        $post = $this->request->post();
    }

    /**
     * note:1,检测手机号
     * auth:YW
     * date:date:2019/02/19
     */
    public function checkPhone()
    {

        $post = $this->request->post();
        //检测是否和原账号是否一致
        $where['uid'] = $post['uid'];
        $where['phone'] = $post['username'];
        $user = $this->obj->table('fwy_lawyer')->where($where)->find();unset($where);
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

        $post['is_member'] = 'true';
        // 判断验证码是否正确
        if ($post['checkcode'] != session($this->request->module().'_code')) {
            self::returnMsgAndToken('10104','验证码错误');
        }
        unset($post['checkcode'],$post['token']);
        $s = session($this->request->module().'_code');
        //self::returnMsgAndToken('10010','这个账号异常，无法修改手机号！',$s);
        //检测主表是否有信息
        $where['id'] = $post['uid'];
        $mast = Db::table('os_user')->where($where)->find();unset($where);

        //检测是否有用户信息
        $where['uid'] = $post['uid'];
        $user = $this->obj->table('fwy_lawyer')->where($where)->find();unset($where);
        if (!$mast || !$user)
        {
            self::returnMsgAndToken('10010','这个账号异常，无法修改手机号！');
        }
        Db::startTrans();$this->obj->startTrans();
        $mast = self::binding_mast($post);       //修改主表
        $user = self::binding_lawyer($post);       //修改子表

        if ($post['is_member'] == 'true')
        {
            $lawyer = self::binding_user($post); //修改用户表
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

    /**
     * auth YW
     * note 提现
     * date 2018-12-24
     * 会员id[uid]，token[token]，提现方式[type[coin,wallet]]，提现额度[much]，到账方式[totype，[wxpay,alipay]]
     */
    public function putcash()
    {
        $post = $this->request->post();

        if ($post)
        {
            if (intval($post['much']) < 1)
            {
                self::returnMsgAndToken('10011','提现额度不能为零，无法提现！');
            }

            $obj = new Common();
            $where['uid'] = $post['uid'];
            $res = Db::table('os_lawyer')->where($where)->find();

            //根据提现方式判断金额
            $much = $post['type'] == 'coin'?intval($res[$post['type']] * $this->assist['expcoin']):$res[$post['type']];

            if (intval($post['much']) > $much || intval($much) < 1)
            {
                self::returnMsgAndToken('10011','提现额度不足，无法提现！');
            }

            //虚拟币提现到余额
            if ($post['type'] == 'coin')
            {
                $min = 100;
                if (is_numeric($post['much'])){
                    if ($post['much'] >= $min && is_int($post['much'] % 100))      //律币必须大于100点，而且为整数才能提现
                    {
                        Db::table('os_lawyer')->startTrans();

                        $data['uid'] = $post['uid'];
                        $data['total'] = sprintf("%.2d",$post['much']/$this->assist['expcoin']);
                        $this->assist['user_type'] = 'uid';

                        $data['payway'] = 'coin';
                        $setDec = $obj->wallet($data,$this->assist,'setDec');

                        $data['payway'] = 'wallet';
                        $setInc = $obj->wallet($data,$this->assist,'setInc');

                        if ($setInc && $setDec) {
                            Db::table('os_lawyer')->commit();
                            self::returnMsgAndToken('10000','提现成功！');
                        } else {
                            Db::table('os_lawyer')->rollback();
                            self::returnMsgAndToken('10013','提现失败！');
                        }
                    } else {
                        $msg['data'] = array('much' => $res[$post['type']]);
                        self::returnMsgAndToken('10010','提现失败，提现的点数不能低于'.$min,$msg);
                    }
                }else{

                    self::returnMsgAndToken('10012','提现失败，请输入有效的额度');
                }

                //余额申请提现
            }else if($post['type'] == 'wallet'){
                if (is_numeric($post['much'])) {
                    $user = $this->obj->table('fwy_lawyer')->field('bankcard,phone')->where($where)->find();
                    if (!$user['bankcard'])
                    {
                        self::returnMsgAndToken('10014','提现失败，您还没有添加您的到款账号！');
                    }
                    $min = 100;
                    Db::table('os_lawyer')->startTrans();
                    if ($res['wallet'] >= $post['much'] && $post['much'] >= $min) {  //检测申请额度不能大于钱包余额

                        $data['uid'] = $post['uid'];
                        $data['payway'] = 'wallet';
                        $data['total'] = $post['much'];
                        $this->assist['user_type'] = 'uid';
                        $setDec = $obj->wallet($data,$this->assist,'setDec');unset($data);

                        $data['service'] = sprintf("%.2f",$post['much'] * ($this->assist['expwallet']/100));
                        $amount = sprintf("%.2f",$post['much'] - $data['service']);

                        $data['serial_number'] = $this->getOnlyCode();
                        $data['totype'] = $post['totype'];
                        $data['tocard'] = $user['bankcard'];
                        $data['phone'] = $user['phone'];
                        $data['amount'] = $amount;
                        $data['uid'] = $post['uid'];
                        $data['creat_time'] = time();
                        $res = $this->obj->table('fwy_withdraw')->insert($data);
                        if ($res && $setDec) {
                            Db::table('os_lawyer')->commit();
                            self::returnMsgAndToken('10000','提现申请成功');
                        } else {
                            Db::table('os_lawyer')->rollback();
                            self::returnMsgAndToken('10013','提现申请失败');
                        }
                    } else {
                        $msg['data'] = array('much' => $res['wallet']);
                        self::returnMsgAndToken('10010','提现失败，余额不能少于'.$min,$msg);
                    }
                }else{
                    self::returnMsgAndToken('10012','提现失败，请输入有效的额度');
                }
            }else{
                self::returnMsgAndToken('10015','不支持其他提现方式');
            }

        }else{
            self::returnMsgAndToken('10004');
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
        self::returnMsg('10107','操作不合法');
    }
}