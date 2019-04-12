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
class Article extends Init
{

    public $tag = array(
        'f' => '推荐',
        'h' => '热点',
    );
    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'] . 'article';
    }
    /**
     * @auth PT
     * @date 2019.3.1
     * @purpose 列表
     * @return void
     */
    public function index()
    {
        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $map['status'] = array('gt', '-1');
        $map['sort'] = '1';
        $where['where'] = $map;
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
     * @purpose 添加公告前序列化图片，添加时间
     * @return void
     */
    public function _before_add(&$list)
    {

        if ($this->request->post()) {
            $data = $this->request->post();

            $list['tag'] = implode(',', $data['tag']);
            $list['content'] = $data['content'];
            $list['status'] = '0';
            $list['add_time'] = time();
        } else {
            $option = $this->cate_tree_html($this->obj[1], $this->config['prefix'] . 'article_cate');
            $this->assign('option', $option);
            $this->display();
        }
    }

    /**
     * @auth PT
     * @date 2019.3.1
     * @purpose 列表展示前反序列化图片
     * @return void
     */
    public function _after_list(&$list)
    {

        foreach ($list as $key => $value) {
            $list[$key]['type_cn'] = get_type_str($this->obj[1], $this->config['prefix'].'article_cate', $value['type'], 'id,tid,name', array('condition' => 'id', 'flag' => 'name', 'pid' => 'tid'));
            $list[$key]['title'] = mb_substr($value['title'], 0, 12) . '...';
        }
    }

    /**
     * @auth PT
     * @date 2019.3.1
     * @purpose 修改操作前序列化图片、编辑时间、写入日志
     * @return void
     */
    public function _before_update(&$list)
    {

        foreach ($list as $key => $value) {
            if (empty($value)) {
                unset($list[$key]);
            }
        }
        $list['tag'] = implode(',', $list['tag']);
        $list['content'] = $list['content'];
        $map['id'] = $list['id'];
        unset($list['schedule_mc']); //去掉多餘數據

    }

    /**
     * @auth PT
     * @date 2019.3.1
     * @purpose
     * @return void
     */

    public function _before_edit(&$list)
    {

        if ($this->request->post()) { } else {
            $w['id'] = $list['id'];
            $tid = $this->obj[1]->table($this->table)->where($w)->value('type');
            $option = $this->cate_tree_html($this->obj[1], $this->config['prefix'] . 'article_cate', '-1', 0, $tid);
            $this->assign('option', $option);
        }
    }


    /**
     * @auth PT
     * @date 2019.3.1
     * @purpose 编辑时反序列化图片用于展示
     * @return void
     */
    public function _after_edit(&$list)
    {
        $list['tag'] = explode(',', $list['tag']);
    }

    /**
     * @auth PT
     * @date 2019.3.1
     * @purpose 预览法条
     * @return void
     */

    public function preview()
    {
        $get = $this->request->request('id');
        $where['id'] = $get;
        $res = $this->obj[1]->table($this->table)->where($where)->find();
        $this->assign('vo', $res);
        return view();
    }

    /**
     * note:无限分类(内嵌样式)
     * auth:YW
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

}
