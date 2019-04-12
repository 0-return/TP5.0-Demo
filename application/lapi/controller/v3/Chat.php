<?php
namespace app\lapi\controller\v3;
/**
 * auth YW
 * note 聊天接口
 * date 2018-08-06
 */
class Chat extends Index  implements Itf {
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


    /**
     * auth YW
     * note 删除聊天
     * date 2018-12-27
     * 会员id[uid]，token[token]，聊天id[id]多条记录格式为","拼接
     */
    public function del()
    {
        $post = $this->request->post();
        if (!isset($post['id']) || empty($post['id']))
        {
            self::returnMsgAndToken('10004');
        }
        $where['id'] = array('in',trim($post['id'],','));
        $where['formId'] = $post['uid'];
        $res = $this->obj->table('fwy_chatlog')->where($where)->setField('status','-1');
        if ($res)
        {
            $msg['data'] = array('id' => $post['id']);
            self::returnMsgAndToken('10000','删除成功！',$msg);
        }else{
            self::returnMsgAndToken('10010','删除失败！');
        }
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
        $where['type'] = '2';
        $res = $this->obj->table('fwy_quick_reply')->where($where)->select();
        if ($res)
        {
            self::returnMsgAndToken('10000','',$res);
        }else{
            self::returnMsgAndToken('10001');
        }
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
     * note 获取历史聊天
     * date 2018-12-27
     * 会员id[uid]，token[token]，分页基数[page]，每页数量[count]，开始时间[begintime]，结束时间[endtime]，聊天标识[chat_no]
     */
    public function show()
    {
        $post = $this->request->post();
        if ($post)
        {
            $page['page'] = isset($post['page']) && !empty($post['page'])?$post['page']:'1';
            $c = isset($post['count']) && !empty($post['count'])?$post['count']:5;
            $p = ($page['page']-1)*$c ;
            if (empty($post['begintime']))
            {
                if (!empty($post['endtime']))
                {
                    $begin = time();
                    $where['totime'] = array('between',array($begin,$post['end_time']));
                }
            }else{
                if (!empty($post['begin_time']) && !empty($post['end_time']))
                {
                    $end = $post['end_time'];
                    $where['toTime'] = array('between',array($post['begin_time'],$end));
                }
            }

            $where['chat_no'] = $post['chat_no'];
            $data['count'] = $this->obj->table('fwy_chatlog')->where($where)->count();
            $res = $this->obj->table('fwy_chatlog')->where($where)->order('add_time desc')->limit($p,$c)->select();unset($where);
            foreach ($res as $key => $value)
            {
                //获取对方的头像
                $where['uid'] = $value['toid'];
                $user = $this->obj->table('fwy_member')->field('nickname,face')->where($where)->find();
                echo $this->obj->getLastSql();
                $res[$key]['nickname'] = $user['nickname'];
                $res[$key]['face'] = $user['face'];
                $res[$key]['weburl'] = $this->config['weburl'];
            }
            $data['list'] = $res;
            if ($res)
            {
                self::returnMsgAndToken('10000','',$data);
            }else{
                self::returnMsgAndToken('10001','没有找到相关数据！');
            }
        }else{
            self::returnMsgAndToken('10004');
        }
    }
    /**
     * auth YW
     * note 已读处理
     * date 2018-12-27
     * 会员id[uid]，token[token]，
     */
    public function readchat()
    {

        $post = $this->request->post();
        if (!isset($post['id']) || empty($post['id']))
        {
            self::returnMsgAndToken('10004');
        }
        $where['id'] = array('in',trim($post['id'],','));
        $where['formId'] = $post['uid'];
        $res = $this->obj->table('fwy_chatlog')->where($where)->setField('isRead','1');
        if ($res)
        {
            $msg['data'] = array('id' => $post['id']);
            self::returnMsgAndToken('10000','',$msg);
        }else{
            self::returnMsgAndToken('10010');
        }
    }

    /**
     * auth YW
     * note 获取消息列表
     * date 2018-12-27
     * 会员id[uid]，token[token]，
     */
    public function showall()
    {
        $post = $this->request->post();
        //获取未完成订单
        $where['lid'] = $post['uid'];
        $where['deliver'] = array('in','0,2,3');
        $res = $this->obj->table('fwy_order')->field('uid,lid,order_no')->where($where)->select();unset($where);
        if ($res)
        {
            foreach ($res as $key => $value)
            {
                $where['uid'] = $value['uid'];
                $user = $this->obj->table('fwy_member')->field('face,nickname')->where($where)->find();

                $user['port'] = 'fwy';
                $user['weburl'] = $this->config['weburl'];
                $res[$key] = array_merge($value,$user);
            }
        }
        $data['not_end'] = $res;
        unset($where);
        //获取已完成订单
        $where['lid'] = $post['uid'];
        $where['receive'] = '1';
        $res = $this->obj->table('fwy_order')->field('uid,lid,order_no')->where($where)->select();unset($where);
        if ($res)
        {
            foreach ($res as $key => $value)
            {
                $where['uid'] = $value['uid'];
                $user = $this->obj->table('fwy_member')->field('face,nickname')->where($where)->find();

                $user['port'] = 'fwy';
                $user['weburl'] = $this->config['weburl'];
                $res[$key] = array_merge($value,$user);
            }
        }
        unset($where);
        $data['is_end'] = $res;
        if ($data)
        {
            self::returnMsgAndToken('10000','',$data);
        }else{
            self::returnMsgAndToken('10001','没有找到相关数据！');
        }
    }

    /**
     * auth YW
     * note 进入聊天时验证订单是否结束（排除退款订单）
     * date 2018-12-27
     * 会员id[uid]，token[token]，
     */
    public function checkline()
    {
        $post = $this->request->post();
        $where['order_no'] = $post['order_no'];
        $where['receive'] = '1';
        $where['refund'] = '0';
        $where['status'] = '1';
        $where['ustatus'] = '2';
        $where['create'] = '1';
        $res = $this->obj->table('fwy_order')->where($where)->find();
        if ($res)
        {
            self::returnMsgAndToken('10010','对方已结束订单！');
        }else{
            self::returnMsgAndToken('10000');
        }
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