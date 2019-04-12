<?php

/**
 * Create by .
 * Cser Administrator
 * Time 15:07
 */
namespace app\index\behaviors;
use think\Controller;
use think\Cookie;
use think\Session;
class Login extends Controller
{
    private $cookie;
    public function run(&$param,$config)
    {
        $this->cookie = $param->module().'_info';
        if (!Cookie::has($this->cookie))
        {
            $this->error('你没有登陆，请登陆！',url($param->module().'/index/index'));
        }
        //验证token
        $this->checkToken($param->module(),$config);

    }
    /**
     * @auth YW
     * @date 2018.11.19
     * @purpose 验证token【弃用】
     * @return void
     */
    /*public function checkToken($module,$config)
    {
        $info = json_decode(Cookie::get("{$this->cookie}"),1);
        $where['token'] = $info['token'];
        $res = Db::table($config['prefix'].'user')->where($where)->field('username,id,token,roleid')->find();
        if ($res)
        {
            $res['token'] = makeToken();
            $upd = Db::table($config['prefix'].'user')->where('id',$res['id'])->setField(['token' => $res['token']]);
            if ($upd)
            {
                putUser($module,$res,$config);
            }
        }else{
            $this->error('登录已超时，请登陆',url($module.'/index/index'));
        }
    }*/
    /**
     * @auth YW
     * @date 2018.11.19
     * @purpose 验证token
     * @return void
     */
    /*public function checkToken($module,$config)
    {
        $info = json_decode(Cookie::get("{$this->cookie}"),1);
        $token = Cache::get('token');
        if ($info['token'] === $token)
        {
            $info['token'] = makeToken();
            putUser($module,$info,$config);
        }else{
            $this->error('登录已超时，请登陆',url($module.'/index/index'));
        }
    }*/

    /**
     * @auth YW
     * @date 2018.11.19
     * @purpose 验证token
     * @return void
     */
    public function checkToken($module,$config)
    {
        $info = json_decode(Cookie::get("{$this->cookie}"),1);
        $token = Session::get($module.'_token');
        if ($info['token'] === $token)
        {
            $info['token'] = makeToken();
            putUser($module,$info,$config);
        }else{
            $this->error('登录已超时，请登陆',url($module.'/index/index'));
        }
    }
}