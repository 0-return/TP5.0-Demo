<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use app\common\controller\Common;
use app\common\controller\Jhsms;
use think\Db;
/**
 * Create by .
 * Cser Administrator
 * Time 16:18
 * Note：会员管理
 */
class Member extends Init
{
    private $sms_config;
    private $status = array(
        '-1'        =>  '<span class="label label-danger radius">已删除</span>',
        '0'         =>  '<span class="label label-default radius">已禁用</span>',
        '1'         =>  '<span class="label label-success radius">使用中</span>',
    );
    private $year = array(
        '0-1',
        '1-2',
        '2-3',
        '4-7',
        '7-10',
        '7-15',
        '15-20',
        '20-30',
        '30-50',
    );
    private $star = array(
        '0-1',
        '1-2',
        '2-3',
        '3-4',
        '4-5',
    );


    function _initialize()
    {
        parent::_init();

        $where['status'] = '1';
        $where['type'] = 'Jhsms';
        $this->sms_config = $this->obj->table($this->config['prefix'].'sms_config')->where($where)->find();
    }


    /**
     * @auth YW
     * @date 2018.11.29
     * @purpose 列表
     * @return void
     */
    public function index()
    {
        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $map['status'] = array('gt','-1');
        $where['where'] = $map;
        $this->_list('',$where  );
        return view();
    }

    private function _filter(&$map)
    {
        $get = $this->request->get();
        if (!empty($get['begintime']) && !empty($get['endtime']))
        {
            $map['add_time'] = array('between',array(strtotime($get['begintime']),strtotime($get['endtime'])));
        }

        $this->checkSearch($map);
    }

    protected function _after_list(&$list)
    {
        foreach ($list as $key => $value)
        {
            $list[$key]['status_cn'] = $this->status[$value['status']];
            if ($value['isfenpeilayer'] == '1')
            {
                $res = $this->obj->table($this->config['prefix'].'lawyer')->where('uid','=',$value['lid'])->field('username')->find();
                $list[$key]['lawyer'] = $res['username'];
            }
        }

    }

    public function showDetail()
    {
        $get = $this->request->get();
        $fields = 'id,uid,face,username,nickname,sex,province_cn,city_cn,area_cn,detail,qq,wechat,add_time,status,email,vipdie_time,isvip,isfenpeilayer';
        $res = $this->obj->table($this->config['prefix'].'member')->field($fields)->where('id', '=', $get['id'])->find();
        if($res)
        {
            $res['area'] = $res['province_cn'].$res['city_cn'].$res['area_cn'];
            $wallet = Db::table('os_lawyer')->where('uid','=',$res['uid'])->field('wallet,coin')->find();
            if (!$wallet)
            {
                $wallet['coin'] = 0;
                $wallet['wallet'] = 0;
            }
            $res = array_merge($res,$wallet);
            $res['weburl'] = $this->config['weburl'];
        }
        $this->assign('vo',$res);
        return view('detail');
    }

