<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：代理管理
 */
class Agentset extends Init
{

    function _initialize()
    {
        parent::_initialize();
    }
    /**
     * @auth PT
     * @date 2018.11.30
     * @purpose 列表
     * @return void
     */
    public function index()
    {

        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $map['where']['status']=array('gt','-1');
        $this->_list('',$map);
        return view();
    }

    public function _filter(&$map)
    {
        $this->checkSearch($map);

    }

    /**
     * @auth PT
     * @date 2018.11.30
     * @purpose 添加公告前序列化图片，添加时间
     * @return void
     */
     public function _before_add(&$list)
    {


    }

     /**
     * @auth PT
     * @date 2018.11.30
     * @purpose 列表展示前反序列化图片
     * @return void
     */
    public function _after_list(&$list)
    {

    }

    /**
     * @auth PT
     * @date 2018.11.30
     * @purpose 修改操作前序列化图片、编辑时间、写入日志
     * @return void
     */
    public function _before_update(&$list)
    {


    }

    /**
     * @auth PT
     * @date 2018.11.30
     * @purpose 编辑时反序列化图片用于展示
     * @return void
     */
     public function _after_edit(&$list)
    {


    }



}