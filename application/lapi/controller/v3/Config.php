<?php
namespace app\lapi\controller\v3;
use app\common\controller\Common;
/**
 * Create by .
 * Cser Administrator
 * Time 11:53
 * Note 配置文件
 */
class Config extends Index implements Itf
{
    /**
     * auth YW
     * note 初始化
     * date 2018-08-06
     */
    public function _initialize()
    {
        parent::_init();
    }

    public function add()
    {
        // TODO: Implement add() method.
    }
    public function show()
    {
        $res = $this->obj->table('fwy_assist')->find();
        $data['assist'] = $res;
        self::returnMsg('10000','',$data);
    }
    public function showall()
    {
        // TODO: Implement showall() method.
    }
    public function edit()
    {
        // TODO: Implement edit() method.
    }
    public function serch()
    {
        // TODO: Implement serch() method.
    }
    public function del()
    {
        // TODO: Implement del() method.
    }
    public function delall()
    {
        // TODO: Implement delall() method.
    }
}