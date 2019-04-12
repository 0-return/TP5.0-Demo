<?php

/**
 * Create by .
 * Cser Administrator
 * Time 15:07
 */
namespace app\index\behaviors;
use think\Controller;
use think\Db;
use think\Config;
class Cfg extends Controller
{
    public function run(&$param)
    {
        $param = Db::table('db_website')->find();
        $config = Config::get('database');
        $param['prefix'] = $config['prefix'];
    }
}