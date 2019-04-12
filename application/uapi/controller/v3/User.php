<?php

namespace app\uapi\controller\v3;
use app\common\controller\Common;
use think\Request;
use think\Db;

class User extends Index{
    protected $config;
    private $assist;
    /**
     * 初始化
     * 检查请求类型，数据格式等
     */
    public function _initialize()
    {
        parent::_init();
        $this->assist = $this->obj->table('fwy_assist')->where('id = 1')->find();

    }


    /**
     * 注册接口
     *
     * @return \think\Response
     */
    public function add()
    {
        $post = $this->request->post();
        //参数验证
        $validate = new \app\uapi\validate\User;
        if(!$validate->check($this->request->post())){
            self::returnMsg('10004',$validate->getError());
        }
         //判断当前是否关闭注册功能
        if($this->config['is_reigster_lawyer'] != 1){
            self::returnMsg('10010','注册通道被关闭');
        }
        // 判断验证码是否正确
        if ($post['checkcode'] != session($this->request->module().'_code')) {
            self::returnMsg('10104','验证码错误');
        }
        unset($post['checkcode']);

        // 主表数据
        $data['nickname'] = 'LV' . mt_rand(1000, 9999);
        $data['username'] = $post['username'];
        $data['password'] = md5($post['password']);
        $data['phone'] = $post['username'];
        $data['status'] = $status = '1';
        $data['add_time'] = $add_time = time();

        // 子表数据
        $_data['nickname'] = $data['nickname'];
        $_data['username'] = $data['username'];
        $_data['password'] = $data['password'];
        $_data['phone'] = $data['phone'];
        $_data['face'] = '/Upload/default/face/img_'.rand(0,4).'.png';
        $_data['add_time'] = $data['add_time'];


        // 钱包表数据，

        $data_['coin'] = !empty($this->assist['reg_coin'])?$this->assist['reg_coin']:'0';
        $data_['wallet'] = !empty($this->assist['reg_wallet'])?$this->assist['reg_wallet']:'0';
        $data_['add_time'] = $_data['add_time'];
        $data_['status'] = '1';
        // 开启事务

        $this->obj->startTrans();
        // 写入主表
        $uid = Db::name('user')->insertGetId($data);
        // 写入子表
        $_data['uid'] = $uid;
        $member = $this->obj->table('fwy_member')->insert($_data);
        // 写入钱包表
        $data_['uid'] = $uid;
        $wallet = Db::name('lawyer')->insert($data_);

        if ($uid && $member && $wallet)
        {
            // 成功后自动登录
            $where['username'] = $data['username'];
            $where['password'] = $data['password'];
            $where['status'] = '1';
            $field = 'id,phone,username';
            unset($_data,$data_);
            $this->obj->commit(); //提交事务
            return $this->_login($field, $where);
        }else{
            $this->obj->rollback(); //回滚事务
            self::returnMsg('10105','注册失败');
        }
    }

    /**
     * 登录接口
     *
     * @return 用户信息
     */
    public function login(){
        $post = $this->request->post();
        $where['username'] = $post['username'];
        $where['password'] = md5($post['password']);
        $where['status'] = '1';
        $field = 'id,phone,username';
        $this->_login($field, $where);
    }

    /**
     *登录/注册调用接口
     *
     * @return 用户信息
     */
    private function _login($field = '', $where = '')
    {

        $fields = 'id,uid,phone,username,uid,nickname,isvip,vipdie_time,sex,province,city,area,detail,status,face';
        $fields = isset($field)?$fields:$field.'uid,nickname,isvip,vipdie_time,sex,province,city,area,detail,status,face';
        $res = $this->obj->table('fwy_member')->field($fields)->where($where)->find();
        //如果字表没有账号信息进入总表查询
        if (!$res)
        {
            $where['username'] = $where['username'];unset($where['phone']);
            $res = Db::table('os_user')->where($where)->find();
            if (!$res)
            {
                self::returnMsg('10106','登录失败，账号密码错误！');
            }else{
                $where['id'] = $res['id'];
                $cki = $this->check_identity($this->obj,$where); unset($where);
                if ($cki == false) {
                    self::returnMsg('10106','登录失败，账号异常请联系客服！');
                } else {
                    $this->updUser($res,0);
                }
            }
        }else{
            $this->updUser($res,1);
        }
    }

     /**
     * auth YW
     * note 退出登录
     * date 2018-12-27
     */
    public function logout()
    {
        $post = $this->request->post();
        $where['uid'] = $post['uid'];
        $this->obj->table('fwy_member')->where($where)->setField('online', '0');
        self::returnMsg('10000','退出登录成功');
    }


