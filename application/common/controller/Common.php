<?php
namespace app\common\controller;
use app\common\controller\Aliapp;
use app\common\controller\Wxapp;
use think\Controller;
use JMessage\JMessage;
use JMessage\IM\User;
use think\Request;
use think\Db;

/*
 * 公共操作
 * */
class Common extends Controller
{
    protected $request;

    public function __construct()
    {
        $this->request = Request::instance();

    }

    /**
     * 处理过期的会员信息
     * @param
     * @return booler 返回ajax的json格式数据
     */
    public function checkvip($obj,$user = '',$path,$fileName)
    {

        $obj->startTrans();
        /*重置会员表会员信息*/
        $data['isvip'] = '0';
        $data['isFenpeilayer'] = '0';
        $data['vipDietime'] = '0';
        $data['lid'] = '0';
        $where['uid'] = $user['uid'];
        $set_m = $obj->table('fwy_member')->where($where)->save($data);unset($data,$where);

        /*更新律师表律师信息*/
        $data['endtime'] = time();
        $data['status'] = '0';
        $data['content'] = '系统停用';
        $where['uid'] = $user['uid'];
        $where['lid'] = $user['lid'];
        $where['status'] = '1';
        $set_l = $obj->table('fwy_memlawyer')->where($where)->save($data);unset($data,$where);
        if ($set_m && $set_l)
        {
            $obj->commit();
            file_put_contents($path.$fileName,'pong:'.date('Y-m-d H:i:s',time()).'-'.$user['uid'].'-处理成功'.PHP_EOL, FILE_APPEND);
        }else{
            $obj->rollback();
            file_put_contents($path.$fileName,'pong:'.date('Y-m-d H:i:s',time()).'-'.$user['uid'].'-处理失败'.PHP_EOL, FILE_APPEND);
        }
    }

    /**
     * 上传文件类型控制 此方法仅限ajax上传使用
     * @param  string $path 字符串 保存文件路径示例： /Upload/image/
     * @param  string $format 文件格式限制
     * @param  integer $maxSize 允许的上传文件最大值 52428800
     * @return booler 返回ajax的json格式数据
     */
    public function upload($path = 'file', $format = 'empty', $maxSize = '52428800',$config = '',$json = true)
    {

        $files = $this->request->file();
        if (is_array($files[$config['field']]))
        {
            foreach ($files[$config['field']] as $value)
            {
                $info = $value->validate(['size' => $maxSize,'ext'=>'jpg,png,gif,mp4'])->move($path);
                //$data[] = $info->getSaveName();
                //$data[] = $info->getPathName();
                $data[] = substr($info->getPathName(),strripos($info->getPathName(),DS."upload"));

            }
        }else{
            $info = $files[$config['field']]->validate(['size'=>$maxSize,'ext'=>'jpg,png,gif,mp4'])->move($path);
            //$data = $info->getSaveName();
            //$data = $info->getPathName();
            $data = substr($info->getPathName(),strripos($info->getPathName(),DS."upload"));
        }
        if ($data)
        {
            if ($json)
            {
                return json_encode($data);
            }else{
                return $data;
            }
        }
    }

    /**
     * auth YW
     * note 对用户钱包操作
     * @param  array $data 数据源
     * @param  array 配置文件
     * @param  string 操作行为
     * date 2018-12-27
     */
    public function wallet($data,$config,$avtive = 'setInc')
    {

        $obj = Db::table('os_lawyer');
        $where['uid'] = $data[$config['user_type']];

        if ($data['payway'] == 'coin' || $data['payway'] == 'wallet')
        {
            if ($data['payway'] == 'coin')
            {
                $data['total'] = intval($data['total']*$config['expcoin']);
            }

            $res = $avtive == 'setInc'?$obj->where($where)->setInc($data['payway'],$data['total']):$obj->where($where)->setDec($data['payway'],$data['total']);
        }else{
            $res = $avtive == 'setInc'?$obj->where($where)->setInc('wallet',$data['total']):$obj->where($where)->setDec('wallet',$data['total']);
        }
        return $res?true:false;
    }

    /**
     * @auth YW
     * @date 2018.11.28
     * @purpose 支付宝提现
     * @return void
     */
    public function payToAlipay($data, $config)
    {
        $obj = new Aliapp();
        return $obj->alipay_fund_transtoac_count_transfer($data,$config);

    }
    /**
     * @auth YW
     * @date 2018.11.28
     * @purpose 微信提现
     * @return void
     */
    public function payToWxpay($data,$config)
    {
        $obj = new Wxapp();
    }
    /**
     * @auth YW
     * @date 2018.11.28
     * @purpose 获取短信模板
     * @return void
     */
    public static function msgConf($obj,$config,&$post)
    {
        $modult_code = $obj->table($config['prefix'] . 'sms_jh_module')->where('id','=',$post['code_type'])->value('code');
        return $modult_code;
    }

    public function wLog($obj , $request = '', $data = '',$config = '',$content = '')
    {
        $controller = strtolower($request->controller());                            //获取控制器名称
        $action = strtolower($request->action());
        $mca = '/'.$controller.'/'.$action;
        $title = $obj->table('fwy_auth')->where('mca','=',$mca)->value('title');
        //区分用户操作

        $id = isset($request->request()['id'])?$request->request()['id']:'0';
        $id = is_array($id)?serialize($id):$id;

        $str = '['.$data['username'].']操作了'.$title.'-[编号：'.$id.']-[说明：'.$s = !empty($content)?$content:'无'.']';
        $data['uid'] = $data['uid'];
        $data['username'] = $data['username'];
        $data['describe'] = $str;
        $data['explain'] = $data['explain']?$data['explain']:'user';
        $data['ip'] = getIp();
        $data['content'] = json_encode($request->request());
        $data['mca'] = strtolower('/'.$controller.'/'.$action);
        $data['addtime'] = time();
        $data['status'] = '1';
        $table = $config['prefix'].'log_'.$data['explain'];

        try {
            /**执行插入操作*/
            $res = $obj->table($table)->insert($data);
            return $res?true:false;
        } catch (Exception $e) {
            /**捕捉异常，记录日志或其他的操作*/
            print $e->getMessage();
            file_put_contents('/log/log_error.txt',$e->getMessage().PHP_EOL,FILE_APPEND);
        } finally {
            /**插入出错后继续执行的代码。*/
            return true;
        }
    }

    /**
     * note:卡密状态处理
     * auth:杨炜
     * date:2019/01/09
     */
    public function checkCard($data)
    {
        $where['id'] = $data['id'];
        $d['activation'] = '2';
        $res = $this->obj->table('fwy_cardcode')->where($where)->update($d);
        return $res?true:false;
    }


    /**
     * @auth YW
     * @date 2019.03.23
     * @purpose 退款处理
     * @return void
     */
    public function refundToAlipay($data, $config)
    {
        $obj = new Aliapp();
        return $obj->alipay_trade_refund($data,$config);

    }
    /**
     * @auth YW
     * @date 2019.03.23
     * @purpose 退款处理
     * @return void
     */
    public function refundToWxpay($data,$config)
    {
        $obj = new Wxapp();

        return $obj->refund($data,$config);
    }




}
