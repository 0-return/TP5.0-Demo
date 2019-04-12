<?php
namespace app\lapi\controller\v3;

/**
 * auth YW
 * note 文章
 * date 2018-08-06
 */
class Content extends Index  implements Itf {

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

    }

    public function show()
    {
        $post = $this->request->post();
        $where['id'] = $post['id'];

        switch ($post['type'])
        {
            case '0':
                $table = 'fwy_lawyer_shortxt';
                $res = $this->obj->table($table)->where($where)->find();
                $res['thumbnail'] = isset($res['thumbnail']) && !empty($res['thumbnail'])?json_decode($res['thumbnail'],1):'';
                break;
            case '1':
                $table = 'fwy_lawyer_content';
                $res = $this->obj->table($table)->where($where)->find();
                break;
            case '2':
                $table = 'fwy_lawyer_video';
                $res = $this->obj->table($table)->where($where)->find();
                $res['thumbnail'] = isset($res['thumbnail']) && !empty($res['thumbnail'])?json_decode($res['thumbnail'],1):'';
                $res['path'] = isset($res['path']) && !empty($res['path'])?json_decode($res['path'],1):'';
                break;
            case '3':
                $table = 'fwy_lawyer_audio';
                $res = $this->obj->table($table)->where($where)->find();
                $res['thumbnail'] = isset($res['thumbnail']) && !empty($res['thumbnail'])?json_decode($res['thumbnail'],1):'';
                $res['path'] = isset($res['path']) && !empty($res['path'])?json_decode($res['path'],1):'';
                break;
            default:
                self::returnMsgAndToken('10010','分类错误');
                break;
        }
        if ($res)
        {
            $res['weburl'] = $this->config['weburl'];
            self::returnMsgAndToken('10000','',$res);
        }else{
            self::returnMsgAndToken('10001','没有找到相关数据！');
        }
    }

    public function showall()
    {
        $post = $this->request->post();
        $page['page'] = isset($post['page']) && !empty($post['page'])?$post['page']:'1';
        $c = isset($post['count']) && !empty($post['count'])?$post['count']:10;
        $p = ($page['page']-1)*$c ;

        $where['uid'] = $post['uid'];
        $countArr  = $this->obj->query("
                   SELECT lcount,scount,vcount FROM (
                   SELECT count(*) as lcount,0 scount, 0 vcount FROM fwy_lawyer_content WHERE uid = {$post['uid']}
                   UNION ALL
                   SELECT 0 lcount,count(*) as scount,0 vcount FROM fwy_lawyer_shortxt WHERE uid = {$post['uid']}
                   UNION ALL
                   SELECT 0 lcount,0 scount,count(*) as cc FROM fwy_lawyer_video WHERE uid = {$post['uid']}
             ) as t");

        $data['count'] = $countArr[0]['lcount']+$countArr[1]['scount']+$countArr[2]['vcount'];
        $res = $this->obj->field("id,uid,title,thumbnail,content,'' path,add_time,review_status, 1 as cate,history_comment_count,histort_reward_count")
            ->table("fwy_lawyer_content")
            ->union(["SELECT id,uid,title,thumbnail,content,'' path,add_time,status as review_status ,0 as cate,history_comment_count,histort_reward_count FROM fwy_lawyer_shortxt WHERE uid = {$post['uid']}","SELECT id,uid,title,thumbnail,'' content,path,add_time,review_status,2 as cate,history_comment_count,histort_reward_count FROM fwy_lawyer_video WHERE uid = {$post['uid']}"],true)
            ->where($where)
            ->limit($p,$c)
            ->order('add_time desc')
            ->select();

        if ($res)
        {

            foreach ($res as $key => $value)
            {
                $res[$key]['thumbnail'] = isset($value['thumbnail']) && !empty($value['thumbnail'])?json_decode($value['thumbnail'],1):'';
                $res[$key]['content'] = isset($value['content']) && !empty($value['content'])?$value['content']:'';
                $res[$key]['path'] = isset($value['path']) && !empty($value['path'])?json_decode($value['path'],1):'';
                $res[$key]['weburl'] = $this->config['weburl'];
            }

            $data['weburl'] = $this->config['weburl'];
            $data['list'] = $res;
            self::returnMsgAndToken('10000','',$data);
        }else{
            self::returnMsgAndToken('10001','没有找到相关数据！');
        }
    }

    /**
     * auth YW
     * note 获取评论列表（长文章，短文章，视频）
     * date 2019-03-27
     */
    public function comment()
    {
        $post = $this->request->post();

        $page['page'] = isset($post['page']) && !empty($post['page'])?$post['page']:'1';
        $c = isset($post['count']) && !empty($post['count'])?$post['count']:10;
        $p = ($page['page']-1)*$c ;

        $where['id'] = $post['id'];
        $fields = 'id,uid,content,history_comment_count,histort_reward_count,add_time';
        switch ($post['type'])
        {
            case '0':       //短文章
                $table = 'fwy_lawyer_content_comment';
                $data['count'] = $this->obj->table($table)->where($where)->count();
                $data['list'] = $this->obj->table($table)->field($fields)->where($where)->limit($p,$c)->order('add_time desc')->select();
                break;
            case '1':       //长文章
                $table = 'fwy_lawyer_content_comment';
                $data['count'] = $this->obj->table($table)->where($where)->count();
                $data['list'] = $this->obj->table($table)->field($fields)->where($where)->limit($p,$c)->order('add_time desc')->select();
                break;
            case '2':       //视频
                $table = 'fwy_lawyer_video_comment';
                $data['count'] = $this->obj->table($table)->where($where)->count();
                $data['list'] = $this->obj->table($table)->field($fields)->where($where)->limit($p,$c)->order('add_time desc')->select();
                break;
            case '3':       //音频
                $table = 'fwy_lawyer_audio_comment';
                $data['count'] = $this->obj->table($table)->where($where)->count();
                $data['list'] = $this->obj->table($table)->field($fields)->where($where)->limit($p,$c)->order('add_time desc')->select();
                break;
        }
        unset($where);
        if ($data['list'])
        {
            foreach ($data['list'] as $key => $value)
            {
                $where['uid'] = $value['uid'];
                $user = $this->obj->table('fwy_member')->where($where)->field('nickname,face')->find();
                $data['list'][$key]['nickname'] = $user['nickname'];
                $data['list'][$key]['face'] = $user['face'];
                $data['list'][$key]['weburl'] = $this->config['weburl'];
            }
            self::returnMsgAndToken('10000','',$data);
        }else{
            self::returnMsgAndToken('10001');
        }
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