<?php
namespace app\index\controller;
use think\Controller;
use think\Cookie;
use think\Db;
use think\Request;
class Index extends Controller
{

    private $module;
    private $config;
    private $table = 'db_member';
    /**
     * @auth YW
     * @date 2017.12.2
     * @purpose 初始化
     * @return void
     */
    public function __construct()
    {

        $request = Request::instance();
        $this->module = $request->module();
        $this->config = Db::table('db_website')->find();


    }

    public function index()
    {
        return view('index');
    }

    /**
     * @auth YW
     * @date 2017.12.4
     * @purpose 登录验证
     * @return void
     */
    public function login()
    {
        if ($_POST){
            $login = $this->checkLogin();
            if ($login){
                $info = json_decode(Cookie::get($this->module.'_info'),1);
                echoMsg('10000','登录成功',$info);
            }else{
                echoMsg('10001','账号或密码错误，请稍后',url('index/index'));
            }
        }else{
            echoMsg('10002','请输入登录信息',url('index/index'));
        }

    }
    /**
     * @auth YW
     * @date 2018.12.13
     * @purpose 忘记密码
     * @return void
     */
    public function reset()
    {

    }

    /**
     * @auth YW
     * @date 2017.12.2
     * @purpose 登录验证
     * @return bool
     */
    private function checkLogin()
    {
        $username = input('post.username/s');
        $password = input('post.password/s');

        if (!empty($username) && !empty($password)) {

            $where['phone'] = $username;
            $where['password'] = md5($password);
            $where['status'] = '1';             //正常用户
            $res = Db::table($this->table)->where($where)->field('id,active_status,user_status,username,num')->find();

            if ($res['active_status'] == '0' || $res['active_status'] == '')      //检测账号是否激活
            {
                echoMsg('10001','账号未激活，无法登录',url($this->module.'/index/index'));
            }

            if ($res['user_status'] == '0' || $res['user_status'] == '')            //检测账号是否锁定
            {

                echoMsg('10001','账号已锁定，无法登录',url($this->module.'/index/index'));

            }
            if ($res){
                $token = makeToken();
                //更新数据库信息
                $dt['token'] = $token;
                $dt['login_time'] = time();
                if (empty($res['num'])) $dt['num'] = '0';
                $upd = Db::table($this->table)->where('id',$res['id'])->update($dt);
                $num = Db::table($this->table)->where('id',$res['id'])->setInc('num',1);

                if ($upd && $num)
                {
                    $data = array(
                        'id' => $res['id'],
                        'username' => $res['username'],
                        'token' => $token,
                    );
                    putUser($this->module,$data,$this->config);
                    return true;
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * @auth YW
     * @date 2018.03.05
     * @purpose 注销登录
     * @return bool
     */
    function logout(){
        $data = array(
            'preip' => getIP(),
            'pretime' => time(),
        );
        $where['id'] = getUser($this->module)['id'];
        $res = Db::table($this->table)->where($where)->update($data);
        if ($res)
        {
            Cookie::delete('info',$this->module.'_');
            return $this->success('退出成功',url('index/index'));
        }
    }
}
