<?php
namespace app\lapi\validate;
use think\Validate;
/**
 * User验证
 */
class User extends Validate
{

    protected $rule = [
        'username|用户名'   =>  ['require','unique:user','min' => 11],
        'password|密码'     =>  ['require','min' => 6,'max' => 20],
        'checkcode|验证码'  =>  ['require'],
    ];

}