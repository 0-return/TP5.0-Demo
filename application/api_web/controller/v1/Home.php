<?php
namespace app\api_web\controller\v1;
use app\common\controller\Common;
use think\Request;
use think\Db;
/**
 * auth YW
 * note 主页（应用首页）
 * date 2018-08-06
 */
class Home extends Index implements Itf
{
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
     * note 获取文章
     * date 2019-04-13
     * param type 文章分类id
     * param page
     * param count
     */
    public function Article()
    {
        $post = $this->request->post();
        $page['page'] = isset($post['page']) && !empty($post['page'])?$post['page']:'1';
        $c = isset($post['count']) && !empty($post['count'])?$post['count']:10;
        $p = ($page['page']-1)*$c ;
        $where['type'] = $post['type'];
        $where['status'] = array('gt','0');
        $res = $this->obj[1]->table($this->config['prefix'].'article')->where($where)->limit($p,$c)->order('add_time desc')->select();
        if ($res)
        {
            $res = array2addUrl($res,$this->config);
            self::returnMsg(10000,'',$res);
        }else{
            self::returnMsg(10010,'没有找到数据');
        }
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
     * note 空操作
     * date 2018-08-06
     */
    public function _empty(){
        self::returnMsg('10107','操作不合法');
    }

}