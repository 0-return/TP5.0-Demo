<?php
namespace app\admin\controller;

use app\admin\common\controller\Init;
use think\Db;

class Orderdata extends Init
{

    private $industry;
    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'] . 'order';
        $this->industry = $this->config['prefix'] . 'goods_type';
    }


    /**
     * note:订单高级统计
     * auth:YW
     * date:2018/09/17
     *
     *
     */
    public function order()
    {
        $get = $this->request->get();
        $db = $this->obj;
        # 必须用上月份最初  算得才是一个月后  不然会按现在的时候算
        date_default_timezone_set('Asia/Shanghai');
        $first_day_of_month = date('Y-m', time());
        $t = strtotime($first_day_of_month);
        for ($i = 0; $i < 12; $i++) { //统计12个月
            if ($i == 0) {
                $after_time = time();
            } else {
                $after_time = strtotime(date("Y-m-01 00:00:00", strtotime(1 - $i . ' month', $t)));
            }
            $before_time = strtotime(date("Y-m-01 00:00:00", strtotime(-$i . ' month', $t)));

            $m['add_time']  = array('between', array($before_time, $after_time));
            // $m['status'] = array('neq', '-1');
            $m['pay'] = array('eq', '1');
            $m['refund'] = array('neq', '2');
            $m['goods_type_en'] = array(array('eq', 'quick'), array('eq', 'letter'), array('eq', 'doc'), 'or'); //咨询
            $l_quick = $db->table($this->table)->where($m)->count();
            $sales_qu[$i] = (int)$l_quick;
            $m['goods_type_en'] =  'shop'; //法律培训
            $l_shop = $db->table($this->table)->where($m)->count();
            $sales_sh[$i] = (int)$l_shop;
            $m['goods_type_en'] = array(array('eq', 'service'), array('eq', 'itext'), array('eq', 'template'), 'or');  //法务服务
            $l_service = $db->table($this->table)->where($m)->count();
            $sales_se[$i] = (int)$l_service;
            $m['goods_type_en'] = 'question'; //留言
            $l_que = $db->table($this->table)->where($m)->count();
            $sales_que[$i] = (int)$l_que;

            $month[$i] = date('y-m', $before_time);
        }
        // var_dump($db->table($this->table)->getLastSql());exit;
        //快速咨询分类@1
        $iid['iid'] = $db->table($this->industry)->where(['name_en' => ['like','%quick%']])->value('id');
        $quick_type = $db->table($this->industry)->Field('id,name')->where($iid)->select();
        //文书服务分类@1
        $iid['iid'] = $db->table($this->industry)->where(['name_en' => ['like','%doc%']])->value('id');
        $doc_type = $db->table($this->industry)->Field('id,name')->where($iid)->select();
        //模板分类@1
        $iid['iid'] = $db->table($this->industry)->where(['name_en' => ['like','%template%']])->value('id');
        $template_type = $db->table($this->industry)->Field('id,name')->where($iid)->select();
        //问答分类@1
        $iid['iid'] = $db->table($this->industry)->where(['name_en' => ['like','%question%']])->value('id');
        $question_type = $db->table($this->industry)->Field('id,name')->where($iid)->select();

        // $w['status'] = array('neq', '-1');
        $w['pay'] = array('eq', '1');
        $w['refund'] = array('neq', '2');
        //快速咨询订单分类占比图@2
        $w['goods_type_en'] = "quick";
        $count_qi = $db->table($this->table)->where($w)->count();
        //文书服务订单分类占比图@2
        $w['goods_type_en'] = "doc";
        $count_do = $db->table($this->table)->where($w)->count();
        //模板订单分类占比图@2
        $w['goods_type_en'] = "template";
        $count_te = $db->table($this->table)->where($w)->count();
        //问答订单分类占比图@2
        $w['goods_type_en'] = "question";
        $count_qu = $db->table($this->table)->where($w)->count();

        // $tt['status'] =  array('neq', '-1');
        $tt['pay'] = array('eq', '1');
        $tt['refund'] = array('neq', '2');
        //快速咨询订单分类占比图@3
        $j = 0;
        $quicknum = count($quick_type);
        for ($i = 0; $i < $quicknum; $i++) {
            $tt['gid'] = $quick_type[$i]['id'];
            $tt['goods_type_en'] = array(array('eq', 'quick'), array('eq', 'letter'), 'or');

            $tqu = $db->table($this->table)->where($tt)->count();
            if ($tqu == '0') {
                $per = 0;
            }else{
                $per = (int)round($tqu / $count_qi * 100);
            }
            $count_qi_t[$i][] = $quick_type[$i]['name'];
            $count_qi_t[$i][] = (int)$per;
            if ($per == 0) {
                $j++;
            }
        }
        if ($j == $quicknum) {
            $count_qi_t[$j][] = '暂无数据';
            $count_qi_t[$j][] = 1;
        }

        //文书服务订单分类占比图@3
        $j = 0;
        $doc_typenum = count($doc_type);
        for ($i = 0; $i < $doc_typenum; $i++) {
            $tt['gid'] = $doc_type[$i]['id'];
            $tt['goods_type_en'] = "doc";
            $tdo = $db->table($this->table)->where($tt)->count();
            if ($tdo == '0') {
                $per = 0;
            }else{
                $per = (int)round($tdo / $count_do * 100);
            }
            $count_do_t[$i][] = $doc_type[$i]['name'];
            $count_do_t[$i][] = (int)$per;
            if ($per == 0) {
                $j++;
            }
        }
        if ($j == $doc_typenum) {
            $count_do_t[$j][] = '暂无数据';
            $count_do_t[$j][] = 1;
        }

        //模板订单分类占比图@3
        $j = 0;
        $template_typenum = count($template_type);
        for ($i = 0; $i < $template_typenum; $i++) {
            $tt['gid'] = $template_type[$i]['id'];
            $tt['goods_type_en'] = "template";
            $tte = $db->table($this->table)->where($tt)->count();
            if ($tte == '0') {
                $per = 0;
            }else{
                $per = (int)round($tte / $count_te * 100);
            }
            $count_te_t[$i][] = $template_type[$i]['name'];
            $count_te_t[$i][] = (int)$per;
            if ($per == 0) {
                $j++;
            }
        }
        if ($j == $template_typenum) {
            $count_te_t[$j][] = '暂无数据';
            $count_te_t[$j][] = 1;
        }

        //问答订单分类占比图@3
        $j = 0;
        $question_typenum = count($question_type);
        for ($i = 0; $i < $question_typenum; $i++) {
            $tt['gid'] = $question_type[$i]['id'];
            $tt['goods_type_en'] = "question";
            $tqe = $db->table($this->table)->where($tt)->count();
            if ($tqe == '0') {
                $per = 0;
            }else{
                $per = (int)round($tqe / $count_qu * 100);
            }
            $count_qu_t[$i][] = $question_type[$i]['name'];
            $count_qu_t[$i][] = (int)$per;
            if ($per == 0) {
                $j++;
            }
        }
        if ($j == $question_typenum) {
            $count_qu_t[$j][] = '暂无数据';
            $count_qu_t[$j][] = 1;
        }

        //订单分类占比图
        $type = $db->query("SELECT
			count(*) AS num,
			count(if(goods_type_en like '%quick%',true,null)) AS 快速咨询,
			count(if(goods_type_en like '%service%',true,null)) AS 法务服务,
			count(if(goods_type_en like '%itext%',true,null)) AS 律师函,
			count(if(goods_type_en like '%doc%',true,null)) AS 文书服务,
			count(if(goods_type_en like '%letter%',true,null)) AS 图文咨询,
			count(if(goods_type_en like '%question%',true,null)) AS 快速问答,
			count(if(goods_type_en like '%shop%',true,null)) AS 服务商城,
			count(if(goods_type_en like '%template%',true,null)) AS 模板商城
			FROM ".$this->table);
        $i = 0;
        $sum_m = 0;
        foreach ($type[0] as $k => $v) {
            if ($i == 0) {
                $sum_m = $v;
            } else {
                $ty_p[$i - 1][] = $k;
                $ty_p[$i - 1][] = (int)round($v / $sum_m * 100);
            }
            $i++;
        }

        //订单状态占比图
        $state = $db->query("SELECT
			count(*) AS num,
			count(if(refund=2,true,null)) AS 已退款,
			count(if(refund=1,true,null)) AS 待退款,
			count(if(pay=0,true,null)) AS 未支付,
			count(if(pay=1,true,null)) AS 已支付,
			count(if(comment=0,true,null)) AS 待评价,
			count(if(comment=1,true,null)) AS 已评价
			FROM ".$this->table);
        $i = 0;
        $sum_m = 0;
        foreach ($state[0] as $k => $v) {
            if ($i == 0) {
                $sum_m = $v;
            } else {
                $st_p[$i - 1][] = $k;
                $st_p[$i - 1][] = (int)round($v / $sum_m * 100);
            }
            $i++;
        }
        // echo "<pre>";
        // var_dump($count_do_t);exit;
        $this->assign('month', json_encode(array_reverse($month))); //统计月份
        $this->assign('sales_qu', json_encode(array_reverse($sales_qu))); //咨询
        $this->assign('sales_sh', json_encode(array_reverse($sales_sh))); //法律培训
        $this->assign('sales_se', json_encode(array_reverse($sales_se))); //法务服务
        $this->assign('sales_que', json_encode(array_reverse($sales_que))); //留言
        $this->assign('count_qi_t', json_encode($count_qi_t)); //快速占比
        $this->assign('count_do_t', json_encode($count_do_t)); //文书占比
        $this->assign('count_te_t', json_encode($count_te_t)); //模板占比
        $this->assign('count_qu_t', json_encode($count_qu_t)); //问答占比
        $this->assign('st_p', json_encode($st_p)); //订单状态占比
        $this->assign('ty_p', json_encode($ty_p)); //订单分类占比
        return view('index');
    }
}
