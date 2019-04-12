<?php
namespace app\client\controller\v3;
/**
 * auth YW
 * note 主页（应用首页）
 * date 2018-08-06
 */
class Video extends Index implements Itf {
    private $table = 'fwy_lawyer_video';
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
     * note 文章详情
     * date 2019-03-21
     */
    public function show()
    {
        $post = $this->request->post();
        $where['id'] = $post['id'];
        $where['status'] = '1';
        $res = $this->obj->table($this->table)->where($where)->find();
        if ($res)
        {
            $res['thumbnail'] = isset($res['thumbnail']) && !empty($res['thumbnail'])?json_decode($res['thumbnail'],1):'';
            $res['path'] = isset($res['path']) && !empty($res['path'])?json_decode($res['path'],1):'';

            $res['weburl'] = $this->config['weburl'];
            self::returnMsg('10000','获取成功',$res);
        }else{
            self::returnMsg('10001','暂无数据');
        }
    }
    /**
     * auth YW
     * note 文章列表
     * date 2019-03-21
     */
    public function showall()
    {
        $post = $this->request->post();
        $page['page'] = isset($post['page']) && !empty($post['page'])?$post['page']:'1';
        $c = isset($post['count']) && !empty($post['count'])?$post['count']:5;
        $p = ($page['page']-1)*$c ;
        /**关键字*/
        if (isset($post['title'])) $where['title'] = array('like',"%{$post['title']}%");

        $where['status'] = '1';
        $count = $this->obj->table($this->table)->where($where)->count();
        $res = $this->obj->table($this->table)->where($where)->limit($p,$c)->order('add_time desc')->select();
        if ($res)
        {
            foreach ($res as $key => $value)
            {
                $res[$key]['thumbnail'] = isset($res['thumbnail']) && !empty($res['thumbnail'])?json_decode($res['thumbnail'],1):'';
                $res[$key]['path'] = isset($res['path']) && !empty($res['path'])?json_decode($res['path'],1):'';
            }

            $data['weburl'] = $this->config['weburl'];
            $data['count'] = $count;
            $data['list'] = $res;
            self::returnMsg('10000','获取成功',$data);
        }else{
            self::returnMsg('10001','暂无数据');
        }
    }
    /**
     * auth YW
     * note 文章添加
     * date 2019-03-21
     */
    public function add()
    {
        if ($this->request->isPost())
        {
            $post = $this->request->post();

            $validate = new \app\client\validate\Content;
            if(!$validate->check($post)){
                return self::returnMsgAndToken('10002',$validate->getError());
            }

            $post['add_time'] = time();
            $res = $this->obj->table($this->table)->insert($post);
            if ($res)
            {
                return self::returnMsgAndToken('10000','文章添加成功');
            }else{
                return self::returnMsgAndToken('100010','文章添加失败');
            }
        }
    }
    /**
     * auth YW
     * note 文章删除（支持批量）
     * date 2019-03-21
     */
    public function del()
    {
        $post = $this->request->post();
        $ids = trim($post['id'],',');
        $where['id'] = array('in',$ids);
        $res = $this->obj->table($this->table)->where($where)->update(array('status' => '-1'));
        if ($res)
        {
            self::returnMsgAndToken('10000','删除成功！');
        }else{
            self::returnMsgAndToken('10010','删除失败！');
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
}