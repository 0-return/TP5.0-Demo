<?php
namespace app\lapi\controller\v3;
use app\common\controller\Send;
use think\Controller;
use think\Request;
use think\Db;

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
    public $db = 'DB_C1';
    private static $validate = array(            //排除验证的控制器方法
        'v3.user/agree',
        'v3.config/show',
        'v3.user/login',
        'v3.user/reset',
        'v3.user/add',
        'v3.user/getcode',
        'v3.content/showall',
        'v3.content/show',
        'v3.home/version',
        'v3.lawyer/question',
        'v3.adv/show',
        'v3.config/show',
        'v3.lawyer/goodstype',

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
                self::returnMsg('10004','not found uid and token!');
            }
            $where['token'] = $post['token'];
            $where['id'] = $post['uid'];
            if ($user = verifyToken(Db::table('os_user'),'',$where,$post,$this->get_config()))
            {
                $this->user = $user;
                $this->config = $this->get_config();
            }else{
                self::returnMsg('10101','验证失败！',$this->get_config());
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
    /**
     * auth YW
     * note 输出json串待uid,token
     * date 2018-08-23
     */
    /*protected function echoMsg($data)
    {
        $user = cookie('user');
        $data['uid'] = $user['uid'];
        $data['token'] = $user['token'];
        return self::returnMsg($data);
    }*/
    /**
     * auth YW
     * note 对用户钱包操作
     * date 2018-12-27
     */
    protected function wallet($data,$config,$avtive = 'setInc')
    {

        $obj = Db::table('os_user');
        $where['uid'] = $data['uid'];
        if ($data['total'] == 'coin' || $data['total'] == 'wallet')
        {
            if ($data['total'] == 'coin')
            {
                $data['total'] = intval($data['total']*$config['expcoin']);
            }
            $res = $avtive == 'setInc'?$obj->where($where)->setInc($data['payway'],$data['total']):$obj->where($where)->setDec($data['payway'],$data['total']);
        }else{
            $res = $avtive == 'setInc'?$obj->where($where)->setInc('wallet',$data['total']):$obj->where($where)->setDec('wallet',$data['total']);
        }
        return $res?true:false;
    }


}