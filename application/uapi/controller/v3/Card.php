<?php
namespace app\uapi\controller\v3;
use think\Db;

class Card extends Index{
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
    /**
     * 卡密兑换接口（兑换点数和VIP天数）
     * 参数： uid , cardcode
     * @return \think\Response
     */
    public function exchange()
    {
        $post =$this->request->post();
        //参数验证
        $validate = new \app\uapi\validate\Card;
        if(!$validate->check($post)){
            self::returnMsgAndToken('10004',$validate->getError());
        }

        $dd['card_code'] = $post['cardcode'];
        $dd['status'] = '1';//状态（0，已销毁；1，待激活；
        $dd['activation'] = '0';//激活状态（0，待激活；1，已激活；2，已过期）
        $card = $this->obj->table('fwy_cardcode')->where($dd)->find();
        if ($card) {
            if ($card['activation'] == '1') {
                self::returnMsgAndToken('10010','提示：卡密已经被使用！');
            } else {
                $now = time();
                if ($now < $card['start_time'] || $now > $card['end_time']) {
                    $re = $this->checkCard($card);
                    self::returnMsgAndToken('10012','提示：卡密已经过期！');
                }
                $map['uid']=$post['uid'];
                if ($card['card_type'] == '1') {                    //VIP天数
                    $field = 'days';
                    $num = $card['days'];
                    $fieldchina = '天数';
                    $mid['uid'] = $post['uid'];
                    // 查询当前会员信息
                    $array = $this->obj->table('fwy_member')->where($mid)->find();
                    $cardBalance = Db::name('lawyer')->where($map)->value('days');
                    // 设置修改字段值
                    // 计算会员到期时间
                    $time = $now + ((int)$card['days'] + (int)$cardBalance) * 86400;
                    $changefield = array('isvip' => '1', 'vipdie_time' => $time);
                    // 设置会员卡到期时间和是否会员
                    $resault = $this->obj->table('fwy_member')->where($mid)->update($changefield);
                } else if ($card['card_type'] == '0') {             //法币
                    $field = 'coin';
                    $fieldchina = '法币';
                    $num=$card['coin'];
                } else {
                    self::returnMsgAndToken('10012','提示：卡密不存在或已过期，请联系客服！');
                }

                $id['id'] = $card['id'];
                $data['cid'] = $card['id'];
                $data['mid'] = $post['uid'];
                $data['add_time'] = time();
                // 开启事务
                Db::startTrans();//开启事务
                // 开启事务
                $m = $this->obj;
                $m->startTrans();//开启事务
                try{
                    // 设置会员卡为已激活
                    $fields['activation'] = '1';
                    $fields['uid'] = $post['uid'];
                    $fields['actime'] = time();
                    $fields['id'] =  $card['id'];
                    $a = $this->obj->table('fwy_cardcode')->where($id)->update($fields);

                    // 添加会员卡天数到会员表
                    $map['uid']=$post['uid'];
                    $e = Db::name('lawyer')->where($map)->setInc($field, $num);
                    // 添加钱包使用日志
                    $b = $this->addWalletlog('卡密兑换', $fieldchina, $num, $post['uid']);
                    // 添加会员卡使用记录
                    $c = $this->obj->table('fwy_cardlog')->insert($data);
                    //添加激活卡密信息到事物处理表
                    unset($data);
                    $data['cid'] = $card['id'];
                    $data['uid'] = $post['uid'];
                    $data['title'] = $card['title'];
                    $data['card_code'] = $card['card_code'];
                    $data['days'] = $card['days'];
                    $data['coin'] = $card['coin'];
                    $data['card_type'] = $card['card_type'];
                    $data['time'] = time();
                    $data['status'] = 1;
                    $d = $this->obj->table('fwy_cardtrans')->insert($data);
                    //提交事务
                    Db::commit();
                    $m->commit();
                    self::returnMsgAndToken('10000','兑换成功');
                }catch (\PDOException $e) {
                    //回滚事务
                    Db::rollback();
                    $m->rollback();
                    self::returnMsgAndToken('10014','兑换失败');
                }
            }

        } else {
            self::returnMsgAndToken('10012','提示：卡密不存在或已过期，请联系客服！');
        }
    }


    /**
     * note:卡密状态处理
     * auth:杨炜
     * date:2019/01/09
     */
    private function checkCard($data)
    {
        $where['id'] = $data['id'];
        $d['activation'] = '2';
        $res = $this->obj->table('fwy_cardcode')->where($where)->update($d);
        return $res?true:false;
    }


/*
     *note:添加钱包日志
     *$action->中午描述，$field->字段值（lawyerCoin：点数，cardBalance：天数），$num->金额数值,$mid->会员id
     *auth:彭桃
     *date:2018/01/16
     */
    private function addWalletlog($action, $field, $num, $mid)
    {
        $data['mid'] = $mid;
        $data['action'] = $action;
        $data['field'] = $field;
        $data['num'] = $num;
        $data['add_time'] = time();
        $res = $this->obj->table('fwy_walletlog')->insert($data);
        if ($res) {
            return true;
        } else {
            return false;
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
