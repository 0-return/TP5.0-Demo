<?php
namespace app\uapi\validate;
use think\Validate;
/**
 * Comment验证
 */
class Comment extends Validate
{

    protected $rule = [
        'lid'      =>  'require',
        'uid'      =>  'require',
        'content'      =>  'require',
        'star'      =>  'require',
        'order_no'      =>  'require',
    ];

    protected $message  =   [
        'lid.require'    => '律师id不能为空',
        'uid.require'    => 'uid不能为空',
        'content.require'    => '内容不能为空',
        'star.require'    => '星级不能为空',
        'order_no.require'    => '订单号不能为空',
    ];
}