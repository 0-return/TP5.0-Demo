<?php
namespace app\uapi\validate;
use think\Validate;
/**
 * Card验证
 */
class Card extends Validate
{

    protected $rule = [
        'cardcode'      =>  'require',
        'uid'      =>  'require',
    ];

    protected $message  =   [
        'cardcode.require'    => '卡号不能为空',
        'uid.require'    => 'uid不能为空',
    ];
}