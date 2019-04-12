<?php
namespace app\lapi\controller\v3;
/**
 * auth YW
 * note
 * date 2018-08-06
 */
class Goods extends Index  implements Itf {
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
     * auth YW
     * note 根据id获取所有行业
     * date 2019-01-11
     *
     */
    public function show()
    {
        $post = $this->request->post();
        if ($post)
        {
            $where['iid'] = $post['id'];
            $where['status'] = '1';
            $res = $this->obj->table('fwy_goods_type')->where($where)->select();
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
        }else{
            self::returnMsgAndToken('10001');
        }
    }
    /**
     * auth YW
     * note 获取所有行业
     * date 2019-01-11
     *
     */
    public function showall()
    {
        $post = $this->request->post();
        if ($post['type'] == 'all')
        {
            $res = self::getGt($iid = '0');
        }else{
            $where['iid'] = '0';
            $where['status'] = '1';
            $res = $this->obj->table('fwy_goods_type')->where($where)->select();
            foreach ($res as $key => $value)
            {
                $res[$key]['weburl'] = $this->config['weburl'];
            }
        }

        if ($res)
        {
            self::returnMsgAndToken('10000','',$res);
        }else{
            self::returnMsgAndToken('10001');
        }
    }
    /**
     * auth YW
     * note 获取所有行业
     * date 2019-01-11
     * 订单id[id]批量删除用','拼接
     */
    private function getGt($iid)
    {
        $where['iid'] = $iid;
        $where['status'] = '1';
        $res = $this->obj->table('fwy_goods_type')->where($where)->select();
        if ($res)
        {
            foreach ($res as $key => $value)
            {
                $res[$key]['weburl'] = $this->config['weburl'];
                $res[$key]['son'] = $this->getGt($value['id']);
            }
        }
        return $res;
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