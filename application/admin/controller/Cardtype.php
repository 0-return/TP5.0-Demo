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

class Cardtype extends Init
{
    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'].'cardtype';
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
        $this->checkSearch($map);
    }

    protected function _after_list(&$list)
    {
    	// 拼接数据
        $cardtypeHtml = $this->cate_tree($list,$parentid = 0,$count = 0);
        $this->assign('cardtype',$cardtypeHtml);

    }

    /**
     * @auth PT
     * @date 2018.03.06
     * @purpose 添加用户
     * @return void
     */
    public function _before_add(&$list){
        if ($this->request->isPost()){
            $_POST['add_time'] = time();
        }else{
            $option = cate_tree_html($this->obj,$this->config['prefix'].'cardtype',array('pid' => 'cid','status'=>'status','title'=>'title','id'=>'id'));
            $this->assign('option',$option);
            $this->display();
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
            if($value['cid'] == $parentid){
                $statushtml = $value['status']?'<span class="label label-success radius">开启</span>':'<span class="label label-danger radius">关闭</span>';
                $statusfuc = $value['status']?'card_stop':'card_start';
                $status = $value['status'] == 0?'1':'0';
                $optionHtml .= "<td>{$value['id']}</td><td>{$value['cid']}</td>";
                $optionHtml .= "<td>{$linstr} {$value['title']}</td>";
                $optionHtml .= "<td>{$statushtml}</td><td class=\"f-14 td-manage\"><a style=\"text-decoration:none\" onClick=\"{$statusfuc}(this,'{$value['id']}','{$status}')\" href=\"javascript:;\" title=\"停用\"><i class=\"Hui-iconfont\">&#xe631;</i></a> <a title=\"删除\" href=\"javascript:;\" onclick=\"_del(this,'{$value['id']}')\" class=\"ml-5\" style=\"text-decoration:none\"><i class=\"Hui-iconfont\">&#xe6e2;</i></a></td>";
                $optionHtml .= $this->cate_tree($data,$value['id'],$count+1);
            }
        }
        $optionHtml .= "</tr>";
        return $optionHtml;
    }





}