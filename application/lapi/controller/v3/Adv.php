<?php
namespace app\lapi\controller\v3;
/**
 * auth YW
 * note 主页（应用首页）
 * date 2018-08-06
 */
class Adv extends Index  implements Itf {

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

    public function del()
    {
        // TODO: Implement del() method.
    }

    public function delall()
    {
        // TODO: Implement delall() method.
    }

    public function edit()
    {
        // TODO: Implement edit() method.
    }
    /**
     * note:获取广告
     * auth:YW
     * date:2018/12/27
     * 标识[flag，参考后台]，端[port]
     */
    public function show()
    {
        $post = $this->request->post();
        $where['flag'] = $post['flag'];
        $where['status'] = '1';
        //获取分类
        $id = $this->obj->table("fwy_advtype")->where($where)->value('id');unset($where);
        $where['location'] = $id;       //广告类型
        $where['status'] = '1';         //状态

        //获取城市广告
        if (!empty($post['pro']))
        {
            $where['pro'] = $post['pro'];
        }else{
            $where['pro'] = '-1';
        }
        //获取城市广告
        if (!empty($post['city']))
        {
            $where['city'] = $post['city'];
        }else{
            $where['city'] = '-1';
        }
        //获取城市广告
        if (!empty($post['area']))
        {
            $where['area'] = $post['area'];
        }else{
            $where['area'] = '-1';
        }
        //用户端和律师端
        if (isset($post['port']) && !empty($post['port']))
        {
            $where['port'] = array('in',$post['port']);
        }

        $res = $this->obj->table("fwy_adv")->where($where)->select();
        if ($res)
        {
            foreach ($res as $key => $value)
            {
                $res[$key]['weburl'] = $this->config['weburl'];
            }
            self::returnMsgAndToken('10000','',$res);
        }else{

            $where['location'] = $id;       //广告类型
            $where['status'] = '1';         //状态
            $where['pro'] = '-1';
            $where['city'] = '-1';
            $where['area'] = '-1';
            $res = $this->obj->table("fwy_adv")->where($where)->select();
            foreach ($res as $key => $value)
            {
                $res[$key]['weburl'] = $this->config['weburl'];
            }
            if ($res)
            {
                self::returnMsgAndToken('10000','',$res);
            }else{

                self::returnMsgAndToken('10001','没有找到相关数据！');
            }
        }
    }

    public function showall()
    {
        // TODO: Implement showall() method.
    }

    public function serch()
    {
        // TODO: Implement serch() method.
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