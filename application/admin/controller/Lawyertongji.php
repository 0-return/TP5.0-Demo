<?php
namespace app\admin\controller;

use app\admin\common\controller\Init;
use think\Db;

/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：法条管理
 */
class Lawyertongji extends Init
{


    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'] . 'lawyer';
    }
    /**
     * @auth PT
     * @date 2019.3.1
     * @purpose 列表
     * @return void
     */
    public function index()
    {
        exit;
        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $where['where'] = $map;
// var_dump($where);exit;
        $this->_list('', $where);
        return view();
    }

    public function _filter(&$map)
    {

        $this->checkSearch($map);
    }


    /**
     * @auth PT
     * @date 2019.3.1
     * @purpose
     * @return void
     */
    public function _after_list(&$list)
    {

        // $where['status'] = '1';
        // $count = $obj->table('fwy_lawfirm')->where($where)->count();
        // //echo $obj->getLastSql();
        // if ($get['do'] == 'export') { //导出当前全部数据
        //     $result = $obj->table('fwy_lawfirm')->field('id,province,city,area,name')->where($where)->select();
        // } else {
        //     $result = $obj->table('fwy_lawfirm')->field('id,province,city,area,name')->where($where)->limit($page->firstRow . ',' . $page->listRows)->select();
        // }

        // foreach ($result as $k => $v) {
        //     $result[$k]['address'] = get_area_nostyle($v['province'])['regionname'] . '-' . get_area_nostyle($v['city'])['regionname'] . '-' . get_area_nostyle($v['area'])['regionname'];
        //     unset($where);

        //     $where['status'] = 2;
        //     $where['lawfirm_id'] = $v['id'];
        //     $result[$k]['count'] = $obj->table('fwy_lawyer')->where($where)->count();       //总人数
        //     unset($where);
        //     $where['a.status'] = 2;
        //     $where['a.lawfirm_id'] = $v['id'];
        //     $where['b.ustatus'] = 2;
        //     $where['b.status'] = array('in', '-1,1,2,3,4');
        //     //金钱收入
        //     $res = $obj->table('fwy_lawyer as a')->field('b.uid,b.total,b.payway')->join('fwy_order as b on a.id = b.lid')->where($where)->select();

        //     $total = 0;
        //     if ($res) {
        //         foreach ($res as $key => $value) {
        //             if ($value['payway'] == 'coin') {
        //                     $total += moneytomoney($value['total'], $this->sysconfig['expcoin']);
        //                 } else {
        //                 $total += floatval($value['total']);
        //             }
        //         }
        //     }
        //     $result[$k]['total'] = $total;
        //     unset($where);
        // }

        // if ($get['do'] == 'export') { //导出当前所有数据
        //     $this->exportDate('lawyermain', $result, 'lawyerOffice');
        //     exit;
        // }
        // //重新变更时间格式条件返回给前端
        // $get['begintime'] = date('Y-m-d', $begintime);
        // $get['endtime'] = date('Y-m-d', $endtime);

        // $this->assign('count', $count);
        // $this->assign('pro', get_area_father());
        // $this->assign('list', $result);
        // $this->assign('showpage', $showpage);
        // $this->assign('condition', $get);
    }



}
