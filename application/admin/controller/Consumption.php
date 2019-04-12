<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Db;

class Consumption extends Init
{

    private $payway = array(
        'alipay' => '支付宝',
        'wxpay' => '微信支付',
        'coin' => '法币支付',
        'wallet' => '余额支付',
    );
      private $status_cn = array(
        100000 => '<span class="label label-default radius">下单成功</span>',
        110000 => '<span class="label label-primary radius">支付成功</span>',
        111000 => '<span class="label label-secondary radius">发货成功</span>',
        112000 => '<span class="label label-warning radius">服务中</span>',
        113000 => '<span class="label label-success radius">服务中</span>',
        111100 => '<span class="label label-danger radius">待评价</span>',
        111110 => '<span class="label label-danger radius">评论成功</span>',
        111111 => '<span class="label label-danger radius">申请退款</span>',
        111112 => '<span class="label label-danger radius">退款成功</span>',
        -100000 => '<span class="label label-danger radius">订单取消</span>',
        -110000 => '<span class="label label-danger radius">订单取消</span>',
    );

    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'] . 'member';
        $this->lawyertable = $this->config['prefix'] . 'lawyer';
    }

    public function index()
    {
        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $map['status'] = array('gt','-1');
        $where['where'] = $map;
        // var_dump($where);exit;
        $this->_list('',$where);

        return view();
    }

    private function _filter(&$map)
    {
        if (!empty($this->request->get('province_cn'))) {
            $map['province_cn'] = $this->request->get('province_cn');
        }
        if (!empty($this->request->get('city_cn'))) {
            $map['city_cn'] = $this->request->get('city_cn');
        }
        if (!empty($this->request->get('area_cn'))) {
            $map['area_cn'] = $this->request->get('area_cn');
        }
        $this->checkSearch($map);
    }


    public function _after_list(&$list){
        $get = $this->request->get();
        if (empty($get['province_cn'])) {
            $get['province_cn'] = '';
        }
        if (empty($get['city_cn'])) {
            $get['city_cn'] = '';
        }
        if (empty($get['area_cn'])) {
            $get['area_cn'] = '';
        }
        $list = $this->memberHistoryOrderFormatByArea($this->obj, $list, $get);
        //导出
        if (empty($get['do'])) {
            $get['do'] = '';
        }
        if ($get['do'] == 'export') {

            return $this->exportDate('area', $list, 'AreaByinfo');
        }
        $this->assign('condition',$get);
    }



    /*
     *note:地区用户列表数据格式化
     *auth:PT
     *date:2018/05/16
     */
    public function memberHistoryOrderFormatByArea($obj, $userInfo, $post)
    {

        foreach ($userInfo as $key => $value) {

            //获取用户订单信息
            $where['uid'] = $value['uid'];
            $where['pay'] = '1';
            $where['refund'] = array('neq','2');
            $userOrder = $obj->table($this->config['prefix'] .'order')->field('uid,total,payway')->where($where)->select();

            $wallet = 0;
            foreach ($userOrder as $ky => $vl) {

                if ($value['uid'] == $vl['uid']) {
                    if ($vl['payway'] == 'coin') {
                        $wallet += moneytomoney($value['total'], $this->sysconfig['expcoin']);
                    } else {
                        $wallet += floatval($vl['total']);
                    }
                }
            }

            //金额
            $userInfo[$key]['total'] = empty($wallet) ? '00' : $wallet;
            //总订单数
            $userInfo[$key]['ordercount'] = count($userOrder);
            //获取律师服务总记录
            $userInfo[$key]['lawyercount'] = $obj->table($this->config['prefix'] .'memlawyer')->where('uid = ' . $value['uid'])->count();
            $userInfo[$key]['order'] = $userOrder;
        }
        return $userInfo;
    }


    /*
     *note:用户历史订单
     *auth:PT
     *date:2018/05/15
     */
    public function memberHistoryOrder()
    {
        $get = $this->request->get();
        $obj = $this->obj;
        // $where['uid'] = $get['uid'];
        $where['pay'] = '1';
        $where['refund'] = array('neq' , '2');

        $begin_time = strtotime(empty($get['begin_time']) ? '1970-01-01 08:00:00' : $get['begin_time']);
        $end_time = strtotime(empty($get['end_time']) ? date('Y-m-d', time()) : $get['end_time']) + 86399;

        $where['add_time'] = array('between', "{$begin_time},{$end_time}");

        if (!empty($get['reunite'])) {
            $where['order_no|phone'] = array('like', "%{$get['reunite']}%");
        }

        $count = $obj->table($this->config['prefix'] . 'order')->where($where)->count();

        $res = $obj->table($this->config['prefix'] . 'order')->where($where)->select();
        // echo "<pre>";
        //  var_dump($res);exit;
        foreach ($res as $key => $value) {
            $res[$key]['nickname'] = $obj->table($this->table)->where('uid = ' . $value['uid'])->value('nickname');
            $res[$key]['phone'] = $obj->table($this->table)->where('uid = ' . $value['uid'])->value('phone');
            if (!empty($value['lid'])) {
                $res[$key]['lawyer'] = $obj->table($this->lawyertable)->where('uid = ' . $value['lid'])->value('username');
            }else{
                $res[$key]['lawyer'] = '无';
            }
            $sta = $value['create'].$value['pay'].$value['deliver'].$value['receive'].$value['comment'].$value['refund'];
            $res[$key]['status_cn'] = $this->status_cn[$sta];
            $res[$key]['total'] = floatval($value['total']);
            $res[$key]['payway_cn'] = $this->payway[$value['payway']];
            if ($value['urgent'] == '1') {
                $res[$key]['urgenttotal'] = $this->moneytomoney($value['urgenttotal'], $this->sysconfig['expcoin']);
            }
        }

        //导出
        if (empty($get['do'])) {
            $get['do'] = '';
        }
        if ($get['do'] == 'export') {

            return $this->exportDate('order', $res, 'UserByorder');
        } else {

            //重新变更时间格式条件返回给前端
            $get['begin_time'] = date('Y-m-d', $begin_time);
            $get['end_time'] = date('Y-m-d', $end_time);

            $this->assign('uid', $get['uid']);
            $this->assign('count', $count);
            $this->assign('condition', $get);
            $this->assign('list', $res);
        }

        return view('show');
    }


    /*
     *note:用户历史服务律师
     *auth:PT
     *date:2018/05/15
     */
    public function memberHistoryLawyer()
    {
        $get = $this->request->get();
        $where['uid'] = $get['uid'];
        $obj = $this->obj;

        $begin_time = strtotime(empty($get['begin_time']) ? '1970-01-01 08:00:00' : $get['begin_time']);
        $end_time = strtotime(empty($get['end_time']) ? date('Y-m-d', time()) : $get['end_time']) + 86399;

        $where['add_time'] = array('between', "{$begin_time},{$end_time}");

        if (!empty($get['reunite'])) {
            $where['username|phone'] = array('like', "%{$get['reunite']}%");
        }

        //获取用户vip资料
        $userInfo = $obj->table($this->table)->field('vipdie_time')->where($where)->find();
        if ($userInfo['vipdie_time'] > time()) {
            $userInfo['vipdie_time'] = floor(ceil(($userInfo['vipdie_time']) - time()) / (60 * 60 * 24));
        } else {
            $userInfo['vipdie_time'] = -1;
        }

        $count = $obj->table($this->config['prefix'] . 'fwy_memlawyer')->where($where)->count();
        $num = '30';
        $page = new Page($count, $num, $get);
        $showpage = $page->show();

        //导出
        if ($get['do'] == 'export') {
            $res = $obj->table($this->config['prefix'] . 'fwy_memlawyer')->where($where)->limit($page->firstRow, $page->listRows)->select();
            foreach ($res as $key => $value) {
                $res[$key]['days'] = floor((($value['end_time'] - $value['begin_time']) - 1) / (60 * 60 * 23));
            }
            $this->exportDate('lawyer', $res, 'UserBylawyer');
        } else {

            $res = $obj->table($this->config['prefix'] . 'fwy_memlawyer')->where($where)->limit($page->firstRow, $page->listRows)->select();
            foreach ($res as $key => $value) {
                //$days = (ceil($value['end_time'] - $value['begin_time'])-1)/(60*60*23);
                $res[$key]['days'] = floor((($value['end_time'] - $value['begin_time']) - 1) / (60 * 60 * 23));
            }
            //重新变更时间格式条件返回给前端
            $get['begin_time'] = date('Y-m-d', $begin_time);
            $get['end_time'] = date('Y-m-d', $end_time);

            $this->assign('uid', $get['uid']);
            $this->assign('count', $count);
            $this->assign('list', $res);
            $this->assign('userlist', $userInfo);
            $this->assign('condition', $get);
        }

        return view('lawyer');
    }



        /*
        *note:金额转换
        *auth:YW
        *$type true[=true,其他币种转换为现金；=false,金额转为为其他币种]，$exp[比例]，$money[要转换的数值]
        *date:2018/05/14
        */
        function moneytomoney($number,$exp,$type = true)
        {
            if ($type)
            {
                $money = $number/$exp;
            }else{
                $money = $number*$exp;
            }
            return $money;
        }


         /**
     * note:数据导出
     * auth:YW
     * date:2018/05/15
     * param $type[导出数据源类型][area,order,lawyer],$data[数据],$title[文档标题]
     */
    public function exportDate($type, $data, $title = '')
    {

        Vendor("Excel.PHPExcel");
        Vendor("Excel.Writer.Excel2007");
        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
        $objActSheet = $objPHPExcel->getActiveSheet();
        switch ($type) {
            case 'area':

                // 水平居中（位置很重要，建议在最初始位置）
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('C')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('D')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('F')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('G')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('H')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);


                $objActSheet->setCellValue('A1', '省/市/区');
                $objActSheet->setCellValue('B1', '昵称');
                $objActSheet->setCellValue('C1', '性别');
                $objActSheet->setCellValue('D1', '历史订单');
                $objActSheet->setCellValue('E1', '历史服务律师');
                $objActSheet->setCellValue('F1', '支出金额');
                $objActSheet->setCellValue('G1', '其他');
                $objActSheet->setCellValue('H1', '金额汇总');


                 // 设置个表格宽度
                $objPHPExcel->getActiveSheet()->getColumnDimension('A1')->setWidth(120);
                $objPHPExcel->getActiveSheet()->getColumnDimension('B1')->setWidth(60);
                $objPHPExcel->getActiveSheet()->getColumnDimension('C1')->setWidth(60);
                $objPHPExcel->getActiveSheet()->getColumnDimension('D1')->setWidth(60);
                $objPHPExcel->getActiveSheet()->getColumnDimension('E1')->setWidth(100);
                $objPHPExcel->getActiveSheet()->getColumnDimension('F1')->setWidth(150);
                $objPHPExcel->getActiveSheet()->getColumnDimension('G1')->setWidth(150);
                $objPHPExcel->getActiveSheet()->getColumnDimension('H1')->setWidth(100);

                // 垂直居中
                $objPHPExcel->getActiveSheet()->getStyle('A')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('B')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('D')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('E')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('F')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('G')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('H')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                foreach ($data as $k => $v) {
                    $k += 2;
                    //$v  = iconv('gb2312', 'utf-8', $v);
                    $objActSheet->setCellValue('A' . $k, $v['area_cn']);
                    $objActSheet->setCellValue('B' . $k, $v['nickname']);
                    // 表格内容
                    if ($v['sex'] == '1') {
                        $objActSheet->setCellValue('C' . $k, '男');
                    } else {
                        $objActSheet->setCellValue('C' . $k, '女');
                    }
                    $objActSheet->setCellValue('D' . $k, $v['ordercount']);
                    $objActSheet->setCellValue('E' . $k, $v['lawyercount']);
                    $objActSheet->setCellValue('F' . $k, $v['total']);
                    $objActSheet->setCellValue('G' . $k, '');
                    $objActSheet->setCellValue('H' . $k, $v['total']);
                }

                break;
            case 'order':

                // 水平居中（位置很重要，建议在最初始位置）
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('C')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('D')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('F')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('G')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('H')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('I')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);


                $objActSheet->setCellValue('A1', '名称');
                $objActSheet->setCellValue('B1', '执行律师');
                $objActSheet->setCellValue('C1', '预留电话');
                $objActSheet->setCellValue('D1', '订单编号');
                $objActSheet->setCellValue('E1', '订单标题');
                $objActSheet->setCellValue('F1', '付款金额');
                $objActSheet->setCellValue('G1', '支付方式');
                $objActSheet->setCellValue('H1', '订单状态');
                $objActSheet->setCellValue('I1', '下单时间');
                $objActSheet->setCellValue('J1', '其他');
                // 设置个表格宽度
                $objPHPExcel->getActiveSheet()->getColumnDimension('A1')->setWidth(120);
                $objPHPExcel->getActiveSheet()->getColumnDimension('B1')->setWidth(60);
                $objPHPExcel->getActiveSheet()->getColumnDimension('C1')->setWidth(60);
                $objPHPExcel->getActiveSheet()->getColumnDimension('D1')->setWidth(100);
                $objPHPExcel->getActiveSheet()->getColumnDimension('E1')->setWidth(100);
                $objPHPExcel->getActiveSheet()->getColumnDimension('F1')->setWidth(150);
                $objPHPExcel->getActiveSheet()->getColumnDimension('G1')->setWidth(150);
                $objPHPExcel->getActiveSheet()->getColumnDimension('H1')->setWidth(100);
                $objPHPExcel->getActiveSheet()->getColumnDimension('I1')->setWidth(100);
                $objPHPExcel->getActiveSheet()->getColumnDimension('J1')->setWidth(100);

                // 垂直居中
                $objPHPExcel->getActiveSheet()->getStyle('A')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('B')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('C')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('D')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('E')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('F')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('G')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('H')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('I')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('J')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

                foreach ($data as $k => $v) {
                    $k += 2;
                    //$v  = iconv('gb2312', 'utf-8', $v);
                    $objActSheet->setCellValue('A' . $k, $v['nickname']);
                    $objActSheet->setCellValue('B' . $k, $v['lawyer']);
                    $objActSheet->setCellValue('C' . $k, $v['phone']);
                    $objActSheet->setCellValue('D' . $k, $v['order_no']);
                    $objActSheet->setCellValue('E' . $k, $v['title']);
                    if ($v['payway'] == 'coin') {
                        $v['total'] = $v['total'] / $this->sysconfig['expcoin'];
                    }
                    $objActSheet->setCellValue('F' . $k, $v['total']);
                    $objActSheet->setCellValue('G' . $k, $v['payway_cn']);
                    $objActSheet->setCellValue('H' . $k, $v['status_cn']);
                    $objActSheet->setCellValue('I' . $k, date('Y-m-d H:i:s', $v['add_time']));
                    $objActSheet->setCellValue('J' . $k, '');
                }
                break;
            case 'lawyer':
                // 水平居中（位置很重要，建议在最初始位置）
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('C')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('D')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('F')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('G')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('H')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('I')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);


                $objActSheet->setCellValue('A1', '姓名');
                $objActSheet->setCellValue('B1', '电话');
                $objActSheet->setCellValue('C1', '地区');
                $objActSheet->setCellValue('D1', '行业');
                $objActSheet->setCellValue('E1', '开始日期');
                $objActSheet->setCellValue('F1', '结束日期');
                $objActSheet->setCellValue('G1', '服务天数');
                $objActSheet->setCellValue('H1', '当前状态');
                $objActSheet->setCellValue('I1', '备注');
                // 设置个表格宽度
                $objPHPExcel->getActiveSheet()->getColumnDimension('A1')->setWidth(120);
                $objPHPExcel->getActiveSheet()->getColumnDimension('B1')->setWidth(60);
                $objPHPExcel->getActiveSheet()->getColumnDimension('C1')->setWidth(60);
                $objPHPExcel->getActiveSheet()->getColumnDimension('D1')->setWidth(60);
                $objPHPExcel->getActiveSheet()->getColumnDimension('E1')->setWidth(100);
                $objPHPExcel->getActiveSheet()->getColumnDimension('F1')->setWidth(150);
                $objPHPExcel->getActiveSheet()->getColumnDimension('G1')->setWidth(150);
                $objPHPExcel->getActiveSheet()->getColumnDimension('H1')->setWidth(100);
                $objPHPExcel->getActiveSheet()->getColumnDimension('I1')->setWidth(100);

                // 垂直居中
                $objPHPExcel->getActiveSheet()->getStyle('A')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('B')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('D')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('E')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('F')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('G')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('H')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('I')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

                foreach ($data as $k => $v) {
                    $k += 2;
                    //$v  = iconv('gb2312', 'utf-8', $v);
                    $objActSheet->setCellValue('A' . $k, $v['username']);
                    $objActSheet->setCellValue('B' . $k, $v['phone']);
                    $objActSheet->setCellValue('C' . $k, $v['area']);
                    $objActSheet->setCellValue('D' . $k, $v['industry']);
                    $begin_time = empty($v['begin_time']) ? '' : date('Y-m-d H:i:s', $v['begin_time']);
                    $end_time = empty($v['end_time']) ? '' : date('Y-m-d H:i:s', $v['end_time']);
                    $objActSheet->setCellValue('E' . $k, $begin_time);
                    $objActSheet->setCellValue('F' . $k, $end_time);
                    $objActSheet->setCellValue('G' . $k, $v['days']);
                    $objActSheet->setCellValue('H' . $k, $v['status'] ? '服务中' : '已结束');
                    $objActSheet->setCellValue('I' . $k, $v['content']);
                }
                break;
            case 'lawyermain': //律师事务所统计
                // 水平居中（位置很重要，建议在最初始位置）
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('C')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('D')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

                $objActSheet->setCellValue('A1', '省/市/区');
                $objActSheet->setCellValue('B1', '名称');
                $objActSheet->setCellValue('C1', '人数');
                $objActSheet->setCellValue('D1', '收益');
                // 设置个表格宽度
                $objPHPExcel->getActiveSheet()->getColumnDimension('A1')->setWidth(120);
                $objPHPExcel->getActiveSheet()->getColumnDimension('B1')->setWidth(60);
                $objPHPExcel->getActiveSheet()->getColumnDimension('C1')->setWidth(60);
                $objPHPExcel->getActiveSheet()->getColumnDimension('D1')->setWidth(60);
                // 垂直居中
                $objPHPExcel->getActiveSheet()->getStyle('A')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('B')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('D')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

                foreach ($data as $k => $v) {
                    $k += 2;
                    //$v  = iconv('gb2312', 'utf-8', $v);
                    $objActSheet->setCellValue('A' . $k, $v['address']);
                    $objActSheet->setCellValue('B' . $k, $v['name']);
                    $objActSheet->setCellValue('C' . $k, $v['count']);
                    $objActSheet->setCellValue('D' . $k, $v['total']);
                }
                break;
            case 'lawyerinfo': //律师统计
                // 水平居中（位置很重要，建议在最初始位置）
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('C')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('D')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('F')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('G')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('H')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('I')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);


                $objActSheet->setCellValue('A1', '姓名');
                $objActSheet->setCellValue('B1', '性别');
                $objActSheet->setCellValue('C1', '手机号码');
                $objActSheet->setCellValue('D1', '服务单数');
                $objActSheet->setCellValue('E1', '收益');
                $objActSheet->setCellValue('F1', '历史服务用户');
                $objActSheet->setCellValue('G1', '服务时间/分钟');
                $objActSheet->setCellValue('H1', '钱包余额');
                // 设置个表格宽度
                $objPHPExcel->getActiveSheet()->getColumnDimension('A1')->setWidth(120);
                $objPHPExcel->getActiveSheet()->getColumnDimension('B1')->setWidth(60);
                $objPHPExcel->getActiveSheet()->getColumnDimension('C1')->setWidth(60);
                $objPHPExcel->getActiveSheet()->getColumnDimension('D1')->setWidth(60);
                $objPHPExcel->getActiveSheet()->getColumnDimension('E1')->setWidth(100);
                $objPHPExcel->getActiveSheet()->getColumnDimension('F1')->setWidth(150);
                $objPHPExcel->getActiveSheet()->getColumnDimension('G1')->setWidth(100);
                $objPHPExcel->getActiveSheet()->getColumnDimension('H1')->setWidth(100);

                // 垂直居中
                $objPHPExcel->getActiveSheet()->getStyle('A')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('B')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('D')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('E')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('F')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('G')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('H')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

                foreach ($data as $k => $v) {
                    $k += 2;
                    //$v  = iconv('gb2312', 'utf-8', $v);
                    $objActSheet->setCellValue('A' . $k, $v['username']);
                    $objActSheet->setCellValue('B' . $k, $v['sex'] ? '男' : '女');
                    $objActSheet->setCellValue('C' . $k, $v['phone']);
                    $objActSheet->setCellValue('D' . $k, $v['number']);
                    $objActSheet->setCellValue('E' . $k, $v['total']);
                    $objActSheet->setCellValue('F' . $k, $v['members']);
                    $objActSheet->setCellValue('G' . $k, $v['time']);
                    $objActSheet->setCellValue('H' . $k, $v['wallet']);
                }
                break;
            case 'lawyerorder': //律师收益统计
                // 水平居中（位置很重要，建议在最初始位置）
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('C')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('D')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('F')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('G')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);


                $objActSheet->setCellValue('A1', '订单编号  ');
                $objActSheet->setCellValue('B1', '金额');
                $objActSheet->setCellValue('C1', '服务用户');
                $objActSheet->setCellValue('D1', '订单类型');
                $objActSheet->setCellValue('E1', '服务时间/分钟');
                $objActSheet->setCellValue('F1', '接单时间');
                $objActSheet->setCellValue('G1', '状态');
                // 设置个表格宽度
                $objPHPExcel->getActiveSheet()->getColumnDimension('A1')->setWidth(120);
                $objPHPExcel->getActiveSheet()->getColumnDimension('B1')->setWidth(60);
                $objPHPExcel->getActiveSheet()->getColumnDimension('C1')->setWidth(60);
                $objPHPExcel->getActiveSheet()->getColumnDimension('D1')->setWidth(60);
                $objPHPExcel->getActiveSheet()->getColumnDimension('E1')->setWidth(100);
                $objPHPExcel->getActiveSheet()->getColumnDimension('F1')->setWidth(150);
                $objPHPExcel->getActiveSheet()->getColumnDimension('G1')->setWidth(150);

                // 垂直居中
                $objPHPExcel->getActiveSheet()->getStyle('A')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('B')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('C')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

                $objPHPExcel->getActiveSheet()->getStyle('D')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('E')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('F')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('G')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

                foreach ($data as $k => $v) {
                    $k += 2;
                    $objActSheet->setCellValue('A' . $k, $v['order_no']);
                    $objActSheet->setCellValue('B' . $k, $v['total']);
                    $objActSheet->setCellValue('C' . $k, $v['user']);
                    $objActSheet->setCellValue('D' . $k, $v['gid_cn']);
                    $objActSheet->setCellValue('E' . $k, $v['time']);
                    $objActSheet->setCellValue('F' . $k, date('Y-m-d H:i:s', $v['begin_time']));
                    $objActSheet->setCellValue('G' . $k, strip_tags($v['status']));
                }
                break;
            case 'lawyer_member': //律师服务用户统计
                // 水平居中（位置很重要，建议在最初始位置）
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('C')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('D')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('F')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('G')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('H')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);


                $objActSheet->setCellValue('A1', '姓名');
                $objActSheet->setCellValue('B1', '电话');
                $objActSheet->setCellValue('C1', '性别');
                $objActSheet->setCellValue('D1', '开始日期');
                $objActSheet->setCellValue('E1', '结束日期');
                $objActSheet->setCellValue('F1', '服务天数  ');
                $objActSheet->setCellValue('G1', '当前状态');
                $objActSheet->setCellValue('H1', '备注');
                // 设置个表格宽度
                $objPHPExcel->getActiveSheet()->getColumnDimension('A1')->setWidth(120);
                $objPHPExcel->getActiveSheet()->getColumnDimension('B1')->setWidth(60);
                $objPHPExcel->getActiveSheet()->getColumnDimension('C1')->setWidth(60);
                $objPHPExcel->getActiveSheet()->getColumnDimension('D1')->setWidth(60);
                $objPHPExcel->getActiveSheet()->getColumnDimension('E1')->setWidth(100);
                $objPHPExcel->getActiveSheet()->getColumnDimension('F1')->setWidth(150);
                $objPHPExcel->getActiveSheet()->getColumnDimension('G1')->setWidth(150);
                $objPHPExcel->getActiveSheet()->getColumnDimension('H1')->setWidth(100);

                // 垂直居中
                $objPHPExcel->getActiveSheet()->getStyle('A')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('B')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('D')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('E')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('F')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('G')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('H')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                foreach ($data as $k => $v) {
                    $k += 2;
                    //$v  = iconv('gb2312', 'utf-8', $v);
                    $objActSheet->setCellValue('A' . $k, $v['nickname']);
                    $objActSheet->setCellValue('B' . $k, $v['phone']);
                    $objActSheet->setCellValue('C' . $k, $v['sex'] ? '男' : '女');
                    $objActSheet->setCellValue('D' . $k, date('Y-m-d H:i:s', $v['begin_time']));
                    if (!empty($v['end_time'])) {
                            $data = date('Y-m-d H:i:s', $v['end_time']);
                        } else {
                        $data = '无限制';
                    }
                    $objActSheet->setCellValue('E' . $k, $data);
                    $objActSheet->setCellValue('F' . $k, $v['days']);
                    $objActSheet->setCellValue('G' . $k, $v['status'] ? '男' : '女');
                    $objActSheet->setCellValue('H' . $k, $v['content']);
                }
                break;
        }
        // 表格高度
        $objActSheet->getRowDimension($k)->setRowHeight(20);
        // Rename sheet
        $objPHPExcel->getActiveSheet()->setTitle($title . '用户统计');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);
        ob_end_clean(); //清除缓冲区,避免乱码
        header('Content-Type: application/vnd.ms-excel;charset=UTF-8")');
        header('Content-Disposition: attachment;filename="' . $title . '(' . date('Ymd-His') . ').xls"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding:binary");
        //这里方便直接访问url下载
        // $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        // $objWriter->save('php://output');

        //返回已经存好的文件目录地址提供下载
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
        $response = array(
            'success' => true,
            'url' => '/' . $this->saveExcelToLocalFile($objWriter, $title . '-' . date('Y-m-d-H-i-s', time())),
        );

        return json_encode($response);
        // END
    }

       /*
     *note:EXECL文件保存
     *auth:YW
     *date:2018/05/15
     *param $objWriter[对象]，$filename[文件名]
     */
    public function saveExcelToLocalFile($objWriter, $filename)
    {
        // make sure you have permission to write to directory
        $filePath = 'tmp/' . $filename . '.xlsx';
        $objWriter->save($filePath);
        return $filePath;
    }


}
