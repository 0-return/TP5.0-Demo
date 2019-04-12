<?php
namespace app\uapi\validate;
use think\Validate;
/**
 * User验证
 */
class User extends Validate
{

    protected $rule = [

        'username'      =>  'checkPhone|require|unique:user',
        'password'      =>  'require|length:6,20',
        'checkcode'      =>  'require|number|length:6',
    ];

    protected $message  =   [
        'username.checkPhone'    => '用户名格式错误',
        'username.require'    => '用户名不能为空',
        'username.unique'      => '用户名已存在',
        'password.require'    => '密码不能为空',
        'password.length'       => '密码应在6-20之间',
        'checkcode.require'    => '验证码不能为空',
        'checkcode.number'       => '验证码格式为数字',
        'checkcode.length'       =>'验证码格式为6位的数字',
    ];

        /**
     * Effect 自定义手机号验证规则
     * @param $value
     * @return bool
     */
    protected function checkPhone($value)
    {
        return 1 === preg_match("/^1[34578]\d{9}$/",$value);
    }


}