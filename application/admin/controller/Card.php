<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Db;
/**
 * Created by PhpStorm.
 * User: EVOL
 * Date: 2018/10/27
 * Time: 17:11
 */

class Card extends Init
{
    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'].'cardcode';
        $this->table2 = $this->config['prefix'].'cardtype';
    }

    public function index()
    {
        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $map['status'] = array('in','0,1');
        $where['where'] =  $map;
        $this->_list('',$where);
        return view();
    }

    public function _filter(&$map){
        $get = $this->request->get();
        if (!empty($get['begintime']) && !empty($get['endtime']))
        {
            $map['add_time'] = array('between',array(strtotime($get['begintime']),strtotime($get['endtime'])));
        }
        $this->checkSearch($map);
    }

    protected function _after_list(&$list)
    {
        $top_type = $this->obj->table($this->table2)->select();
        $typeid = $type = $this->request->request('type');        //销毁激活
        foreach ($list as $key => $value){
            $typeWhere['id'] = $value['type'];
            $title = $this->obj->table($this->table2)->where($typeWhere)->value('title');
            $list[$key]['title'] = $title;
            if (!empty($value['uid']))
            {
                $w['uid'] = $value['uid'];
                $list[$key]['uname'] = get_name($this->obj->table($this->config['prefix'].'member'),$w);
                //计算过期时间
                $list[$key]['enday'] = ceil(($value['end_time']-time())/60/60/24);
            }else{
                $list[$key]['uname'] = '';
                $list[$key]['enday'] = '';
            }

            if (time() - $value['end_time'] > 0 && $value['activation'] != '1')
            {
                self::checkCard($value);
            }
        }
        $this->assign('top_type',$top_type);
        $this->assign('typeid',$typeid);

    }

    /**
     * @auth PT
     * @date 2018.03.06
     * @purpose 添加用户
     * @return void
     */
    public function _before_add(&$list){
        if ($this->request->isPost()){
            $_POST['start_time'] = strtotime($this->request->request('start_time'));
            $_POST['end_time'] = strtotime($this->request->request('end_time'));
            if ($_POST['start_time'] >= $_POST['end_time'] || ($_POST['end_time'] - $_POST['start_time'] < 18600))
            {
                echoMsg('11707','卡密结束时间不能小于或等于起始时间！');
            }

            $_POST['add_time'] = time();
            $_POST['status'] = 1;
            $number = (int)$this->request->request('number');
            // echoMsg('11707',$number);
            unset($_POST['number']);
            $_POST['card_code'] = $this->rand_str();

            $num = 0;
            if ($number > 1) {
                $number = $number -1 ;
                for($i = 0;$i<$number;$i++)
                {
                    $dd = $list;
                    $dd['card_code'] = $this->rand_str();

                    $res = $this->obj->table($this->table)->insert($dd);
                    if ($res)
                    {
                        $num = $num+1;
                    }
                }
                 if ($num == $number)
                {

                }else{
                    echoMsg('11707','卡密结束时间不能小于或等于起始时间！');
                }
            }
        }else{
            $option = cate_tree_html($this->obj,$this->config['prefix'].'cardtype',array('pid' => 'cid','status'=>'status','title'=>'title','id'=>'id'));
            $this->assign('option',$option);
            $this->display();
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
        $res = $this->obj->table($this->table)->where($where)->setField('activation','2');
        return $res?true:false;
    }


    /*
     *note:生成指定长度的纯数字字符串
     *auth:杨炜
     * input 长度
     * return $iden 前缀标识 $len长度
     */
    function rand_str($iden = '', $len = 16, $group = 4)
    {
        $str = '';
        for ($j = 1; $j <= $len; $j++) {
            if ($j % $group == 0) {
                $str .= mt_rand(0, 9) . '-';
            } else {
                $str .= mt_rand(0, 9);
            }
        }
        $str = trim($str, '-');
        return $str;
    }

    /**
     * note:导出excl
     * auth:杨炜
     * date:2018/03/30
     */
    public function exportexcel(){
        $post = $this->request->request();
        $obj = $this->obj;
        $where['type'] = $post['typeid'];
        if ($post['typeid'] == 0)
        {
            $this->error('请选择分类！');
            exit(0);

        }
        if (isset($post['activation']))        //激活状态
        {
            if ($post['activation'] == 0)
            {
                $title = '待激活';
            }else{
                $title = '已激活';
            }
            $where['activation'] = $post['activation'];
            $data = $obj->table($this->table)->where($where)->select();
        }else{
            $title = '全部';
            $data = $obj->table($this->table)->where($where)->select();
        }

        Vendor("Excel.PHPExcel");
        Vendor("Excel.Writer.Excel2007");
        $objPHPExcel = new \PHPExcel();
        new \PHPExcel_Writer_Excel2007($objPHPExcel);
        $objActSheet = $objPHPExcel->getActiveSheet();
        // 水平居中（位置很重要，建议在最初始位置）
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('C')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('D')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('F')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $objActSheet->setCellValue('A1', '卡密');
        $objActSheet->setCellValue('B1', '卡面金额');
        $objActSheet->setCellValue('C1', '卡面点/天数');
        $objActSheet->setCellValue('D1', '文字描述');
        $objActSheet->setCellValue('E1', '开始时间');
        $objActSheet->setCellValue('F1', '截止时间');
        // 设置个表格宽度
        $objPHPExcel->getActiveSheet()->getColumnDimension('A1')->setWidth(120);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B1')->setWidth(60);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C1')->setWidth(60);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D1')->setWidth(60);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E1')->setWidth(100);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F1')->setWidth(100);

        // 垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('B')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('D')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('E')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('F')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        foreach($data as $k=>$v){
            $k +=2;
            //$v  = iconv('gb2312', 'utf-8', $v);
            $objActSheet->setCellValue('A'.$k, $v['card_code']);
            $objActSheet->setCellValue('B'.$k, $v['price']);
            // 表格内容
            if ($v['card_type'] == '1')
            {
                $objActSheet->setCellValue('C'.$k, $v['days'].'天');
            }else{
                $objActSheet->setCellValue('C'.$k, $v['coin'].'点');
            }

            $objActSheet->setCellValue('D'.$k, $v['title']);
            $objActSheet->setCellValue('E'.$k, date('Y-m-d H:i:s',$v['start_time']));
            $objActSheet->setCellValue('F'.$k, date('Y-m-d H:i:s',$v['end_time']));
            // 表格高度
            $objActSheet->getRowDimension($k)->setRowHeight(20);

        }
        // Rename sheet
        $objPHPExcel->getActiveSheet()->setTitle($title.'卡密列表');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);
        ob_end_clean();//清除缓冲区,避免乱码
        header('Content-Type: application/vnd.ms-excel;charset=UTF-8")');
        header('Content-Disposition: attachment;filename="'.$title.'卡密列表(' . date('Ymd-His') . ').xls"');
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        // END
    }





}