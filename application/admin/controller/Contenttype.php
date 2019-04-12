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

class Contenttype extends Init
{
    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'] . 'content_type';
    }

    public function index()
    {
        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $map['status'] = array('in', '0,1');
        $where['where'] =  $map;
        $this->_list('', $where, false, '', 'desc', '9999999999999999999999');
        return view();
    }

    public function _filter(&$map)
    {
        $this->checkSearch($map);
    }

    protected function _after_list(&$list)
    {

        $option = $this->cate_tree($list);
        $this->assign('lists', $option);
    }

    /**
     * @auth PT
     * @date 2018.03.06
     * @purpose 添加用户
     * @return void
     */
    public function _before_add(&$list)
    {
        if ($this->request->post()) {
            if (isset($list['images'])) {
                $list['icon'] = serialize($list['images']);
            }
            unset($list['images']);
        } else {
            $option = $this->cate_tree_html($this->obj, $this->table);
            $this->assign('option', $option);
        }
    }

    public function _before_edit(&$list)
    {
        if ($this->request->post()) { } else {
            $w['id'] = $_REQUEST['id'];
            $tid = $this->obj->table($this->table)->where($w)->value('tid');
            $option = $this->cate_tree_html($this->obj, $this->table, '-1', 0, $tid);
            $this->assign('option', $option);
        }
    }


    public function _after_edit(&$list)
    {

        if ($list['icon']) {
            $list['url'] = $this->config['weburl'];
            $list['images'] = unserialize($list['icon']);
        } else {
            $list['images'] = '';
        }
    }

    public function _before_update(&$list)
    {

        if (isset($list['images'])) {
            $list['icon'] = serialize($list['images']);
        }
        unset($list['images']);
    }

    /*
     *note:无限分类(内嵌样式)
     *auth:杨炜
     * input $data数据，$parentid父id $count累加次数
     * return htmlstr
     */
    public function cate_tree_html($obj, $table = null, $parentid = '-1', $count = 0, $tid = '')
    {
        $obj = empty($table) ? $obj : $obj->table($table);
        $where['tid'] = $parentid;
        $where['status'] = array('gt', 0);
        $res = $obj->where($where)->select();
        if (empty($res)) return false;
        $optionHtml = '';
        $linstr = str_repeat("——|", $count);
        foreach ($res as $key => $value) {
            if (empty($tid)) {
                if ($value['tid'] == $parentid) {
                    $optionHtml .= "<option value='{$value['id']}'>{$linstr} {$value['name']}</option>";
                    $optionHtml .= $this->cate_tree_html($obj, $table, $value['id'], $count + 1);
                }
            } else {
                if ($value['tid'] == $parentid) {
                    if ($tid == $value['id'] || $tid == '-1') {
                        $optionHtml .= "<option selected value='{$value['id']}'>{$linstr} {$value['name']}</option>";
                    } else {
                        $optionHtml .= "<option value='{$value['id']}'>{$linstr} {$value['name']}</option>";
                    }
                    $optionHtml .= $this->cate_tree_html($obj, $table, $value['id'], $count + 1, $tid);
                }
            }
        }
        return $optionHtml;
    }

    /*
     *note:无限分类(内嵌样式)
     *auth:杨炜
     * input $data数据，$parentid父id $count累加次数
     * return htmlstr
     */
    public function cate_tree($data, $parentid = -1, $count = 0)
    {
        $optionHtml = "<tr>";
        $linstr = str_repeat("——|", $count);

        foreach ($data as $key => $value) {
            if ($value['tid'] == $parentid) {
                $statushtml = $value['status'] ? '<span class="label label-success radius">开启</span>' : '<span class="label label-danger radius">关闭</span>';
                $statusfuc = $value['status'] ? 'card_stop' : 'card_start';
                $status = $value['status'] == 0 ? '1' : '0';
                $optionHtml .= "<td>{$value['id']}</td><td>{$value['tid']}</td>";
                $optionHtml .= "<td>{$linstr} {$value['name']}</td>";
                if ($value['status'] == 0) {
                    $optionHtml .= "<td>{$statushtml}</td><td class=\"f-14 td-manage\"><a style=\"text-decoration:none\" onClick=\"_edit('编辑','/public/admin.php/admin/contenttype/showbyid','{$value['id']}',1200,600)\" href=\"javascript:;\" title=\"编辑\"><i class=\"Hui-iconfont\">&#xe6df;</i></a> <a style=\"text-decoration:none\" onClick=\"{$statusfuc}(this,'{$value['id']}','{$status}')\" href=\"javascript:;\" title=\"启用\"><i class=\"Hui-iconfont\">&#xe6e1;</i></a> <a title=\"删除\" href=\"javascript:;\" onclick=\"_del(this,'{$value['id']}')\" class=\"ml-5\" style=\"text-decoration:none\"><i class=\"Hui-iconfont\">&#xe6e2;</i></a></td>";
                } else {
                    $optionHtml .= "<td>{$statushtml}</td><td class=\"f-14 td-manage\"><a style=\"text-decoration:none\" onClick=\"_edit('编辑','/public/admin.php/admin/contenttype/showbyid','{$value['id']}',1200,600)\" href=\"javascript:;\" title=\"编辑\"><i class=\"Hui-iconfont\">&#xe6df;</i></a> <a style=\"text-decoration:none\" onClick=\"{$statusfuc}(this,'{$value['id']}','{$status}')\" href=\"javascript:;\" title=\"停用\"><i class=\"Hui-iconfont\">&#xe631;</i></a> <a title=\"删除\" href=\"javascript:;\" onclick=\"_del(this,'{$value['id']}')\" class=\"ml-5\" style=\"text-decoration:none\"><i class=\"Hui-iconfont\">&#xe6e2;</i></a></td>";
                }

                $optionHtml .= $this->cate_tree($data, $value['id'], $count + 1);
            }
        }
        $optionHtml .= "</tr>";
        return $optionHtml;
    }
}
