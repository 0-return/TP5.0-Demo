<?php
/**
 * Created by PhpStorm.
 * User: EVOL
 * Date: 2018/11/13
 * Time: 21:13
 */
namespace app\admin\behaviors;
use think\Controller;
class Msg extends Controller
{
    private $msg = array(
        'success'       => '操作成功',
        'error'         => '操作失败',
        'fail'          => '操作无效',
        'get_success'    => '获取成功',
        'get_error'    => '获取成功',
        'login'         => '账号密码错误',
        'code'          => '验证码有误',
        'auth'          => '你没有权限，请联系管理员',
        'serch_check'   => '请输入要查询的信息',
        'add_check'     => '记录已存在，请不要重复添加',
        'del_fail'      => '无法删除，该类目下还有信息',
        'upload_success'      => '文件上传成功',
        'upload_fail'      => '文件上传失败',
        'add_goods'      => '添加了商品',
        'del_goods'      => '删除了商品',
        'lawyer_auth_is_pass'      => '认证未通过，无法推荐',
        'lawfirm_delete_is_fail'      => '无法删除，该律师事务所下还有律师',
        'user_not_member' => '无法分配服务律师：当前用户会员已经过期',
        'lawyer_not_status' => '无法分配服务律师：当前律师认证信息异常',
    );

    public function run(&$param)
    {
        $param = $this->msg;
    }
}