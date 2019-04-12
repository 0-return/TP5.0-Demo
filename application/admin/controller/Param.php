<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Db;
/**
 * Created by PhpStorm.
 * User: EVOL
 * Date: 2018/10/27
 * Time: 17:11
 */

class Param extends Init
{
    function _initialize()
    {
        parent::_init();

    }

    public function index()
    {
        $config = self::config();
        $assist = self::assist();
        $this->assign('config',$config);
        $this->assign('assist',$assist);
        return view();
    }

    public function config()
    {
        if ($this->request->isPost())
        {
            $this->table = $this->config['prefix'].'config';
            $this->updateByAjax();
        }else{
            $res = $this->obj->table($this->config['prefix'].'config')->find();
            return $res;
        }

    }

    public function assist()
    {
        if ($this->request->isPost())
        {
            $this->table = $this->config['prefix'].'assist';
            $this->updateByAjax();
        }else{
            $res = $this->obj->table($this->config['prefix'].'assist')->find();
            return $res;
        }

    }


}