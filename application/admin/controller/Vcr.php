<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Db;

/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：视频管理
 */
class Vcr extends Init
{
    private $sms_config;
    private $tag_cn = array(
        'f' => '推荐',
        'h' => '热点',
    );

    /**
     * @auth YW
     * @date 2017.12.2
     * @purpose 初始化
     * @return void
     */
    public function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'].'lawyer_video';
        $where['status'] = '1';
        $where['type'] = 'Jhsms';
        $this->sms_config = $this->obj->table($this->config['prefix'].'sms_config')->where($where)->find();
    }

    public function index()
    {
        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $map['status'] = array('gt',-1);
        $where['where'] = $map;
        $this->_list('',$where);
        return view();
    }

    public function _filter(&$map)
    {
        $get = $this->request->get();
        if (!empty($get['begintime']) && !empty($get['endtime']))
        {
            $map['add_time'] = array('between',array(strtotime($get['begintime']),strtotime($get['endtime'])));
        }
        $this->checkSearch($map);
    }

    public function _after_list(&$list)
    {
        foreach ($list as $key => $value)
        {
            if ($value['local'] == '0')             //站内添加域名
            {
                $list[$key]['thumbnail'] = $this->config['weburl'].json_decode($value['thumbnail'],1);
                $list[$key]['path'] = $this->config['weburl'].json_decode($value['path'],1);
                $list[$key]['tag'] = $value['tag']?$value['tag']:'t';
            }
        }
    }

    public function _after_edit(&$list)
    {

        $list['tag'] = !empty($list['tag'])?explode(',',$list['tag']):array('t');

    }

    public function _before_update(&$post)
    {

        foreach ($post as $key => $value) {
            if ($value == '') {
                unset($post[$key]);
            }
        }

        $post['tag'] = implode(',', $post['tag']);
        if (intval(isset($post['review_status'])) > 1) $post['review_time'] = time();

    }

    public function _after_update(&$id)
    {
        $post = $this->request->post();
        $where['sid'] = $id;
        if ($post['review_status'] == '0')
        {
            $data['status'] = '0';
            $data['tag'] = implode(',', $post['tag']);
            $this->obj->table($this->config['prefix'].'article_main')->where($where)->update($data); unset($where);
        }elseif($post['review_status'] == 1){
            $data['status'] = '1';
            $data['tag'] = implode(',', $post['tag']);
            $this->obj->table($this->config['prefix'].'article_main')->where($where)->update($data); unset($where);
        }else{
            $data['status'] = '-1';
            $this->obj->table($this->config['prefix'].'article_main')->where($where)->update($data);
        }
        echoMsg('10000',$this->message['success']);
    }

    public function _before_forbid(&$data)
    {
        $data = 'review_status';
    }

    public function _after_forbid(&$id)
    {
        $this->obj->startTrans();
        $data['review_time'] = time();

        $where['id'] = $id;
        $res = $this->obj->table($this->table)->where($where)->update($data); unset($where);

        $where['sid'] = $id;
        $status = $this->obj->table('fwy_article_main')->where($where)->value('status');
        $status = $status?'0':'1';

        $id = editArticleMainId($this->obj->table('fwy_article_main'),$id,$status);
        if ($res && $id)
        {
            $this->obj->commit();
            echoMsg('10000',$this->message['success']);
        }else{
            $this->obj->rollback();
            echoMsg('10001',$this->message['error']);
        }
    }


    public function _after_delete($id)
    {
        delArticleMainId($this->obj->table('fwy_article_main'),$id);
        echoMsg('10000',$this->message['success']);
    }



    /**
     * @auth PT
     * @date 2019.3.1
     * @purpose 预览
     * @return void
     */

    public function preview()
    {
        $get = $this->request->request();
        $where['id'] = $get['id'];
        $res = $this->obj->table($this->table)->where($where)->find();
        $this->assign('vo', $res);
        return view();
    }
}