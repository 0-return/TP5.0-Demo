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
     * note 获取分类
     * date 2019-03-26
     *
     */
    public function goodsType()
    {
        $where['name_en'] = array('like',"%question%");
        $pid = $this->obj->table('fwy_goods_type')->where($where)->value('id'); unset($where);

        $where['iid'] = $pid;
        $res = $this->obj->table('fwy_goods_type')->where($where)->select();
        if ($res)
        {

            foreach ($res as $key => $value)
            {
                $res[$key]['weburl'] = $this->config['weburl'];
            }
            self::returnMsgAndToken('10000','',$res);
        }else{
            self::returnMsgAndToken('10001');
        }
    }

    /**
     * auth YW
     * note 问题列表
     * date 2018-12-22
     * 会员id[uid]，token[token]，分页基数[page]，每页数量[count]
     */
    public function question()
    {
        $post = $this->request->post();
        $page['page'] = isset($post['page']) && !empty($post['page'])?$post['page']:'1';
        $c = isset($post['count']) && !empty($post['count'])?$post['count']:5;
        $p = ($page['page']-1)*$c ;

        /**根据关键词查询*/
        if (isset($post['describe']) && !empty($post['describe'])) $where['describe'] = array('like',"%{$post['describe']}%");
        /**根据行业查询*/
        if (isset($post['goods_type_id']) && !empty($post['goods_type_id'])) $where['goods_type_id'] = $post['goods_type_id'];
        /**根据地区查询*/
        if (isset($post['province_cn']) && !empty($post['province_cn'])) $where['province_cn'] = array('like',"%{$post['province_cn']}%");
        if (isset($post['city_cn']) && !empty($post['city_cn'])) $where['city_cn'] = array('like',"%{$post['city_cn']}%");
        if (isset($post['area_cn']) && !empty($post['area_cn'])) $where['area_cn'] = array('like',"%{$post['area_cn']}%");
        /**根据采纳状况查询*/
        if (isset($post['status']) && !empty($post['status']))
        {
            $where['status'] = $post['status'];
        }else{
            $where['status'] = array('IN', "-1,1,2");
        }
        /**如果不为空，则为我的历史回答*/
        if (isset($post['uid']) && !empty($post['uid'])) $where['lid'] = array('LIKE', "%{$post['uid']}%");
        /**律师不能看到自己的留言问题*/
        if (isset($post['uid']) && !empty($post['uid'])) $where['uid'] = array('not in',$post['uid']);


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
        unset($post['uid'],$post['token']);
        $data['condition'] = $post;
        $data['list'] = $res;
        if ($res) {

            self::returnMsgAndToken('10000','',$data);
        } else {

            self::returnMsgAndToken('10001','没有找到相关数据！');
        }

    }


    /*public function answer()
    {
        $post = $this->request->post();
        $page['page'] = isset($post['page']) && !empty($post['page'])?$post['page']:'1';
        $c = isset($post['count']) && !empty($post['count'])?$post['count']:5;
        $p = ($page['page']-1)*$c ;


        //获取回答列表(获取第一条回答)
        $where['uid'] = $post['uid'];
        $where['pid'] = $post['id'];
        $where['is_first_answer'] = 0;
        $data['q_count'] = $this->obj->table('fwy_answer')->where($where)->count();
        $answer = $data['q_count'] > 0?$this->obj->table('fwy_answer')->where($where)->order("add_time asc")->find():[];

        //获取用户信息
        unset($where);
        $where['uid'] = $answer['uid'];
        $user = $this->obj->table('fwy_lawyer')->where($where)->field('username,face,province_cn,city_cn')->find();
        $answer = array_merge($answer,$user);
        unset($where);


        //获取回答列表(获取后续回答)
        $where['aid'] = $answer['id'];
        $where['is_first_answer'] = '1';
        $data['q_count'] = $this->obj->table('fwy_answer')->where($where)->count();
        $question = $data['q_count'] > 0?$this->obj->table('fwy_answer')->where($where)->order("add_time asc")->page($p, $c)->select():[];
        unset($where);

        foreach ($question as $key => $value)
        {
            $table = $value['user_type'] == 0?'fwy_lawyer':'fwy_member';
            $where['uid'] = $value['uid'];
            if ($value['user_type'] == 0)
            {
                $user = $this->obj->table($table)->where($where)->field('lawfirm_id,username,face,province_cn,city_cn')->find();
                $lawfirm = $this->obj->table('fwy_lawfirm')->where('id','=',$user['lawfirm_id'])->value('name');
                $value['lawfirm'] = $lawfirm;

            }else{
                $user = $this->obj->table($table)->where($where)->field('nickname as username,face,province_cn,city_cn')->find();
            }
            $temp[] = array_merge($value,$user);
        }
        $answer['answer'] = $temp;
        if ($answer) {
            self::returnMsgAndToken('10000','',array('list' =>$answer,'weburl' => $this->config['weburl'], 'count' => $data['q_count']));
        } else {
            self::returnMsgAndToken('10001','没有找到相关数据！');
        }

    }*/
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

        //获取回答列表
        $where['pid'] = $post['id'];
        $data['q_count'] = $this->obj->table('fwy_answer')->where($where)->count();
        $answer = $data['q_count'] > 0?$this->obj->table('fwy_answer')->where($where)->order("add_time asc")->select():[];
        unset($where);

        foreach ($answer as $key => $value)
        {
            $table = $value['user_type'] == 0?'fwy_lawyer':'fwy_member';
            $where['uid'] = $value['uid'];
            if ($value['user_type'] == 0)
            {
                $user = $this->obj->table($table)->where($where)->field('lawfirm_id,username,face,province_cn,city_cn')->find();
                $lawfirm = $this->obj->table('fwy_lawfirm')->where('id','=',$user['lawfirm_id'])->value('name');
                $value['lawfirm'] = $lawfirm;

            }else{
                $user = $this->obj->table($table)->where($where)->field('nickname as username,face,province_cn,city_cn')->find();
            }
            $res[] = array_merge($value,$user);
            unset($where);
        }

        //数据排序
        $is_first_answer_temp = '';
        $no_first_answer_temp = '';
        foreach ($res as $key => $value)
        {
            if ($value['is_first_answer'] == '0')
            {
                $is_first_answer_temp = $value;
            }else{
                $no_first_answer_temp[] = $value;
            }
        }
        $is_first_answer_temp['answer'] = $no_first_answer_temp;

        if ($answer) {
            self::returnMsgAndToken('10000','',array('list' => $is_first_answer_temp,'weburl' => $this->config['weburl'], 'count' => $data['q_count']));
        } else {
            self::returnMsgAndToken('10001','没有找到相关数据！');
        }

    }

    /**
     * auth YW
     * note 回答问题
     * date 2018-12-24
     * 会员id[uid]，token[token]，问题id[id]，内容[content]
     */
    public function doanswer()
    {
        $post = $this->request->post();
        $post['lid'] = $post['uid'];
        $where['id'] = $post['id'];
        $question = $this->obj->table('fwy_question')->where($where)->field('lid,status')->find();unset($where);

        if ($question && $question['status'] == '2')
        {
            self::returnMsgAndToken('10011','这个问题已经被采纳了，回答失败！');
        }

        if (empty($question['lid']))                        //第一条回答
        {
            $question['lid'] = $post['uid'].',';
            $data_['is_first_answer'] = '0';                 /*是否是第一条回答(0第一  ,1不是第一)*/
        }else{
            $where['pid'] = $post['id'];
            $where['uid'] = @reset(explode(',',trim($question['lid'],',')));
            $answer = $this->obj->table('fwy_answer')->where($where)->field('id')->find(); unset($where);
            $question['lid'] .= $post['lid'].',';
            $data_['is_first_answer'] = '1';                 /*是否是第一条回答(0第一  ,1不是第一)*/
            $data_['aid'] = $answer['id'];
        }

        $this->obj->startTrans();
        $where['id'] = $post['id'];
        $where['status'] = '1';
        $_data['lid'] = trim($question['lid']);
        $save = $this->obj->table('fwy_question')->where($where)->update($_data);

        //添加回答信息到回答表
        $data_['pid'] = $post['id'];
        $data_['uid'] = $post['uid'];
        $data_['content'] = $post['content'];
        $data_['add_time'] = time();
        $data_['user_type'] = "0";
        $res = $this->obj->table('fwy_answer')->insert($data_);
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
    public function _empty(){
        self::returnMsg('10107','操作不合法');
    }

}