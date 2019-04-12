<?php
namespace app\lapi\controller\v3;
use app\common\controller\Common;
use think\Request;
use think\Db;
/**
 * auth YW
 * note 主页（应用首页）
 * date 2018-08-06
 */
class Home extends Index implements Itf
{
    private $member;
    private $assist;
    /**
     * auth YW
     * note 初始化
     * date 2018-08-06
     */
    public function _initialize()
    {
        parent::_init();
        $this->member = DB::name('lawyer');
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

    }
    /**
     * note:首页
     * auth:YW
     * date:2019/01/09
     * 会员id[uid]，token[token]，起始时间[begin]可选，结束时间[end]可选，
     */
    public function show()
    {
        $post = $this->request->post();
        $data['wallet'] = self::getwallet($post)?self::getwallet($post):[];
        $data['orders'] = self::getOrderCount($post)?count(self::getOrderCount($post)):'0';
        $data['qas'] = self::getQaCount($post)?count(self::getQaCount($post)):'0';
        $data['profit'] = self::getTodayProfit($post)?self::getTodayProfit($post):'0';
        $data['notice'] = array(
            'wallet' => '钱包信息',
            'orders' => '历史订单数量',
            'qas' => '历史问答数量',
            'profit' => '今日收益',
        );

        $this->returnMsgAndToken('10000','',$data);
    }
    /**
     * note:获取余额
     * auth:YW
     * date:2019/01/09
     * 会员id[uid]，token[token]
     */
    private function getwallet($data)
    {
        $where['uid'] = $data['uid'];
        $where['status'] = '1';
        return $res = $this->member->where($where)->field('wallet,coin,days')->find();
    }
    /**
     * note:获取订单数量
     * auth:YW
     * date:2019/01/09
     * 会员id[uid]，token[token]
     */
    private function getOrderCount($data)
    {
        $where['lid'] = $data['uid'];
        $where['create'] = $where['pay'] = $where['deliver'] = $where['receive'] = 1;
        if (isset($data['begin'])) $where['add_time'] = array('egt',$data['begin']);
        if (isset($data['end'])) $where['add_time'] = array('elt',$data['begin']);
        $res = $this->obj->table('fwy_order')->field('price,total')->where($where)->select();

        return $res;
    }
    /**
     * note:获取咨询数量
     * auth:YW
     * date:2019/01/09
     * 会员id[uid]，token[token]
     */
    private function getQaCount($data)
    {
        $where['status'] = '2';
        $where['lid'] = array('like',"%{$data['uid']}%");
        if (isset($data['begin'])) $where['add_time'] = array('egt',$data['begin']);
        $res = $this->obj->table('fwy_question')->field('pay_coin')->where($where)->select();
        foreach ($res as $key => $value) {
            $lid = explode(',', trim($value['lid'], ','));
            foreach ($lid as $k => $v) {
                if ($v == $data['uid']) {
                    $res[$key] = $value;
                }
            }
        }
        return $res;
    }
    /**
     * note:今日收益[全部]
     * auth:YW
     * date:2019/01/09
     * 会员id[uid]，token[token]
     */
    private function getTodayProfit($data)
    {
        $data['begin'] = strtotime(date('Y-m-d',time()));
        $data['end'] = strtotime(date('Y-m-d',time()))+86399;
        //var_dump(date('Y-m-d H:i:s',$data['begin']),date('Y-m-d H:i:s',$data['end']));exit;
        //获取今日订单收益
        $order = self::getOrderCount($data);
        $oprofit = 0;
        foreach ($order as $key => $value)
        {
            $oprofit += $value['total'];
        }
        //获取回答收益
        $qprofit = 0;
        $qas = self::getQaCount($data);
        foreach ($qas as $key => $value)
        {
            $qprofit += $value['pay_coin'];
        }
        $qprofit = sprintf("%.2f",$qprofit/$this->assist['expcoin']);

        return $profit = sprintf("%.2f",$profit = $oprofit + $qprofit);
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
        $where['status'] = '2';
        $res = $this->obj->table('fwy_lawyer')->field('id,uid,status')->order('add_time desc')->limit(1)->where($where)->find();unset($where);
        if ($res)$data['user'] = $res;

        /**
         * note:检查正在进行的订单
         */
        $where['lid'] = $post['uid'];
        $where['status'] = '1';
        $res = $this->obj->table('fwy_order')->field('id,uid,order_no')->order('add_time desc')->limit(1)->where($where)->find();unset($where);
        if ($res)$data['order'] = $res;

        /**
         * note:版本号写入
         */
        $where['uid'] = $post['uid'];
        $where['status'] = '2';
        $temp['version'] = $post['version'];
        $temp['user_type'] = $post['user_type'];
        $temp['edit_time'] = time();
        $res = $this->obj->table('fwy_lawyer')->where($where)->update($temp);unset($where,$temp);
        $data['vsi'] = $res?array('status' => '1'):array('status' => '0');
        if ($data)
        {

            $this->returnMsgAndToken('10000','',$data);
        }else{
            $this->returnMsgAndToken('10001');
        }
    }


     /**
     * auth PT
     * note 检查是否有未接订单 (uid,token)
     * date 2019-03-13
     *
     */
    public function isorder()
    {
        $where['status'] = '0';
        $res = $this->obj->table('fwy_ortemp')->where($where)->select();
        if ($res) {
            self::returnMsgAndToken('10000','有可接订单',$res);
        }else{
            self::returnMsgAndToken('10001');
        }
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
            return self::returnMsg('10004');
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

}