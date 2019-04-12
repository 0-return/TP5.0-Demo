<?php
namespace app\admin\controller;
use app\common\controller\Init;
/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：系统配置
 */
class Config extends Init
{
    public function _initialize()
    {
        parent::_initialize();
    }
    /**
     * @auth 杨炜
     * @date 2017.12.4
     * @purpose 首页
     * @return void
     */
    public function index()
    {
        return view();
    }
    /**
     * @auth 杨炜
     * @date 2017.12.4
     * @purpose 设置信息
     * @return void
     */
    public function _before_insert(){

    }


}