<?php
namespace app\uapi\validate;
use think\Validate;
/**
 * User验证
 */
class Order extends Validate
{

    protected $rule = [

        'gid'               =>  'require',
        'uid'               =>  'require',
        'total'             =>  'require',
    ];

    protected $message  =   [
        'gid.require'       => '商品信息不能为空',
        'uid.require'       => '用户名不能为空',
        'total.require'     => '金额不能为空',

    ];


}