    /**
     * @auth YW
     * @date 2019.03.08
     * @purpose 律师筛选
     * @return void
     */
    public function lawyerList()
    {
        $get = $this->request->get();

        $where['status'] = '1';
        $where['name_en'] = array('like',"%quick%");;
        $temp = $this->obj->table($this->config['prefix'].'goods_type')->where($where)->find();unset($where);
        $gt = $this->obj->table($this->config['prefix'].'goods_type')->where('iid','=',$temp['id'])->select();

        $res = $get;
        if (count($get) > 1)
        {
            $this->table = $this->config['prefix'].'lawyer';

            unset($get['uid']);
            $get = paramFormart($get);
            if (isset($get['province_cn'])) $map['province_cn'] = array('like',"%{$get['province_cn']}%");
            if (isset($get['city_cn'])) $map['city_cn'] = array('like',"%{$get['city_cn']}%");
            if (isset($get['area_cn'])) $map['area_cn'] = array('like',"%{$get['area_cn']}%");
            if (isset($get['star']))
            {
                $between = explode('-',$get['star']);
                $map['star'] = array('between',$between);
            }
            if (isset($get['industryid'])) $map['industryid'] = array('like',"%{$get['industryid']}%");
            if (isset($get['work_time']))
            {
                $between = explode('-',$get['work_time']);
                $map['work_time'] = array('between',$between);
            }
            $map['status'] = '2';
            $where['where'] = $map;
            $module = parent::getModel();
            $list = $module->where($where['where'])->paginate(48,false,['query'=>request()->param()]);
            //echo $module->getLastSql();
            $page = $list->render();
            $count = $list->total();
            $list = $list->items();
            foreach ($list as $key => $value )
            {
                $list[$key]['weburl'] = $this->config['weburl'];
            }
            $this->assign('count',$count);
            $this->assign('list',$list);
            $this->assign('page',$page);
        }else{
            $this->assign('count','0');
            $this->assign('list','');
            $this->assign('page','');
        }

        $this->assign('yr',$this->year);
        $this->assign('st',$this->star);
        $this->assign('gt',$gt);
        $this->assign('v',$res);
        return view('lawyer_select');
    }
    /**
     * @auth YW
     * @date 2019.03.08
     * @purpose 分配律师
     * @return void
     */
    public function lawyerStart()
    {
        //判断是否会员，判断律师是否合法，增加当前律师到用户表里，增加律师记录到用户律师记录表里，发送短信
        $post = $this->request->Post();
        if ($post)
        {
            /**判断是否会员*/
            $where['id'] = $post['uid'];
            $user = $this->obj->table($this->config['prefix'].'member')->where($where)->field('uid,isvip,phone')->find(); unset($where);
            if (!$user)
            {
                echoMsg('10010',$this->message['user_not_member']);
            }
            /**判断律师是否合法*/
            $where['id'] = $post['lid'];
            $lawyer = $this->obj->table($this->config['prefix'].'lawyer')->where($where)->field('uid,username,phone,industryid,province_cn,city_cn,area_cn')->find(); unset($where);
            if (!$lawyer)
            {
                echoMsg('10011',$this->message['lawyer_not_status']);
            }
            $this->obj->startTrans();

            /**增加当前律师到用户表里*/
            $_data['lid'] = $lawyer['uid'];
            $_data['isfenpeilayer'] = 1;
            $where['uid'] = $user['uid'];
            $where['isvip'] = '1';
            $where['isfenpeilayer'] = '0';
            $save = $this->obj->table($this->config['prefix'].'member')->where($where)->update($_data);

            //echo $this->obj->getLastSql();
            /**增加律师记录到用户律师记录表里*/
            $iArr = explode(',',$lawyer['industryid']);

            $str = '';
            foreach ($iArr as $key => $value)
            {
                $str .= $this->obj->table($this->config['prefix'].'goods_type')->where('id','=',$value)->value('name').'-';
            }
            $data_['industry'] = trim($str,'-');
            $data_['uid'] = $user['uid'];
            $data_['lid'] = $lawyer['uid'];
            $data_['chat_no'] = get_str_guid();
            $data_['begin_time'] = time();
            $data_['username'] = $lawyer['username'];
            $data_['phone'] = $lawyer['phone'];
            $data_['status'] = '1';
            $data_['area'] = $lawyer['province_cn'].$lawyer['city_cn'].$lawyer['area_cn'];
            $add = $this->obj->table($this->config['prefix'].'memlawyer')->insert($data_);

            //$add = true;
            /**发送短信*/
            if ($save && $add)
            {
                $this->obj->commit();
                /**给用户发送短信*/
                $sms = array(
                    'phone' => $user['phone'],
                    'tpl_id' => '',
                );

                Jhsms($sms,$this->sms_config);

                /**给律师发送短信*/
                $sms = array(
                    'phone' => $lawyer['username'],
                    'tpl_id' => '',
                );

                Jhsms($sms,$this->sms_config);

                /**日志*/
                $obj = new Common();
                $user = getUser($this->request->module());
                $data['uid'] = $post['lid'];
                $data['username'] = $user['username'];
                $data['explain'] = 'sys';
                $obj->wLog($this->obj,$this->request,$data,$this->config);

                echoMsg('10000',$this->message['success']);
            }else{
                $this->obj->rollback();
                echoMsg('10011',$this->message['error']);
            }
        }
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
        $where['uid'] = $get['uid'];
        $res['data'] = $this->obj->table($this->config['prefix'].'memlawyer')->where($where)->select();
        $res['tpl'] = 'lawyer_detail';
        return $res;

    }

    public function _after_anything(&$list)
    {
        $this->assign('list',$list['data']);
    }

    /**
     * @auth YW
     * @date 2019.03.09
     * @purpose 律师移除
     * @return void
     */
    public function lawyerStop()
    {
        $post = $this->request->Post();
        //将用户表里的律师id，是否分配律师初始化
        /*$where['uid'] = $post['uid'];
        $member_res = $this->obj->table($this->table)->where($where)->find(); unset($where);
        //获取律师地区，行业，电话
        $where['uid'] = $post['lid'];
        $lawyer_res = $this->obj->table($this->config['prefix'].'lawyer')->field('uid,username,phone,industryid,province_cn,city_cn,area_cn')->where($where)->find();unset($where);*/

        $this->obj->startTrans();
        $data['isfenpeilayer'] = '0';
        $data['lid'] = '0';

        $where['uid'] = $post['uid'];
        $update_member_res = $this->obj->table($this->table)->where($where)->update($data);unset($where,$data);

        //修改用户律师表里的律师状态
        $where['uid'] = $post['uid'];
        $where['lid'] = $post['lid'];
        $where['status'] = '1';
        $data['end_time'] = time();
        $data['status'] = '0';
        $update_lawyer_res = $this->obj->table($this->config['prefix'].'memlawyer')->where($where)->update($data);
        //echo $this->obj->getLastSql();
        if ($update_lawyer_res && $update_member_res)
        {
            $this->obj->commit();
            echoMsg('10000',$this->message['success']);
        }else{
            $this->obj->rollback();
            echoMsg('10001',$this->message['error']);
        }
    }

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

    public function _calByAjax()
    {
        $obj = Db::table('os_lawyer');
        $this->table = 'os_lawyer';
        self::calByAjax($obj,'uid');
    }

    /**
     * @auth YW
     * @date 2019.03.07
     * @purpose 存储短信发送记录
     * @return void
     */
    private function save_date(&$data)
    {
        $this->table = $this->config['prefix'] . 'sms';
        $data['add_time'] = time();
        $res = $this->obj->table($this->table)->insert($data);
        return $res?true:false;
    }


}