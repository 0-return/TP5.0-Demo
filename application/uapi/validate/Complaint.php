<?php
namespace app\uapi\validate;
use think\Validate;
/**
 * User验证
 */
class Complaint extends Validate
{

    protected $rule = [
        'content|反馈内容'   =>  ['require'],
    ];

}