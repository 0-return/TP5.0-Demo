<?php
namespace app\lapi\validate;
use think\Validate;
/**
 * User验证
 */
class Cert extends Validate
{

    protected $rule = [
        'practicelaw|事务所'   =>  ['require'],
    ];

}