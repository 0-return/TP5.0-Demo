<?php

/**
 * Create by .
 * Cser Administrator
 * Time 15:07
 */
namespace app\admin\behaviors;
use think\Controller;
use think\Cookie;
use think\Db;
class Rbac extends Controller
{

    public function run(&$param)
    {
        $user = json_decode(Cookie::get($param->module().'_info'),1);
        $ids = getFields(Db::table('db_admin_role'),"id = {$user['roleid']}",array('fields'=>'ids','type' => 'value'));
        $ids_arr = explode(',',$ids);
        $ac = strtolower('/'.$param->controller().'/'.$param->action());
        //var_dump($ac);
        $res = getFields(Db::table('db_admin_auth'),"mca = '{$ac}' and is_check = 1",array('fields'=>'id,remark','type' => 'find'));
        if ($user['roleid'] != '1')
        {
            if (!in_array($res,$ids_arr))
            {
                $this->error('你没有权限，请联系管理员');
            }
        }
        //日志记录
        /*$behaviors = $param->request();
        if ($behaviors)
        {
            //操作日志
            $data['explain'] = 'auto';
            $data['uid'] = $user['id'];
            $data['describe'] = $res['remark'];
            $data['username'] = $user['username'];
            $data['mca'] = $ac;
            $data['content'] = json_encode($behaviors);
            $data['addtime'] = time();
            Db::table('db_log')->insert($data);
        }*/
    }

}