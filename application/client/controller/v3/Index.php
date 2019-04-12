<?php
namespace app\client\controller\v3;
use app\common\controller\Send;
use think\Controller;
use think\Request;
use think\Db;

/**
 * @auth YW
 * @date 2018.01.12
 * @purpose 律师援助版pc端
 * @return json
 * @eg：http://localhost.back/index.php/Api/v3/user/login
 * @note：
 * @2018-08-23 修复入口重复执行bug，导致token验证失效。
 */
class Index extends Controller{

    use Send;
    public $user;                        //用户信息
    protected $config;                   //配置文件（支付信息，系统配置，个人配置，等等）
    protected $obj;                        //数据库对象
    public $request;
    public $db = 'DB_C1';
    private static $validate = array(            //排除验证的控制器方法
        'v3.user/login',
        'v3.user/verfiy',
        'v3.content/cate',
    );
    /**
     * auth YW
     * note 验证
     * date 2018-08-23
     * note 三种状态，登录，游客，特殊页面
     */
    public function _init()
    {

        $this->request = Request::instance();
        $this->obj = Db::connect(config("database.{$this->db}"));
        if (!in_array(strtolower($this->request->controller().'/'.$this->request->action()),self::$validate))
        {
            $post = $this->request->post();
            if (empty($post['token']) || empty($post['uid'])) {
                return self::returnMsg('10004','缺少参数');
            }
            $where['token'] = $post['token'];
            $where['id'] = $post['uid'];
            if ($user = verifyToken($this->obj,'fwy_lawyer',$where,$post,$this->get_config()))
            {
                $this->user = $user;
                $this->config = $this->get_config();
            }else{
                return self::returnMsg('10101','验证失败！',$this->get_config());
            }
        }
        $this->config = $this->get_config();
    }
    /**
     * auth YW
     * note 配置信息[可选]
     * date 2018-08-23
     */
    private function get_config()
    {
        $res = $this->obj->table('fwy_config')->find();
        $res['microtime'] = time();
        return $res;
    }



}