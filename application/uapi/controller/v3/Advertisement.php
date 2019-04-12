<?php
namespace app\uapi\controller\v3;
use think\Db;
class Advertisement extends Index{
    protected $config;
      /**
     * 初始化
     *
     * @return \think\Response
     */
    public function _initialize()
    {
        parent::_init();
        $this->config =  $this->obj ->table('fwy_config')->where('id = 1')->find();
    }

	/**
     * note:根据id获取,首先获取区域广告，然后获取城市广告，然后获取省份广告，最后获取全国广告
     * auth:PT
     * date:2019/1/25
     * port 1律师端，2用户端，null全部
     * flag(广告位置标识)
     */
    public function showbyid()
    {
        $post = $this->request->post();
        $where['flag'] = $post['flag'];
        $where['status'] = '1';
        $obj=$this->obj;
        //获取分类
        $id = $obj->table("fwy_advtype")->where($where)->find();unset($where);
        $where['location'] = $id['id'];       //广告类型
        $where['status'] = '1';         //状态

        //获取城市广告
        if (!empty($post['pro']))
        {
            $where['pro'] = $post['pro'];
        }
        //获取城市广告
        if (!empty($post['city']))
        {
            $where['city'] = $post['city'];
        }
        //获取城市广告
        if (!empty($post['area']))
        {
            $where['area'] = $post['area'];
        }
        //用户端和律师端
        if (isset($post['port']) && !empty($post['port']))
        {
            $where['port'] = array('in',$post['port']);
        }

        $res = $obj->table("fwy_adv")->where($where)->select();
        if ($res)
        {
            $res = $this->resultFormat($res);
            self::returnMsg('10000','',$res);
        }else{

            $where['location'] = $id;       //广告类型
            $where['status'] = '1';         //状态
            $res = $obj->table("fwy_adv")->where($where)->select();
            $res = $this->resultFormat($res);
            if ($res)
            {
            	self::returnMsg('10000','',$res);
            }else{
            	self::returnMsg('10001','没有找到相关数据！');
            }
        }
    }
    /*
     *note:图片地址拼装
     *auth:PT
     *date:2019/1/25
     */
    public function resultFormat($result)
    {
        foreach ($result as $k => $v) {
          if (!empty($v['picture_path'])) {
              $a = unserialize($v['picture_path']);
              foreach ($a as $key => $value) {
                $a[$key] = $this->config['weburl'].$value;
              }
              $result[$k]['pic'] = $a;
              $result[$k]['picture_path'] = unserialize($v['picture_path']);
              $result[$k]['weburl'] = $this->config['weburl'];
          }

        }
        return $result;
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
