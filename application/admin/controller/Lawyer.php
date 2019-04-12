<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Db;

/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：律师管理
 */
class Lawyer extends Init
{
    private $sms_config;
    private $cert_type_cn = array(
        1 => '<span class="label label-warning radius">个人</span>',
        2 => '<span class="label label-primary radius">律所</span>',
        3 => '<span class="label label-primary radius">机构</span>',
    );


    /**
     * @auth YW
     * @date 2017.12.2
     * @purpose 初始化
     * @return void
     */
    public function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'].'lawyer';
        $where['status'] = '1';
        $where['type'] = 'Jhsms';
        $this->sms_config = $this->obj->table($this->config['prefix'].'sms_config')->where($where)->find();
    }

    public function index()
    {
        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $where['where'] = $map;
        $this->_list('',$where);
        return view();
    }

    public function _filter(&$map)
    {
        $this->checkSearch($map);
    }

    public function _after_list(&$list)
    {

        foreach ($list as $key => $value)
        {

            if (isset($value['industryid']))
            {
                $ids = explode(',',$value['industryid']);
                $str = '';
                foreach ($ids as $ky => $vl)
                {
                    $where['id'] = $vl;
                    $str .= $this->obj->table($this->config['prefix'].'goods_type')->where($where)->value('name').'-';
                }
                $list[$key]['industry_cn'] = trim($str,'-');

            }else{
                $list[$key]['industry_cn'] = '待完善';
            }
            if (isset($value['cert_type']) && !empty($value['cert_type']))
            {
                $list[$key]['cert_type_cn'] = $this->cert_type_cn[$value['cert_type']];
            }else{
                $list[$key]['cert_type_cn'] = '待完善';
            }



        }

    }
    /**
     * @auth YW
     * @date 2019.03.12
     * @purpose 短信发送
     * @return void
     */
    public function sendMsg()
    {
        if ($this->request->isPost())
        {
            if($this->config_sub['sms'] == '0')
            {
                echoMsg('10008',"提示：短信功能已关闭，请联系管理员开启！");
            }

            $post = $this->request->Post();
            $sms = array(
                'phone' => $post['target'],
                'tpl_id' => 120832,
            );

            $send = Jhsms($sms,$this->sms_config);
            //$send = true;
            $i = '1';
            if ($send === true)
            {
                $post['status'] = '1';
                $post['target'] = json_encode($post['target']);
                self::save_date($post);
                echoMsg('10000',"提示：{$i}条短信发送成功");
            }else{
                $post['status'] = '0';
                $post['target'] = json_encode($post['target']);
                self::save_date($post);
                $msg = json_encode($send);
                echoMsg('10001',"提示：{$i}条短信发送失败，{$msg}");
            }
        }else{
            $get = $this->request->get();
            $res = $this->obj->table($this->config['prefix'] . 'sms_jh_module')->select();
            $this->assign('v',$get);
            $this->assign('list',$res);
            return view('sms');
        }
    }


    public function _after_edit(&$list)
    {
        unset($list['password'],$list['token']);
        $list['weburl'] = $this->config['weburl'];
        $list['lawfrim_cn'] = $this->obj->table($this->config['prefix'].'lawfirm')->where('id' ,'=' , $list['lawfirm_id'])->value('name');
        $industryArr = explode(',',$list['industryid']);
        $str = '';
        foreach ($industryArr as $key => $value)
        {
            $str .= $this->obj->table($this->config['prefix'].'goods_type')->where('id', '=',$value)->value('name');
            $str .= ' ';
        }
        $list['industry_cn'] = $str;
        $list['cert'][] = $list['certa']; unset($list['certa']);
        $list['cert'][] = $list['certb']; unset($list['certb']);
        $list['cert'][] = $list['certc']; unset($list['certc']);


    }

    public function _before_update(&$post)
    {
        if ($this->request->isPost())
        {
            if (isset($post['password']) && $post['password'] != '')
            {
                $post['password'] = md5($post['password']);
            }
        }
    }
    /**
     * note:获取律师事务所
     * auth:杨炜
     * date:2018/05/18
     */
    public function getLasfirm()
    {
        $post = $this->request->Post();
        $where['name'] = array('like',"%{$post['reunite']}%");
        $lawfirm = $this->obj->table($this->config['prefix'].'lawfirm')->where($where)->select();
        if ($lawfirm)
        {
            echoMsg('10000',$this->message['get_success'],$lawfirm);
        }else{
            echoMsg('10001',$this->message['get_error']);
        }
    }
    /**
     * note:律师资料
     * auth:杨炜
     * date:2019/03/01
     */
    public function showDetail()
    {
        $get = $this->request->get();
        $fields = 'uid,id,phone,weixin,face,star,username,industryid,bankcard,province_cn,city_cn,area_cn,weixin,email,praiserate,history_order_count,introduction';
        $res = $this->obj->table($this->config['prefix'].'lawyer')->field($fields)->where('id', '=', $get['lid'])->find();

        $industryArr = explode(',',$res['industryid']);
        $str = '';
        foreach ($industryArr as $key => $value)
        {
            $str .= $this->obj->table($this->config['prefix'].'goods_type')->where('id', '=',$value)->value('name');
            $str .= ' ';
        }
        $res['industry_cn'] = $str;
        if($res)
        {
            $res['introduction'] = '';
            $res['area'] = $res['province_cn'].$res['city_cn'].$res['area_cn'];
            $wallet = Db::table('os_lawyer')->where('uid','=',$res['uid'])->field('wallet,coin')->find();
            $res = array_merge($res,$wallet);
            $res['weburl'] = $this->config['weburl'];
        }
        $this->assign('vo',$res);
        return view('detail');
    }
    /**
     * @auth YW
     * @date 2019.03.11
     * @purpose 历史服务记录
     * @return void
     */
    public function _before_anything()
    {
        $get = $this->request->get();
        $where['lid'] = $get['uid'];
        $res['data'] = $this->obj->table($this->config['prefix'].'memlawyer')->where($where)->select();
        $res['tpl'] = 'user_detail';
        return $res;
    }

    public function _after_anything(&$list)
    {
        $this->assign('list',$list['data']);
    }

    public function _before_forbid(&$field)
    {

        $post = $this->request->Post();
        $where['id'] = $post['id'];
        $where['status'] = '2';
        $res = $this->obj->table($this->table)->where($where)->find();
        if (!$res)
        {
            echoMsg('10001',$this->message['lawyer_auth_is_pass']);
        }else{
            $field = "is_top";
        }
    }

    public function _calByAjax()
    {
        $obj = Db::table('os_lawyer');
        $this->table = 'os_lawyer';
        self::calByAjax($obj,'uid');
    }


}