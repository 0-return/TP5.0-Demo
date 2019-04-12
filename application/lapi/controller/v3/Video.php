<?php
namespace app\lapi\controller\v3;
use app\common\controller\Common;

/**
 * auth YW
 * note 视频
 * date 2018-08-06
 */
class Video extends Index  implements Itf {
    private $fwy_lawyer_video = 'fwy_lawyer_video';
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
     * note 视频信息
     * date 2019-03-27
     */
    public function show()
    {
        $post = $this->request->post();
        $where['uid'] = $post['uid'];
        $res = $this->obj->table($this->fwy_lawyer_video)->where($where)->fine();
        if ($res)
        {
            $res['thumbnail'] = isset($res['thumbnail']) && !empty($res['thumbnail'])?json_decode($res['thumbnail'],1):'';
            $res['path'] = isset($res['path']) && !empty($res['path'])?json_decode($res['path'],1):'';

            self::returnMsgAndToken('10000','',$res);
        }else{
            self::returnMsgAndToken('10001');
        }
    }
    /**
     * auth YW
     * note 视频列表
     * date 2019-03-27
     */
    public function showall()
    {
        $post = $this->request->post();
        $page['page'] = isset($post['page']) && !empty($post['page'])?$post['page']:'1';
        $c = isset($post['count']) && !empty($post['count'])?$post['count']:10;
        $p = ($page['page']-1)*$c;

        $where['uid'] = $post['uid'];
        $date['count'] = $this->obj->table($this->fwy_lawyer_video)->where($where)->count();
        $res = $this->obj->table($this->fwy_lawyer_video)->where($where)->limit($p,$c)->select();
        if ($res)
        {
            foreach ($res as $key => $value)
            {
                $res[$key]['thumbnail'] = isset($value['thumbnail']) && !empty($value['thumbnail'])?json_decode($value['thumbnail'],1):'';
                $res[$key]['path'] = isset($value['path']) && !empty($value['path'])?json_decode($value['path'],1):'';
                $res[$key]['cate'] = 2;
                $res[$key]['content'] = '';
                $res[$key]['weburl'] = $this->config['weburl'];
            }
            $date['weburl'] = $this->config['weburl'];
            $date['list'] = $res;
            self::returnMsgAndToken('10000','',$date);
        }else{
            self::returnMsgAndToken('10001');
        }
    }


    /**
     * auth YW
     * note 视频添加
     * date 2019-03-27
     */
    public function add()
    {
        $post = $this->request->post();
        $data['uid'] = $post['uid'];
        $data['add_time'] = time();
        $data['title'] = $post['title'];
        $where['uid'] = $post['uid'];
        $lv = $this->obj->table('fwy_lawyer')->where($where)->find();

        if ($lv['cert_type'] == '2') {
            return self::returnMsgAndToken('10011','你没有权限上传视频');
        }

        if (isset($_FILES['video']) && !empty($_FILES['video']))
        {
            //保存路径
            $path = ROOT_PATH.$this->config['upload'].DS.'lawyer_video'.DS.'video';
            //图片名称
            $obj = new Common();
            $this->config['field'] = 'video';
            $res = $obj->upload($path , $format = 'empty', $maxSize = '52428800', $this->config ,true);
            $data['path'] = $res;
        }

        if (isset($_FILES['thumbnail']) && !empty($_FILES['thumbnail']))
        {
            //保存路径
            $path = ROOT_PATH.$this->config['upload'].DS.'lawyer_video'.DS.'images';
            //图片名称
            $obj = new Common();
            $this->config['field'] = 'thumbnail';
            $res = $obj->upload($path , $format = 'empty', $maxSize = '52428800', $this->config ,true);
            $data['thumbnail'] = $res;
        }

        $this->obj->startTrans();
        $res = $this->obj->table('fwy_lawyer_video')->insert($data);
        $id = addArticleMainId($this->obj->table('fwy_article_main'),'lawyer_video');

        if ($res && $id) {
            $this->obj->commit();
            self::returnMsgAndToken('10000','发布成功');
        }else{
            $this->obj->commit();
            self::returnMsgAndToken('10014','发布失败');
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
        $res = $this->obj->table('fwy_lawyer_video')->where($where)->update(array('status' => '-1'));

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