<?php
namespace app\client\controller\v3;
use app\common\controller\Common;
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
        $laud = self::getLaud($post);
        $cc = self::getContentCount($post);
        $vc = self::getVideoCount($post);
        $wallet = self::getwallet($post);
        $orders = self::getOrderCount($post);
        $qas = self::getQaCount($post);
        $profit = self::getTodayProfit($post);


        $data['laud'] = $laud?$laud:'0';
        $data['contentcount'] = !empty($cc)?$cc:'0';
        $data['videocount'] = !empty($vc)?$vc:'0';
        $data = !empty($wallet)?array_merge($wallet,$data):'0';
        $data['orders'] = !empty($orders)?$orders:'0';
        $data['qas'] = !empty($qas)?$qas:'0';
        $data['profit'] = !empty($profit)?$profit:'0';


        $data['notice'] = array(
            'laud' => '获赞数量',
            'contentcount' => '内容发布数量，只限已审核',
            'videocount' => '视频发布数量，只限已审核',
            'wallet' => '钱包信息',
            'orders' => '历史订单数量',
            'qas' => '历史问答数量',
            'profit' => '今日收益',
        );

        $this->returnMsgAndToken('10000','获取成功',$data);
    }
    /**
     * note:获取余额
     * auth:YW
     * date:2019/01/09
     * 会员id[uid]，token[token]
     */
    private function getwallet(&$data)
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
    private function getOrderCount(&$data)
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
    private function getQaCount(&$data)
    {
        $where['status'] = '2';
        $where['lid'] = array('like',"%{$data['uid']}%");
        if (isset($data['begin'])) $where['add_time'] = array('egt',$data['begin']);
        $res = $this->obj->table('fwy_question')->field('pay_coin,lid')->where($where)->select();
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
    private function getTodayProfit(&$data)
    {

        $data['begin'] = strtotime(date('Y-m-d',time()));
        $data['end'] = strtotime(date('Y-m-d',time()))+86399;
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

    /**
     * auth YW
     * note 获赞
     * date 2019-03-21
     */
    private function getLaud(&$data)
    {
        $where['uid'] = $data['uid'];
        $res = $this->obj->table('fwy_lawyer')->field('click_num')->where($where)->find();
        return $res['click_num'];
    }

    /**
     * auth YW
     * note 文章数
     * date 2019-03-21
     */
    private function getContentCount(&$data)
    {
        $where['uid'] = $data['uid'];
        $res = $this->obj->table('fwy_lawyer_content')->where($where)->count();
        return $res;
    }

    /**
     * auth YW
     * note 视频数
     * date 2019-03-21
     */
    private function getVideoCount(&$data)
    {
        $where['uid'] = $data['uid'];
        $res = $this->obj->table('fwy_lawyer_video')->where($where)->count();
        return $res;
    }
    /**
     * auth YW
     * note 律师信息
     * date 2019-03-21
     */
    public function showall()
    {
        $post = $this->request->post();
        $where['uid'] = $post['uid'];
        $res = $this->obj->table('fwy_lawyer')->field('industryid,sex,email')->where($where)->find();unset($where);
        //获取行业信息
        $str = '';
        $data = explode(',',$res['industryid']); unset($res['industryid']);
        foreach ($data as $key => $value)
        {
            $where['id'] = $value;
            $type = $this->obj->table('fwy_goods_type')->where($where)->field('name')->find();
            $str .= $type['name'].'-';
        }
        $res['industry_cn'] = trim($str,'-');

        //获取律师事务所
        $res['lawfirm'] = $this->obj->table('fwy_lawfirm')->where('id','=','lawfirm_id')->field('name')->find();

        if ($res)
        {
            $this->returnMsgAndToken('10000','获取成功',$res);
        }else{
            $this->returnMsgAndToken('10010','获取失败');
        }

    }

    public function serch()
    {
        // TODO: Implement serch() method.
    }

    /**
     * note:获取系统消息
     * auth:YW
     * date:2019/01/09
     * 会员id[uid]，token[token]
     */
    public function getinfo()
    {
        $where['status'] = '1';
        $where['system_lawyer'] = array('neq','');
        $data['count'] = $this->obj->table('fwy_system_message')->where($where)->count();
        $data['list'] = $this->obj->table('fwy_system_message')->where($where)->select();
        if ($data['list'])
        {

            $this->returnMsgAndToken(10000,'获取成功',$data);
        }else{
            $this->returnMsgAndToken(10001,'暂无数据');
        }

    }

    /**
     * auth YW
     * note 空操作
     * date 2018-08-06
     */
    public function _empty()
    {
        $msg['code'] = '10103';
        $msg['msg'] = '操作不合法！';
        $msg['data'] = [];
        return $msg;
    }

}