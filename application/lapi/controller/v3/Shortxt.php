<?php
namespace app\lapi\controller\v3;
use app\common\controller\Common;

/**
 * auth YW
 * note 段文章
 * date 2018-08-06
 */
class Shortxt extends Index  implements Itf {

    /**
     * auth YW
     * note 初始化
     * date 2018-08-06
     */
    public function _initialize()
    {
        parent::_init();
    }
    /**
     * auth YW
     * note 添加文章
     * date 2019-02-21
     */
    public function add()
    {
        $post = $this->request->post();
        //$validate = new \app\lapi\validate\Content();
        /*if (!$validate->check($post))
        {
            return self::returnMsgAndToken('10004',$validate->getError());
        }*/

        if ($_FILES)
        {
            //保存路径
            $path = ROOT_PATH.$this->config['upload'].DS."shortxt";
            //图片名称
            $obj = new Common();
            $this->config['field'] = 'thumbnail';
            $res = $obj->upload($path , $format = 'empty', $maxSize = '52428800', $this->config ,true);
            $data['thumbnail'] = $res;
        }

        $this->obj->startTrans();
        $data['uid'] = $post['uid'];
        $data['content'] = $post['content'];
        $data['add_time'] = time();
        $data['status'] = '1';
        $data['tag'] = 'f';
        $res = $this->obj->table('fwy_lawyer_shortxt')->insert($data);

        $id = addArticleMainId($this->obj->table('fwy_article_main'),'lawyer_shortxt');

        if ($res && $id)
        {
            $this->obj->commit();
            self::returnMsgAndToken('10000','发表成功',$res);
        }else{
            $this->obj->rollback();
            self::returnMsgAndToken('10010','发表失败');
        }
        unset($data,$data_);
    }



    public function show()
    {
        $post = $this->request->post();
        $table = $post['type'] == '1'?$this->fwy_lawyer_content:$this->fwy_lawyer_shortxt;
        $where['id'] = $post['id'];
        $where['status'] = '1';
        $res = $this->obj->table($table)->where($where)->find();
        if ($res)
        {
            $res['thumbnail'] = isset($res['thumbnail']) && !empty($res['thumbnail'])?json_decode($res['thumbnail'],1):'';
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

        $data['count'] = 10;
        $where['uid'] = $post['uid'];
        $res = $this->obj->field("id,uid,title,thumbnail,content,add_time,review_status,1 cate,history_comment_count,histort_reward_count")
            ->table("fwy_lawyer_content")
            ->union(["SELECT id,uid,title,thumbnail,content,add_time,status as review_status, 0 cate,history_comment_count,histort_reward_count FROM fwy_lawyer_shortxt  WHERE uid = {$post['uid']}"],true)
            ->where($where)
            ->limit($p,$c)
            ->order('add_time desc')
            ->select();
        if ($res)
        {
            foreach ($res as $key => $value)
            {
                $res[$key]['thumbnail'] = isset($value['thumbnail']) && !empty($value['thumbnail'])?json_decode($value['thumbnail'],1):'';
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
     * note 删除
     * date 2019-03-27
     */
    public function del()
    {
        $post = $this->request->post();
        $where['id'] = $post['id'];
        $this->obj->startTrans();
        $res = $this->obj->table('fwy_lawyer_shortxt')->where($where)->update(array('status' => '-1'));

        $id = delArticleMainId($this->obj->table('fwy_article_main'),$post['id']);
        if ($res && $id)
        {
            $this->obj->commit();
            self::returnMsgAndToken('10000','删除成功');
        }else{
            $this->obj->rollback();
            self::returnMsgAndToken('10010','删除失败');
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