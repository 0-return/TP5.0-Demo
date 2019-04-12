<?php
/**
 * Created by PhpStorm.
 * User: EVOL
 * Date: 2018/11/13
 * Time: 21:13
 */
namespace app\index\behaviors;
use think\Controller;
class Msg extends Controller
{
    private $msg = array(
        'msg_success'       => '操作成功',
        'msg_error'         => '操作失败',
        'msg_fail'          => '操作无效',
        'msg_avtive_off'          => '账号未激活，无法登录',
        'msg_user_off'          => '账号已锁定，无法登录',
    );

    public function run(&$param)
    {
        $param = $this->msg;
    }
}