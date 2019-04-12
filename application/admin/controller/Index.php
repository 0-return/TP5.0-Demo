<?php
namespace app\admin\controller;
use think\captcha\Captcha;
use think\Controller;
use think\Request;
use think\Cookie;
use think\Db;


class Index extends Controller
{
    private $module;
    private $config;
    private $table = 'db_admin_user';
    private $obj;
    protected $request;
    private $db;
    /**
     * @auth YW
     * @date 2017.12.2
     * @purpose 初始化
     * @return void
     */
    public function __construct()
    {
        $this->db = [1 => 'DB_C1',2 => 'DB_C2',];                           //数据库

        $this->request = Request::instance();
        $this->module = $this->request->module();
        $this->obj[1] = Db::connect(config("database.{$this->db[1]}"));
        $this->config = $this->obj[1]->table('db_config_system')->find();

    }


    /**
     * @auth YW
     * @date 2017.12.4
     * @purpose 后台登录
     * @return void
     */
    public function index()
    {
        return view();
    }


    /**
     * @auth YW
     * @date 2017.12.4
     * @purpose 登录验证
     * @return void
     */
    public function login()
    {
        if ($this->request->isPost()){
            $code = $this->checkCode();
            $login = $this->checkLogin();
            if ($login && $code){
                $post = $this->request->Post();
                $post['check'] == 'true'?Cookie::set('data',$post):Cookie::delete('data');
                return $this->success('登录成功，正在跳转...',url('user/welcome'));
            }else{
                exit($this->error('账号密码错误，请稍后'));
            }
        }else{
            exit($this->error('请输入登录信息，请稍后'));
        }
    }

    /**
     * @auth YW
     * @date 2017.12.2
     * @purpose 登录验证
     * @return bool
     */
    private function checkLogin()
    {
        $post = $this->request->Post();
        $username = $post['username'];
        $password = $post['password'];

        if (!empty($username) && !empty($password)) {

            $where['username'] = $username;
            $where['password'] = md5($password);
            $res = $this->obj[1]->table($this->table)->where($where)->field('id,username,roleid')->find();

            if ($res){
                $token = makeToken();
                //更新数据库信息
                $upd = $this->obj[1]->table($this->table)->where('id',$res['id'])->update(['token' => $token,'login_time' => time()]);
                $num = $this->obj[1]->table($this->table)->where('id',$res['id'])->setInc('num',1);
                if ($upd && $num)
                {
                    $data = array(
                        'id' => $res['id'],
                        'username' => $res['username'],
                        'roleid' => $res['roleid'],
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
     * @date 2017.12.2
     * @purpose 验证码
     * @return bool
     */
    private function checkCode($code = '',$id = '')
    {
        //return true;
        if (empty($code)) $code = input('post.code/s');

        $captcha = new Captcha();
        return $captcha->check($code, $id);
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
        $res = $this->obj[1]->table($this->table)->where($where)->update($data);
        if ($res)
        {
            Cookie::delete('info',$this->module.'_');
            Cookie::delete('data');
            return $this->success('退出成功',url('index/index'));
        }
    }

    /**
     * @auth YW
     * @date 2017.12.2
     * @purpose 验证码
     * @return bool
     */
    public function Verfiy()
    {
        $captcha = new Captcha();
        return $captcha->entry();
    }




}
