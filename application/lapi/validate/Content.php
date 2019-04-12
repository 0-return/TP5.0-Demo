<?php
namespace app\lapi\validate;
use think\Validate;
/**
 * Content验证
 */
class Content extends Validate
{

    protected $rule = [
        'content|内容'   =>  ['require'],
    ];

}