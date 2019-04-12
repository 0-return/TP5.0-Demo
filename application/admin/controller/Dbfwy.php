<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use app\common\controller\Backup;
use think\Db;
use think\Config;
/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：数据库管理
 */
set_time_limit(0);
class Dbfwy extends Init
{
    private $private_config;
    private $db_arr = array(
        'db_auth',
        'db_user',
        'db_role',
    );
    function _initialize()
    {
        parent::_init();
        $this->private_config = Config::get('database');
    }

    /**
     * @auth YW
     * @date 2018.11.06
     * @purpose 权限管理
     * @return void
     */
    public function index()
    {
        $config = array_merge($this->private_config['DB_C1']);
        $obj = new Backup($config);
        $res = $obj->dataList();
        $this->assign('list',$res);
        return view();
    }
    /**
     * @auth YW
     * @date 2018.11.06
     * @purpose 添加数据表
     * @return void
     */
    public function add()
    {

    }
    /**
     * @auth YW
     * @date 2018.11.14
     * @purpose 获取数据库表信息
     * @return void
     */
    public function show()
    {
        $post = $this->request->post();
        if ($post)
        {
            $res = $this->obj->query("DESC {$post['table']}");
            if (!$res)
            {
                $this->assign('list',$res);
            }
        }
        $this->view();
    }
    /**
     * @auth YW
     * @date 2018.11.14
     * @purpose 编辑数据库信息
     * @return void
     */
    public function edit()
    {
        $post = $this->request->post();
    }

    /**
     * @auth YW
     * @date 2018.11.06
     * @purpose 数据库导出
     * @return void
     */
    public function export()
    {

        $obj = new Backup($this->private_config['DB_C1']);
        $res = $obj->dataList();
        $count = count($res);
        $i = 0;
        foreach ($res as $key => $value)
        {
            if ($obj->backup($value['name'],0) == false) $i++;
        }
        if ($count == $i)
        {
            echoMsg('10000',$this->message['success']);
        }else{
            echoMsg('10001',$this->message['error']);
            exit(0);
        }
    }
    /**
     * @auth YW
     * @date 2018.11.11
     * @purpose 清空数据表
     * @return void
     */
    public function clear()
    {
        $post = $this->request->post();
        $post['path'] = explode(',',trim($post['path'],','));
        $i = 0;
        foreach ($post['path'] as $value)
        {
            if (!in_array($value,$this->db_arr))
            {
                //开始清空操作
                $res = Db::query("TRUNCATE TABLE $value");
                if (!$res)
                {
                    $i++;
                }
            }
        }
        echoMsg('10000','成功清除了'.$i.'个表的数据');
    }
    /**
     * @auth YW
     * @date 2018.11.11
     * @purpose 删除数据表
     * @return void
     */
    public function del()
    {
        $post = $this->request->post();
        $res = $this->obj->query("DROP TABLE IF EXISTS {$post['table']}");
        if (!$res)
        {
            echoMsg('10000','success');
        }else{
            echoMsg('10001','error');
        }
    }
}