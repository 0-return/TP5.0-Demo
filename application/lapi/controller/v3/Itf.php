<?php
/**
 * Create by .
 * Cser Administrator
 * Time 17:59
 */
namespace app\lapi\controller\v3;
interface Itf{

    public function add();          //添加
    public function del();          //根据id删除
    public function delall();       //删除所有
    public function edit();         //编辑(用户状态修改)
    public function show();         //根据id获取
    public function showall();      //获取所有信息
    public function serch();        //搜索

}