<?php
namespace app\uapi\controller\v3;
use app\common\controller\Common;
use think\Request;
use think\Db;
/**
 * auth YW
 * note 消息版块
 * date 2018-08-06
 */
class Msg extends Index
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

    /**
     * note:获取系统消息
     * auth:YW
     * date:2019/01/09
     * 会员id[uid]，token[token]
     */
    public function sysmsg()
    {
        $where['status'] = '1';
        $where['user'] = array('neq','');
        $data['count'] = $this->obj->table('fwy_sys_msg')->where($where)->count();
        $data['list'] = $this->obj->table('fwy_sys_msg')->where($where)->select();
        if ($data['list'])
        {
            $this->returnMsgAndToken('10000','',$data);
        }else{

            $this->returnMsgAndToken('10001');
        }

    }

    /**
     * auth YW
     * note 空操作
     * date 2018-08-06
     */
    public function _empty(){
        self::returnMsg('10107','操作不合法');
    }
}