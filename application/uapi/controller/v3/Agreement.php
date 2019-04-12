<?php
namespace app\uapi\controller\v3;
use think\Db;


class Agreement extends Index{
    protected $config;
      /**
     * 初始化
     *
     * @return \think\Response
     */
    public function _initialize()
    {
        parent::_init();

    }


    /*
     *note:获取协议信息
     *auth:PT
     *date:2019/01/25
     *flag(标识)
     */
    public function showbyid()
    {
        $post = $this->request->post();
        $where['status'] = '1';
        $where['flag'] = $post['flag'];
        $res = $this->obj->table('fwy_agreement')->where($where)->find();
        if ($res) {
        	self::returnMsg('10000','',$res);
        } else {
        	self::returnMsg('10001','没有找到相关数据！');
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
