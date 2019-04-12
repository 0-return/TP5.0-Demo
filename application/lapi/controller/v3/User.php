<?php
namespace app\lapi\controller\v3;
use app\common\controller\Common;
use think\Db;
use Think\Exception;

/**
 * auth YW
 * note 用户管理（个人中心，用户信息，登录，编辑，添加，删除，等等）
 * date 2018-08-06
 */
class User extends Index implements Itf {


    /**
     * auth YW
     * note 初始化
     * date 2018-08-06
     */
    public function _initialize()
    {
        parent::_init();
    }


    /**
     * auth YW
     * note 登录
     * date 2018-08-06
     */
    public function login($data = '') {

        $post = $this->request->post();
        $where['phone'] = isset($data['username']) && !empty($data['username'])?$data['username']:$post['username'];
        $where['password'] = $data['password'] = md5(isset($data['password']) && !empty($data['password'])?$data['password']:$post['password']);
        $res = $this->obj->table('fwy_lawyer')->field('id,uid,phone,username,status')->where($where)->find();unset($data);
        //如果字表没有账号信息进入总表查询

        if(!$res){
            $where['username'] = $where['phone'];unset($where['phone']);
            $res = Db::table('os_user')->where($where)->find();

            if (!$res)
            {
                self::returnMsg('10106','登录失败，账号密码错误！');
            }else{
                $where['id'] = $res['id'];
                $cki = $this->check_identity($where); unset($where);
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
     * note:获取，修改用户信息
     * auth:杨炜
     * date:2018/01/25
     * return: bool
     */
    private function updUser($res,$status)
    {
        $res['weburl'] = $this->config['weburl'];
        $uid = $status?$res['uid']:$res['id'];
        //生成token保存到数据库
        $data['token'] = makeToken();
        $where['id'] = $uid;
        //更新登录token信息
        $save = Db::table('os_user')->where($where)->update($data); unset($where);
        if ($res && $save)
        {
            $user = array('uid' => $uid, 'token' => $data['token']);
            $timeout = isset($this->config['mtimeout']) && !empty($this->config['mtimeout'])?$this->config['mtimeout']:604800;
            cookie('user',$user,$timeout);
            $w['uid'] = $uid;
            $d['online'] = '1';
            $this->obj->table('fwy_lawyer')->where($w)->update($d);
            self::returnMsgAndToken('10000','登录成功',$res);
        }else{
            self::returnMsgAndToken('10011','登录错误：账号信息同步失败！');
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
        $data['online'] = '0';
        $this->obj->table('fwy_lawyer')->where($where)->update($data);
        setcookie('user','',time()-3600,'/');
        // session_unset();
        // session_destroy();
        self::returnMsg('10000','退出成功！');

    }

    /**
    * note:验证用户身份，并交换信息
    * auth:杨炜
    * date:2018/01/25
    * input: $obj模型对象 $where 条件
    * return: bool
    */
    private function check_identity($where = '')
    {
        $face = '/Upload/default/face/img_'.rand(0,4).'.png';
        $res = Db::table('os_user')->field('*')->where($where)->find();

        if (!$res) return false;
        $data['uid'] = $res['id'];
        $data['username'] = $res['nickname'];
        $data['phone'] = $res['phone'];
        $data['password'] = $res['password'];
        $data['status'] = 0;
        $data['add_time'] = time();
        $data['extractrate'] = '0';
        $data['face'] = $face;

        try{
            $res = $this->obj->table('fwy_lawyer')->insert($data);
            return $res?true:false;
        }catch (Exception $e){
            return false;
        }

    }

    /**
     * auth YW
     * note 注册
     * date 2018-12-14
     */
    public function add()
    {
        $post = $this->request->post();
        $user = Db::name('user');

        $where['username'] = $post['username'];
        $res = $user->where($where)->find();
        if ($res)
        {
            self::returnMsg('10010','注册失败：该账号已存在，若无法登录请联系客服！');
        }

        //判断当前是否关闭注册功能
        if($this->config['is_reigster_lawyer'] != 1){
            self::returnMsg('10011','注册通道被关闭！');
        }

        if ($post['checkcode'] != session($this->request->module().'_code') && empty($post['checkcode'])) {

            self::returnMsg('10104','验证码错误！');
        }

        //验证
        $validate = new \app\lapi\validate\User;
        if(!$validate->check($post)){
            self::returnMsg('10004',$validate->getError());
        }

        //写入会员表
        $user->startTrans();
        //总会员表信息
        $data['phone'] = $data['username'] = $post['username'];
        $data['nickname'] = 'LV'.mt_rand(1000,9999);
        $data['password'] = md5($post['password']);
        $data['status'] = '1';
        $data['add_time'] = time();
        $reg = $user->insertGetId($data);

        //子会员表信息
        $userdate['uid'] = $reg;
        $userdate['phone'] = $post['username'];
        $userdate['username'] = $data['nickname'];
        $userdate['password'] = md5($post['password']);
        if (!empty($post['province'])) $userdate['province'] = $post['province'];
        if (!empty($post['province_cn'])) $userdate['province_cn'] = $post['province_cn'];
        if (!empty($post['city'])) $userdate['city'] = $post['city'];
        if (!empty($post['city_cn'])) $userdate['city_cn'] = $post['city_cn'];
        if (!empty($post['area'])) $userdate['area'] = $post['area'];
        if (!empty($post['area_cn'])) $userdate['area_cn'] = $post['area_cn'];
        $userdate['add_time'] = time();
        $userdate['token'] = makeToken();
        $userdate['face']= "/Upload/default/face/face.png";//头像
        $add = $this->obj->table('fwy_lawyer')->insert($userdate);

        //消费管理表初始化
        $wallet = Db::table('os_lawyer');
        $lawyerdata['uid'] = $reg;
        $lawyerdata['add_time'] = time();
        $law = $wallet->insert($lawyerdata);

        if ($reg && $add && $law) {
            $user->commit();
            unset($data,$userdate,$lawyerdata);
            $data['username'] = $post['username'];
            $data['password'] = $post['password'];
            $this->login($data);
        }else{
            $user->rollback();
            self::returnMsg('10105','注册失败');
        }
    }

    public function del()
    {
        // TODO: Implement del() method.
    }

    public function delall()
    {
        // TODO: Implement delall() method.
    }

    /**
     * auth YW
     * note 修改个人资料
     * date 2018-12-14
     */
    public function edit()
    {
        $post = $this->request->post();
        if ($post)
        {

            $pks = array_keys($post);
            foreach ($pks as $key => $value)
            {
                $fields = array(
                    'username',
                    'phone',
                    'password',
                    'introduction',
                    'email',
                    'sex',
                    'bankcard',
                    'work_time',
                    'province',
                    'city',
                    'area',
                    'province_cn',
                    'city_cn',
                    'area_cn',
                    );
                if (in_array($value,$fields))
                {

                    if (isset($post[$value]) && $post[$value] != '')
                    {
                        //新版本
                        $data[$value] = $post[$value];
                        if (isset($post['password']) && !empty($post['password'])) $data['password'] = md5($post['password']);
                    }
                }
            }

            $data['edit_time'] = time();
            if ($_FILES)
            {

                //保存路径
                $path = ROOT_PATH.$this->config['upload'].DS."face";

                //图片名称
                $obj = new Common();
                $this->config['field'] = 'face';
                $res = $obj->upload($path , $format = 'empty', $maxSize = '52428800', $this->config ,false);
                $data['face'] = $res;                    //头像
            }
            /*更新子表信息*/
            $where['uid'] = $post['uid'];
            $res = $this->obj->table('fwy_lawyer')->where($where)->update($data);unset($where);

            if ($res)
            {
                self::returnMsgAndToken('10000','修改成功');
            }else{
                self::returnMsgAndToken('10010','修改失败');
            }
        }else{
            self::returnMsgAndToken('10004');
        }
    }
    /**
     * auth YW
     * note 忘记密码
     * date 2018-12-14
     */
    public function reset()
    {
        $post = $this->request->post();
        if (!$post)
        {
            self::returnMsg('10004');
        }
        if ($post['checkcode'] != session($this->request->module().'_code') || empty($post['checkcode'])) {

            self::returnMsg('10104','验证码错误');
        }else{

            $where['phone'] = $post['username'];
            $where['password'] = md5($post['password']);
            $data['password'] = md5($post['password']);
            $res = $this->obj->table('fwy_lawyer')->where($where)->find();unset($where['password']);
            if (!$res && $this->obj->table('fwy_lawyer')->where($where)->update($data))
            {
                self::returnMsg('10000','密码修改成功');
            }else{
                self::returnMsg('10011','修改失败不能和之前的密码一样');
            }
        }
    }

    /**
     * note:个人中心
     * auth:YW
     * date:2018/12/14
     */
    public function show()
    {
        $post = $this->request->post();
        if ($post) {

            $where['id'] = $post['uid'];
            $where1['uid'] = $post['uid'];
            $result = Db::table('os_lawyer')->where($where1)->field('wallet,coin')->find();
            $res = $this->obj->table("fwy_lawyer")->field('introduction,sex,phone,username,face,province_cn,city_cn,area_cn,price,bankcard,status,work_time,click_num,history_fansh_count,history_comment_count,history_answer_count,history_order_count')->where($where1)->find();
            unset($where);
            if ($result && $res) {
                $res['weburl'] = $this->config['weburl'];
                $result = array_merge($result,$res);
                self::returnMsgAndToken('10000','',$result);
            } else {
                self::returnMsgAndToken('10001','没有找到相关数据！');
            }
        }
    }

    public function showall()
    {
        // TODO: Implement showall() method.
    }

    public function serch()
    {
        // TODO: Implement serch() method.
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
            $validate = new \app\lapi\validate\Complaint();
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
    /**
     * note:评论列表
     * auth:YW
     * date:2018/12/27
     * 会员id[uid]，token[token]，分页基数[page]，每页数量[count]，
     */
    public function comment()
    {
        $post = $this->request->post();
        $where['status'] = '1';
        $where['lid'] = $post['uid'];

        $page['page'] = isset($post['page']) && !empty($post['page'])?$post['page']:'1';
        $c = isset($post['count']) && !empty($post['count'])?$post['count']:5;
        $p = ($page['page']-1)*$c ;
        $data['count'] = $this->obj->table('fwy_comment')->where($where)->count();
        $res = $this->obj->table('fwy_comment')
                        ->field('uid,content,add_time')
                        ->where($where)->order('add_time desc')
                        ->limit($p,$c)
                        ->select(); unset($where);
        if ($res)
        {
            foreach ($res as $key => $value)
            {
                $where['uid'] = $value['uid'];
                $user = $this->obj->table('fwy_member')->field('face')->where($where)->find();

                $res[$key]['face'] = $user['face'];
                $res[$key]['weburl'] = $this->config['weburl'];
            }
            $data['list'] = data2empty($res);
            self::returnMsgAndToken('10000','',$data);
        }else{
            self::returnMsgAndToken('10001','没有找到相关数据！');
        }
    }
    /**
     * note:我的客户
     * auth:YW
     * date:2018/12/27
     * 会员id[uid]，token[token]，分页基数[page]，每页数量[count]，
     */
    public function customer()
    {
        $post = $this->request->post();
        $page['page'] = isset($post['page']) && !empty($post['page'])?$post['page']:'1';
        $c = isset($post['count']) && !empty($post['count'])?$post['count']:5;
        $p = ($page['page']-1)*$c ;

        //获取我现在的所有客户
        $where['lid'] = $post['uid'];
        $data['count'] = $this->obj->table('fwy_memlawyer')->where($where)->count();
        $res = $this->obj->table('fwy_memlawyer')->field('uid,begin_time,end_time,chat_no,status')->where($where)->limit($p,$c)->select();unset($where);

        foreach ($res as $key => $value)
        {
            $where['uid'] = $value['uid'];
            $user = $this->obj->table('fwy_member')->field('uid,lid,isvip,vipdie_time,face,nickname')->where($where)->find();
            $res[$key] = array_merge($value,$user);
            $res[$key]['time'] = ceil(($value['end_time'] - $value['begin_time'])/60).'分钟';
            //如果这个会员的到期
            if (time() - $user['vipdie_time'] > 0)
            {
                $path = '/Logs/user/';
                $fileName = date('Y-m-d',time()).'.txt';
                self::checkvip($this->obj,$user,$path,$fileName);
            }
        }

        if ($res) {
            foreach ($res as $key => $value)
            {
                $res[$key]['weburl'] = $this->config['weburl'];
                $res[$key]['port'] = 'fwy';
            }
            $data['list'] = $res;
            self::returnMsgAndToken('10000','',$data);
        } else {

            self::returnMsgAndToken('10001','没有找到相关数据！');
        }
    }

    /**
     * 处理过期的会员信息
     * @param
     * @return booler 返回ajax的json格式数据
     */
    private function checkvip($obj,$user = '',$path,$fileName)
    {

        $obj->startTrans();
        /*重置会员表会员信息*/
        $data['isvip'] = '0';
        $data['isfenpeilayer'] = '0';
        $data['vipdie_time'] = '0';
        $data['lid'] = '0';
        $where['uid'] = $user['uid'];
        $set_m = $obj->table('fwy_member')->where($where)->update($data);unset($data,$where);

        /*更新律师表律师信息*/
        $data['end_time'] = time();
        $data['status'] = '0';
        $data['content'] = '系统停用';
        $where['uid'] = $user['uid'];
        $where['lid'] = $user['lid'];
        $where['status'] = '1';
        $set_l = $obj->table('fwy_memlawyer')->where($where)->update($data);unset($data,$where);
        if ($set_m && $set_l)
        {
            $obj->commit();
            //file_put_contents($path.$fileName,'pong:'.date('Y-m-d H:i:s',time()).'-'.$user['uid'].'-处理成功'.PHP_EOL, FILE_APPEND);
        }else{
            $obj->rollback();
            //file_put_contents($path.$fileName,'pong:'.date('Y-m-d H:i:s',time()).'-'.$user['uid'].'-处理失败'.PHP_EOL, FILE_APPEND);
        }

    }
    /**
     * note:专属客户聊天验证
     * auth:YW
     * date:2018/12/27
     * 会员id[uid]，token[token]，
     */
    public function checkmembervipstatus()
    {
        $post = $this->request->post();
        if (!isset($post['uid']) || empty($post['uid']))
        {
            self::returnMsgAndToken('10004');
        }

        $where['status'] = '1';
        $where['lid'] = $post['uid'];
        $res = $this->obj->table('fwy_memlawyer')->field('chat_no,begin_time,end_time')->where($where)->find();
        $res = data2empty($res);
        if ($res)
        {
            self::returnMsgAndToken('10000');
        }else{

            self::returnMsgAndToken('10011','该用户的vip已到期，无法回应对方');
        }
    }
    /**
     * note:是否接单开关
     * auth:YW
     * date:2019/01/18
     *
     */
    public function is_receipt()
    {
        $post = $this->request->post();
        if (!isset($post['is_receipt']) || $post['is_receipt'] == '')
        {
            self::returnMsgAndToken('10004');
        }
        $data['is_receipt'] = $post['is_receipt'];
        $where['uid'] = $post['uid'];

        $res = $this->obj->table('fwy_lawyer')->where($where)->update($data);
        if ($res)
        {
            self::returnMsgAndToken('10000','操作成功',$res);
        }else{
            self::returnMsgAndToken('10010','操作失败');
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
                        self::returnMsg('10011','该手机号已经被注册',array('code' => $code));
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
                    self::returnMsg('10011','暂时不支持其他方式');
                    break;

            }
            $config = $this->obj->table('fwy_sms_config')->where('status','1')->find();
            //$res = JhSms($data,$config);
            $res = true;
            if ($res)
            {
                self::returnMsg('10000','获取验证码成功',array('code' => $code));
            }else{

                self::returnMsg('10010','获取验证码失败',array('list' => $res));
            }

        }else{
            self::returnMsg('10004','获取验证码失败');
        }

    }

    /**
     * auth YW
     * note 空操作
     * date 2018-08-06
     */
    public function _empty(){
        self::returnMsg('10107','操作不合法');
    }

}