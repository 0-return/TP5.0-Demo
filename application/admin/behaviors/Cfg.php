<?php

/**
 * Create by .
 * Cser Administrator
 * Time 15:07
 */
namespace app\admin\behaviors;
use think\Controller;
use think\Config;
class Cfg extends Controller
{
    public function run(&$obj , &$param)
    {

        $param = $obj->table('db_config_system')->find();
        $config = Config::get('database');
        $param['prefix'] = $config['DB_C1']['prefix'];
    }
}