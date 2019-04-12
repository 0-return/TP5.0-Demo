<?php
namespace app\lapi\controller\v3;
use app\common\controller\Common;
use think\Db;
/**
 * auth YW
 * note 律师操作
 * date 2018-08-06
 */
class Lawyer extends Index implements Itf
{

    private $assist;
    /**
     * auth YW
     * note 初始化
     * date 2018-08-06
     */
    public function _initialize()
    {

        parent::_init();
        $this->assist = $this->obj->table('fwy_assist')->find();
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

    public function show()
    {
        // TODO: Implement show() method.
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
     * note 问题列表
     * date 2018-12-22
     * 会员id[uid]，token[token]，分页基数[page]，每页数量[count]，行业分类['industry_id'][可选]
     */
    public function question()
    {
        $post = $this->request->post();
        $page['page'] = isset($post['page']) && !empty($post['page'])?$post['page']:'1';
        $c = isset($post['count']) && !empty($post['count'])?$post['count']:5;
        $p = ($page['page']-1)*$c ;

        //根据行业id
        if (isset($post['goods_type_id']) && !empty($post['goods_type_id'])) $where['goods_type_id'] = $post['goods_type_id'];
        //律师id
        if ($post['uid']) $where['lid'] = array('LIKE', "%{$post['uid']}%");

        $where['status'] = array('IN', "-1,1,2");
        if (isset($post['uid']) && !empty($post['uid'])) {
            $res = $this->obj->table('fwy_question')->where($where)->order("add_time desc")->limit($p, $c)->select();
            foreach ($res as $key => $value) {
                $lid = explode(',', trim($value['lid'], ','));
                foreach ($lid as $k => $v) {
                    if ($v == $post['uid']) {
                        $res[$key] = $value;
                    }
                }
            }
        } else {
            $res = $this->obj->table('fwy_question')->where($where)->order("add_time desc")->limit($p, $c)->select();
        }

        $data['count'] = $this->obj->table('fwy_question')->where($where)->count();
        unset($where);

        for ($i = 0; $i < count($res); $i++) {
            $user = $this->obj->table('fwy_member')->where("uid='{$res[$i]['uid']}'")->find();

            //没有设置省市区
            if (empty($user['province']) || empty($user['province'])) {
                $res[$i]['address'] = "";
            } else {
                $res[$i]['address'] = $user['province'].$user['city'];
            }

            $where['pid'] = $res[$i]['id'];
            $where['uid'] = @reset(explode(',',trim($res[$i]['lid'],',')));
            $res[$i]['count'] = $this->obj->table('fwy_answer')->where($where)->count();
            $res[$i]['nickname'] = $user['nickname'];
            $res[$i]['weburl'] = $this->config['weburl'];
            $res[$i]['face'] = $user['face'];

            $name = $this->obj->table('fwy_goods_type')->where("id='{$res[$i]['goods_type_id']}'")->value("name");
            $res[$i]['title'] = $name;
        }
        $data['list'] = $res;
        if ($res) {

            self::returnMsgAndToken('10000','获取成功！',$data);
        } else {

            self::returnMsgAndToken('10010','暂无数据！');
        }

    }

    /**
     * auth YW
     * note 回答列表
     * date 2018-12-22
     * 会员id[uid]，token[token]，分页基数[page]，每页数量[count]
     */
    public function answer()
    {
        $post = $this->request->post();
        $page['page'] = isset($post['page']) && !empty($post['page'])?$post['page']:'1';
        $c = isset($post['count']) && !empty($post['count'])?$post['count']:5;
        $p = ($page['page']-1)*$c ;

        //获取回答
        $where['uid'] = $post['uid'];
        $where['pid'] = $post['id'];
        $data['a_count'] = $this->obj->table('fwy_answer')->where($where)->count();
        if ($data['a_count'] > 0)
        {
            $answer = $this->obj->table('fwy_answer')->where($where)->order("add_time desc")->page($p, $c)->select();unset($where);
        }


        //获取追问
        if ($post['mid'] != '')
        {
            $where['uid'] = $post['mid'];
            $where['pid'] = $post['id'];
            $data['q_count'] = $this->obj->table('fwy_answer')->where($where)->count();
            if ($data['q_count'])
            {
                $question = $this->obj->table('fwy_answer')->where($where)->order("add_time desc")->page($p, $c)->select();unset($where);
            }
        }

        $result = array_column($res = array_merge($question,$answer),'add_time');
        array_multisort($result,SORT_ASC,$res);

        for ($i = 0; $i < count($res); $i++) {
            $where['uid'] = $res[$i]['uid'];
            $law = $this->obj->table('fwy_lawyer')->where($where)->field("face,province,city,company,username,lawfirm_id")->find();unset($where);

            $res[$i]['address'] = $law['province'].$law['city'];
            $where['id'] = $law['lawfirm_id'];
            $res[$i]['lawfirm'] = $this->obj->table('fwy_lawfirm')->where($where)->getField('name');unset($where);

            $res[$i]['weburl'] = $this->config['weburl'];
            $res[$i]['face'] = $law['face'];
            $res[$i]['username'] = $law['username'];

            //获取追问
            $where['uid'] = $res[$i]['id'];
            $where['is_first_answer'] = '1';
            $answer = $this->obj->table('fwy_answer')->where($where)->select();
            $res[$i]['answer'] = $answer?$answer:'';
            unset($where);
        }
        if ($res) {
            self::returnMsgAndToken('10000','获取成功！',array('list' =>$res, 'count' => $data['a_count']+$data['q_count']));
        } else {
            self::returnMsgAndToken('10010','暂无数据！');
        }

    }
    /**
     * auth YW
     * note 回答问题[部分功能后续要弃用]
     * date 2018-12-24
     * 会员id[uid]，token[token]，问题id[id]，内容[content]
     */
    public function doanswer()
    {
        $post = $this->request->post();
        $post['lid'] = $post['uid'];
        $where['id'] = $post['id'];
        $question = $this->obj->table('fwy_question')->where($where)->field('lid')->find();unset($where);

        if ($question && $question['status'] == '2')
        {
            self::returnMsgAndToken('10011','这个问题已经被采纳了，回答失败！');
        }

        if (empty($question['lid']))                        //第一条回答
        {
            $question['lid'] = $post['uid'].',';
            $data['is_first_answer'] = '0';                 /*是否是第一条回答(0第一  ,1不是第一)*/
        }else{
            $where['pid'] = $post['id'];
            $where['uid'] = @reset(explode(',',trim($question['lid'],',')));
            $answer = $this->obj->table('fwy_answer')->where($where)->field('id')->find(); unset($where);
            $question['lid'] .= $post['lid'].',';
            $data['is_first_answer'] = '1';                 /*是否是第一条回答(0第一  ,1不是第一)*/
            $data['aid'] = $answer['id'];                   /*旧版本后续弃用*/
        }
        unset($data);
        $this->obj->startTrans();
        $where['id'] = $post['id'];
        $where['status'] = '1';
        $data['lid'] = trim($question['lid']);
        $save = $this->obj->table('fwy_question')->where($where)->update($data); unset($data);

        //添加回答信息到回答表
        $data['pid'] = $post['id'];
        $data['uid'] = $post['uid'];
        $data['content'] = $post['content'];
        $data['add_time'] = time();
        $data['user_type'] = "0";
        $res = $this->obj->table('fwy_answer')->insert($data);
        if($res && $save){
            $this->obj->commit();
            self::returnMsgAndToken('10000','回答成功！');
        }else{
            $this->obj->rollback();
            self::returnMsgAndToken('10010','回答失败！');
        }
    }

    /**
     * auth YW
     * note 空操作
     * date 2018-08-06
     */
    public function _empty()
    {
        $msg['code'] = '10103';
        $msg['msg'] = '操作不合法！';
        $msg['data'] = [];
        return $msg;
    }

}