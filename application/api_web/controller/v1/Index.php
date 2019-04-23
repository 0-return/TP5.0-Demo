<?php
namespace app\api_web\controller\v1;
use app\common\controller\Send;
use think\Controller;
use think\Request;
use think\Db;
use think\Config;

/**
 * @auth YW
 * @date 2018.01.12
 * @purpose 接口入口
 * @return json
 * @eg：http://localhost.back/index.php/Api/v3/user/login
 * @note：日志记录，注册用户uid必须传递。
 * @2018-08-23 修复入口重复执行bug，导致token验证失效。
 */
class Index extends Controller{

    use Send;
    public $user;                        //用户信息
    protected $config;                   //配置文件（支付信息，系统配置，个人配置，等等）
    protected $obj;                        //数据库对象
    public $request;
    public $db;
    private static $validate = array(            //排除验证的控制器方法

    );
    /**
     * auth YW
     * note 入口验证
     * date 2018-08-23
     * note
     */
    public function _init()
    {

        $this->db = [1 => 'DB_C1',2 => 'DB_C2',];                           //数据库
        $this->request = Request::instance();
        $this->obj[1] = Db::connect(config("database.{$this->db[1]}"));
        $config = Config::get('database');
        $this->config['prefix'] = $config[$this->db[1]]['prefix'];
        $this->config = array_merge($this->get_config(),$this->config);

    }

    /**
     * auth YW
     * note 配置信息[可选]
     * date 2018-08-23
     */
    private function get_config()
    {
        $res = $this->obj[1]->table($this->config['prefix'].'config_system')->find();
        $res['microtime'] = time();
        return $res;
    }



}