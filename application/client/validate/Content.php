<?php
namespace app\client\validate;
use think\Validate;
/**
 * User验证
 */
class Content extends Validate
{

    protected $rule = [
        'title|标题'   =>  ['require'],
        'content|内容'   =>  ['require'],
    ];

}