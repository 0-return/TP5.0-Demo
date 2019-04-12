<?php
namespace app\uapi\controller\v3;

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
class Index extends Controller
{
    use Send;
    public $user;                        //用户信息
    protected $config;                   //配置文件（支付信息，系统配置，个人配置，等等）
    protected $obj;                        //数据库对象
    public $request;
    public $db = 'DB_C1';
    private static $validate = array(            //排除验证的控制器方法
        'v3.user/login',
        'v3.user/reset',
        'v3.user/add',
        'v3.user/agree',
        'v3.user/getcode',
        'v3.agreement/showbyid',
        'v3.advertisement/showbyid',
        'v3.home/indextopadv',
        'v3.home/classroom',
        'v3.home/changeonce',
        'v3.home/governmentaffairs',
        'v3.tool/get_active',
        'v3.tool/getdata',
        'v3.find/gettype',
        'v3.find/getdatabyid',
        'v3.find/getdetail',
        'v3.order/alipayrefund',
        'v3.order/wxpdayrefun',
        'v3.order/alipayrechargerefund',
        'v3.order/wxpayrechargerefund',
        'v3.goods/newgetgoods',
        'v3.goods/newgethtgoodsbyid',
        'v3.goods/newgethtdetailbyid',
        'v3.goods/newgetgoodsbyid',
        'v3.goods/newgetdetailbyid',
        'v3.goods/newgetallgoods',
        'v3.goods/newgetdoc',
        'v3.question/showbyid',
        'v3.question/showallbyid',
        'v3.question/showallbylid',
        'v3.question/showquestion',
        'v3.question/showall',
        'v3.question/showother',
        'v3.home/version',
        'v3.home/showall',
        'v3.config/show',
        'v3.lawyer/showall',
        'v3.find/globalsearch',
        'v3.msg/sysmsg',
        'v3.special/index',
        'v3.special/getbyflag',
        'v3.member/forget',
        'v3.find/share',

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
        if (!in_array(strtolower($this->request->controller() . '/' . $this->request->action()), self::$validate)) {
            $post = $this->request->post();
            if (empty($post['token']) || empty($post['uid'])) {
                self::returnMsg('10004', '缺少参数');
            }
            $where['token'] = $post['token'];
            $where['id'] = $post['uid'];
            if ($user = verifyToken(Db::table('os_user'), '', $where, $post, $this->get_config())) {
                $this->user = $user;
                $this->config = $this->get_config();
            } else {
                self::returnMsg('10101', '验证失败！', $this->get_config());
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
