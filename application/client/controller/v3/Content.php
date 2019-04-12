<?php
namespace app\client\controller\v3;
/**
 * auth YW
 * note 文章
 * date 2018-08-06
 */
class Content extends Index  implements Itf {
    private $table = 'fwy_lawyer_content';
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
     * date date 2019-03-25
     */
    public function cate()
    {
        $field = array(
            'id' => 'id',
            'pid' => 'tid',
            'key' => 'id',
            'title' => 'name',
            'status' => 'status',
        );
        $option = cateTreeHtml($this->obj,'fwy_lawyer_content_type',$field,'-1');
        self::returnMsg('10000','获取成功',$option);
    }
    /**
     * auth YW
     * note 文章分类
     * date 2019-03-25
     */
    public function show()
    {
        $post = $this->request->post();
        $where['id'] = $post['id'];
        $where['status'] = array('in','0,1');
        $res = $this->obj->table($this->table)->where($where)->find();

        if ($res)
        {
            $temp = $res;
            $type_cn = self::getContentType($res);
            $data['type_cn'] = $type_cn;
            $data['weburl'] = $this->config['weburl'];
            $res = array_merge($data,$temp);
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

        $where['status'] = array('in','0,1');
        $count = $this->obj->table($this->table)->where($where)->count();
        $res = $this->obj->table($this->table)->where($where)->limit($p,$c)->order('add_time desc')->select();
        if ($res)
        {
            foreach ($res as $key => $value)
            {
                $res[$key]['type_cn'] = self::getContentType($value);
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
                return self::returnMsgAndToken('10004',$validate->getError());
            }

            $post['add_time'] = time();
            unset($post['token']);
            $this->obj->startTrans();
            $post['status'] = '1';
            $res = $this->obj->table($this->table)->insert($post);
            $id = addArticleMainId($this->obj->table('fwy_article_main'),'lawyer_content');
            if ($res && $id)
            {
                $this->obj->commit();
                return self::returnMsgAndToken('10000','文章添加成功');
            }else{
                $this->obj->rollback();
                return self::returnMsgAndToken('10010','文章添加失败');
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
        $this->obj->startTrans();
        $post = $this->request->post();
        $ids = trim($post['id'],',');
        $where['id'] = array('in',$ids);
        $res = $this->obj->table($this->table)->where($where)->update(array('status' => '-1'));

        $ids = explode(',',$ids);
        $count = count($ids);
        $i = 0;
        foreach ($ids as $key => $value)
        {
            $id = delArticleMainId($this->obj->table('fwy_article_main'),$value);
            if ($id)
            {
                $i++;
                $this->obj->commit();
            }else{
                $this->obj->rollback();
            }
        }
        if ($res && $count == $i)
        {
            self::returnMsgAndToken('10000','删除成功！');
        }else{
            if ($count > 1)
            {
                self::returnMsgAndToken('10010','部分内容删除失败！');
            }else{
                self::returnMsgAndToken('10010','删除失败！');
            }
        }
    }

    public function delall()
    {
        // TODO: Implement delall() method.
    }
    /**
     * auth YW
     * note 编辑
     * date 2019-03-21
     */
    public function edit()
    {
        if ($this->request->isPost())
        {
            $post = $this->request->post();
            $where['id'] = $post['id'];
            $validate = new \app\client\validate\Content;
            if(!$validate->check($post)){
                return self::returnMsgAndToken('10004',$validate->getError());
            }

            $post['edit_time'] = time();unset($post['uid'],$post['token']);
            $res = $this->obj->table($this->table)->where($where)->update($post);
            if ($res)
            {
                return self::returnMsgAndToken('10000','文章更新成功');
            }else{
                return self::returnMsgAndToken('100010','文章更新失败');
            }
        }
    }
    /**
     * auth YW
     * note 获取文章分类名称
     * date 2019-03-25
     */
    private function getContentType(&$res)
    {

        $res = $this->obj->table('fwy_lawyer_content_type')->where('id','=',$res['type'])->value('name');

        return $res;
    }

    public function serch()
    {
        // TODO: Implement serch() method.
    }
}