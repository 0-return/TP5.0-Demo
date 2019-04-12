<?php
namespace app\uapi\validate;
use think\Validate;
/**
 * Question验证
 */
class Question extends Validate
{

    protected $rule = [
        'describe'      =>  'require',
        'uid'      =>  'require',
        'goods_type_id'      =>  'require',
        'pay_coin'      =>  'require',
    ];

    protected $message  =   [
        'describe.require'    => '问题描述不能为空',
        'uid.require'    => 'uid不能为空',
        'goods_type_id.require'    => '行业id不能为空',
        'pay_coin.require'    => '赏金不能为空',
    ];
}