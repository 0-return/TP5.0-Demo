<?php
namespace app\api_web\controller\v1;
use app\common\controller\Common;
use think\Request;
use think\Db;
/**
 * auth YW
 * note 获取零散信息
 * date 2019-04-13
 */
class Plug extends Index implements Itf
{

    /**
     * auth YW
     * note 初始化
     * date 2019-04-13
     */
    public function _initialize()
    {
        parent::_init();

    }
    /**
     * auth YW
     * note 获取导航
     * date 2019-04-13
     * param
     */
    public function nav()
    {

        $where['tid'] = -1;
        $where['status'] = array('gt','-1');
        $res = $this->obj[1]->table($this->config['prefix'].'article_cate')->where($where)->select();
        if ($res)
        {
            self::getSonNav($res);
        }

        self::returnMsg(10000,'',$res);

    }
    /**
     * auth YW
     * note 获取导航
     * date 2019-04-13
     *
     */
    private function getSonNav(&$res)
    {
        foreach ($res as $key => $value)
        {
            $where['tid'] = $value['id'];
            $where['status'] = array('gt','-1');
            $array = $this->obj[1]->table($this->config['prefix'].'article_cate')->where($where)->select();
            $res[$key]['son'] = $array;
            $res[$key]['url'] = $this->config['weburl'];
        }
        return $res;
    }
    /**
     * auth YW
     * note 根据后台标识获取协议
     * date 2019-04-13
     * param flag 协议标识
     */
    public function Agreement()
    {

        $post = $this->request->Post();
        $where['flag'] = $post['flag'];
        $where['status'] = array('gt',-1);
        $res = $this->obj[1]->table($this->config['prefix'].'agreement')->where($where)->find();
        if ($res)
        {
            $res['url'] = $this->config['weburl'];
            self::returnMsg(10000,'',$res);
        }else{
            self::returnMsg(10010,'没有找到数据');
        }

    }
    /**
     * auth YW
     * note 获取图片
     * date 2019-04-13
     * param flag 广告标识
     */
    public function Advert()
    {
        $post = $this->request->Post();
        $where['tag'] = $post['flag'];
        $where['status'] = array('gt',-1);
        $res = $this->obj[1]->table($this->config['prefix'].'advert')->where($where)->select();
        if ($res)
        {
            $res = array2addUrl($res,$this->config);
            self::returnMsg(10000,'',$res);
        }else{
            self::returnMsg(10010,'没有找到数据');
        }
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

    }
    /**
     * note:首页
     * auth:YW
     * date:2019/01/09
     * 会员id[uid]，token[token]，起始时间[begin]可选，结束时间[end]可选，
     */
    public function show()
    {

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