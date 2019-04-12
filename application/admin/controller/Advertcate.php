<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Db;

class Advertcate extends Init{

    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'].'advert_cate';
    }

    public function index()
    {
        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $map['status'] = array('in','0,1');
        $where['where'] =  $map;
        $this->_list('',$where,false,'','desc','9999999999999999999999');
        return view();
    }

    public function _filter(&$map){
        $this->checkSearch($map);
    }

    public function _after_list(&$list){
        $cardtypeHtml = $this->cate_tree($list);
        $this->assign('advtype', $cardtypeHtml);
    }

       /**
     * @auth PT
     * @date 2019.3.1
     * @purpose 添加前
     * @return void
     */
     public function _before_add(&$list)
    {

        if ($this->request->post()){
            $list['status'] = '1';
        }else{
            $option = cate_tree_html($this->obj[1], $this->table, array('pid' => 'aid', 'id' => 'id', 'title' => 'title', 'status' => 'status'));
            $this->assign('option', $option);
        }

    }

    /*
     *note:无限分类(内嵌样式)
     *auth:杨炜
     * input $data数据，$parentid父id $count累加次数
     * return htmlstr
     */
    public function cate_tree($data,$parentid = 0,$count = 0)
    {
        $optionHtml = "<tr>";
        $linstr = str_repeat("——|",$count);

        foreach ($data as $key => $value){
            if($value['aid'] == $parentid){
                $statushtml = $value['status']?'<span class="label label-success radius">开启</span>':'<span class="label label-danger radius">关闭</span>';
                $statusfuc = $value['status']?'card_stop':'card_start';
                $status = $value['status'] == 0?'1':'0';
                $optionHtml .= "<td>{$value['id']}</td><td>{$value['aid']}</td>";
                $optionHtml .= "<td>{$linstr} {$value['title']}</td>";
                $optionHtml .= "<td>{$value['flag']}</td>";
                $optionHtml .= "<td>{$value['discribe']}</td>";
                if ($value['status']==0) {
                    $optionHtml .= "<td>{$statushtml}</td><td class=\"f-14 td-manage\"> <a style=\"text-decoration:none\" onClick=\"{$statusfuc}(this,'{$value['id']}','{$status}')\" href=\"javascript:;\" title=\"启用\"><i class=\"Hui-iconfont\">&#xe6e1;</i></a> <a title=\"删除\" href=\"javascript:;\" onclick=\"_del(this,'{$value['id']}')\" class=\"ml-5\" style=\"text-decoration:none\"><i class=\"Hui-iconfont\">&#xe6e2;</i></a></td>";
                }else{
                    $optionHtml .= "<td>{$statushtml}</td><td class=\"f-14 td-manage\"> <a style=\"text-decoration:none\" onClick=\"{$statusfuc}(this,'{$value['id']}','{$status}')\" href=\"javascript:;\" title=\"停用\"><i class=\"Hui-iconfont\">&#xe631;</i></a> <a title=\"删除\" href=\"javascript:;\" onclick=\"_del(this,'{$value['id']}')\" class=\"ml-5\" style=\"text-decoration:none\"><i class=\"Hui-iconfont\">&#xe6e2;</i></a></td>";
                }

                $optionHtml .= $this->cate_tree($data,$value['id'],$count+1);
            }
        }
        $optionHtml .= "</tr>";
        return $optionHtml;
    }



}