    /**
     * note:获取，修改用户信息
     * auth:杨炜
     * date:2018/01/25
     * return: bool
     */
    private function updUser($res,$status)
    {
        $uid = $status?$res['uid']:$res['id'];
        $where['uid'] = $uid;
        $user = Db::name('lawyer')->where($where)->field('coin,wallet')->find();unset($where);

        $res['weburl'] = $this->config['weburl'];
        $res['coin'] = $user['coin'];
        $res['wallet'] = $user['wallet'];
        $res['token'] = $data['token'] = makeToken();
        $where['id'] =  $uid;
        cookie('user',array('uid' => $uid,'token' => $res['token']));
        //更新token
        $update = Db::table('os_user')->where($where)->update($data);
        if ($update)
        {
            self::returnMsgAndToken('10000','登录成功',$res);
        }else{
            self::returnMsg('10106','登录错误：账号信息同步失败！',$res);
        }
    }
    /**
    * note:验证用户身份，并交换信息
    * auth:杨炜
    * date:2018/01/25
    * input: $obj模型对象 $where 条件
    * return: bool
    */
    function check_identity($obj, $where = '')
    {
        $face = '/Upload/default/face/img_'.rand(0,4).'.png';
        $res = Db::table('os_user')->field('*')->where($where)->find();
        if (!$res) return false;
        $data['uid'] = $res['id'];
        $data['username'] = $res['phone'];
        $data['nickname'] = $res['nickname'];
        $data['phone'] = $res['phone'];
        $data['password'] = $res['password'];
        $data['sex'] = $res['sex'];
        $data['add_time'] = time();
        $data['face'] = $face;
        $res = $obj->table('fwy_member')->insert($data);
        if ($res) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * note:验证码
     * auth:YW
     * date:2018/12/14
     * 手机号[username]，行为[do，[reg[注册],edit[修改密码],reset[忘记密码],replace[换绑手机号]]]
     */
    public function getcode()
    {

        $post = $this->request->post();
        if ($post)
        {
            $data['phone'] = $post['username'];
            $code = randCode(6);
            session($this->request->module().'_code',$code);
            switch ($post['do'])
            {
                case 'reg':         //注册
                    $where['phone'] = $post['username'];
                    $res = Db::table('os_user')->where($where)->find();
                    if ($res)
                    {
                        return self::returnMsg('10015','该手机号已经被注册');
                    }
                    $data['tpl_id'] = '120828';
                    $data['tpl_value'] = "#code#=$code";
                    break;
                case 'reset':       //忘记，找回密码
                    $data['tpl_id'] = '120823';
                    $data['tpl_value'] = "#code#=$code";
                    break;
                case 'edit':        //修改密码
                    $data['tpl_id'] = '120824';
                    $data['tpl_value'] = "#code#=$code";
                    break;
                case 'replace';     //换绑手机
                    $data['tpl_id'] = '120825';
                    $data['tpl_value'] = "#code#=$code";
                    break;
                case 'untying';       //解绑手机
                    $data['tpl_id'] = '147583';
                    $data['tpl_value'] = "#code#=$code";
                    break;
                default:
                    return self::returnMsg('10011','暂时不支持其他方式');
                    break;

            }
            $config = $this->obj->table('fwy_sms_config')->where('status','1')->find();
            //$res = JhSms($data,$config);
            $res = true;

            if ($res)
            {
                self::returnMsg('10000','验证码发送成功',$code);
            }else{
                self::returnMsg('10011','验证码发送失败',$res);
            }

        }else{
            self::returnMsg('10004','验证码发送失败');

        }

    }

    /**
     * note:协议信息
     * auth:YW
     * date:2018/12/27
     * 会员id[uid]，token[token]，flag
     */
    public function agree()
    {
        $post = $this->request->post();
        $where['status'] = '1';
        $where['flag'] = $post['flag'];
        $res = $this->obj->table('fwy_agreement')->where($where)->find();
        if ($res) {
            self::returnMsgAndToken('10000','',$res);
        } else {
            self::returnMsgAndToken('10001');
        }
    }


    /**
     * note:反馈意见
     * auth:YW
     * date:2018/12/27
     * 会员id[uid]，token[token]，反馈类型[type,[1系统问题,0投诉律师(lid)]]，内容：[content]，图片[images],终端[tag[user用户，lawyer律师]],联系方式[phone]
     */
    public function complaint()
    {
        $post = $this->request->post();
        if (!isset($post['content']) || empty($post['content']))
        {
            self::returnMsgAndToken('10004');
        }

        //查找之前的反馈有没有处理
        $where['uid'] = $post['uid'];
        $where['status'] = '0';
        $res = $this->obj->table('fwy_complaint')->where($where)->find();
        if (!$res)
        {
            $post['lid'] = $post['type'] == '0'?$post['lid']:'';
            $post['add_time'] = time();
            $post['status'] = '0';
            $post['content'] = strip_tags($post['content']);
            if ($_FILES)
            {
                //保存路径
                $path = $this->config['upload'].DS."complaint";
                //图片名称
                $obj = new Common();
                $this->config['field'] = 'images';
                $res = $obj->upload($path , $format = 'empty', $maxSize = '52428800', $this->config ,true);
                $post['images'] = $res;
            }
            //验证
            $validate = new \app\uapi\validate\Complaint();
            if(!$validate->check($post)){
                self::returnMsgAndToken('10004',$validate->getError());
            }
            unset($post['token']);
            $res = $this->obj->table('fwy_complaint')->insert($post);
            if ($res)
            {
                self::returnMsgAndToken('10000','反馈提交成功',$res);
            }else{
                self::returnMsgAndToken('10012','反馈提价失败，请联系客服吧');
            }
        }else{
            self::returnMsgAndToken('10011','您有一个反馈待处理，请联系客服吧');
        }
    }







}
