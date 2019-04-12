<?php
namespace app\uapi\controller\v3;
use app\common\controller\Common;
use think\Request;
use think\Db;
/**
 * Create by .
 * Cser Administrator
 * Time 11:53
 * Note 配置文件
 */
class Chat extends Index
{

    /**
     * 初始化
     *
     * @return \think\Response
     */
    public function _initialize()
    {
        parent::_init();
    }


/**
     * auth YW
     * note 获取聊天快捷短语(配合ajax)
     * date 2018-12-27
     * 会员id[uid]，token[token]
     */
    public function getQuickChatList()
    {
        $post = $this->request->post();
        $where['content'] = array('like',"%{$post['keyword']}%");
        $where['type'] = '1';
        $res = $this->obj->table('fwy_quick_reply')->where($where)->select();
        if ($res)
        {
            self::returnMsgAndToken('10000','',$res);
        }else{
            self::returnMsgAndToken('10001');
        }
    }

    /**
     * auth YW
     * note 获取正在咨询订单列表
     * date 2018-12-27
     * 会员id[uid]，token[token]
     */
    public function chatlist()
    {
        $post = $this->request->post();
        if (!isset($post['uid'])) {
            self::returnMsgAndToken('10004');
        }
        $where['status'] = '1';
        $where['create'] = '1';
        $where['ustatus'] = '1';
        $where['uid'] = $post['uid'];
        $arr = $this->obj->table('fwy_order')->where($where)->order('add_time desc')->select();
        if ($arr) {
            foreach ($arr as $k => $v) {
                $w['uid'] = $v['lid'];
                $lawyer = $this->obj->table('fwy_lawyer')->where($w)->find();
                $arr[$k]['username'] = $lawyer['username'];
                $arr[$k]['weburl'] =$this->config['weburl'];
                $arr[$k]['face'] = $lawyer['face'];
            }
            self::returnMsgAndToken('10000','',$arr);
        }else{
            self::returnMsgAndToken('10001','没有找到相关数据！');
        }
    }

}