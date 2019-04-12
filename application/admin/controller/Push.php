<?php
namespace app\admin\controller;

use app\admin\common\controller\Init;

use think\Db;


class Push extends Init
{

    private $pushobj;
    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'] . 'push';
        $this->pushobj = new \app\common\controller\Push;
    }

    public function index()
    {
        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $map['status'] = array('in', '0,1');
        $where['where'] = $map;
        $this->_list('', $where);
        return view();
    }

    public function _filter(&$map)
    {
        $this->checkSearch($map);
    }

    public function _after_list(&$list)
    {
        $now_time = time();
        foreach ($list as $k => $v) {
            $w['tag'] = $v['msgtype'];
            $list[$k]['msgtype'] = $this->obj->table($this->config['prefix'] . 'pushtype')->where($w)->value('name');
            if ($now_time > $v['push_time']) {
                $m['id'] = $v['id'];
                $res = $this->obj->table($this->table)->where($m)->setField('push_status', 0);
            }
            if (!empty($v['push_time'])) {
                $list[$k]['push_time'] = $v['push_time'];
            } else {
                $list[$k]['push_time'] = '';
            }
        }
    }

    public function _before_add(&$list)
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['add_time'] = time();
            $data['push_time'] = strtotime($data['push_time']);
            #拼装push参数
            $push_data['content'] = $data['content'];
            $d[$data['msgType']] = $data['msgid'];
            $push_data['json'] = json_encode($d);
            if (empty($data['push_status'])) {
                $data['push_status'] = '0';
            }
            $data['msgtype'] = $data['msgType'];
            unset($data['msgType']);
            // 判断推送端
            $duan = $data['toport'] ? $data['toport'] : 'user';
            #定时推送
            if ($data['push_status'] == 1 && !empty($data['push_status'])) {
                $push_data['time'] = $data['push_time']; //时间戳
                $response = $this->pushobj->set_schedule($push_data['content'], $push_data['json'], $push_data['time'], $duan);
            } else { #立即推送
                $response = $this->pushobj->pushMessage($push_data['content'], $push_data['json'], $duan);
            }
            if ($response) {
                if ($data['push_status'] == 1) {
                    $data['note'] = $response['data'];
                }
                $db = $this->obj;
                $res = $db->table($this->table)->insert($data);
                echoMsg('10000', '推送成功');
            } else {
                echoMsg('11102', '推送失败');
            }
        } else {
            //获取类型
            $list = $this->obj->table($this->config['prefix'] . 'pushtype')->field('id,tag,name')->order('id desc')->select();
            $type = $list['0']['tag'];
            $db = $this->obj;
            if ($type == 'fatiaoid') {
                $w['sort'] = '1';
                $arr = $db->table($this->config['prefix'] . 'content')->field('id,title')->where($w)->order('add_time desc')->select();
            } else if ($type == 'articleid') {
                $arr = $db->table($this->config['prefix'] . 'lawyer_content')->field('id,title')->order('add_time desc')->select();
            } else if ($type == 'videoid') {
                $arr = $db->table($this->config['prefix'] . 'lawyer_video')->field('id,title')->order('add_time desc')->select();
            } else if ($type == 'lawyerid') {
                $arr = $db->table($this->config['prefix'] . 'lawyer')->field('id,username as title')->order('add_time desc')->select();
            } else {
                $arr = '';
            }
            $this->assign('tree', $list); // 赋值类型
            $this->assign('arr', $arr); // 赋值类型
            $this->display();
        }
    }

    public function showtitle()
    {
        $post = $this->request->post();
        $type = $post['type'];
        $mark = $post['mark'];
        $push_time = strtotime($post['push_time']);
        if (!$mark) {
            $w['add_time'] = array('lt', $push_time);
            //定时内容时间需大于推送时间
        }
        $db = $this->obj;
        if ($type == 'fatiaoid') {
            $w['sort'] = '1';
            $arr = $db->table($this->config['prefix'] . 'content')->field('id,title')->where($w)->order('add_time desc')->select();
        } else if ($type == 'articleid') {
            $arr = $db->table($this->config['prefix'] . 'lawyer_content')->field('id,title')->order('add_time desc')->select();
        } else if ($type == 'videoid') {
            $arr = $db->table($this->config['prefix'] . 'lawyer_video')->field('id,title')->order('add_time desc')->select();
        } else if ($type == 'lawyerid') {
            $arr = $db->table($this->config['prefix'] . 'lawyer')->field('id,username as title')->order('add_time desc')->select();
        } else {
            $arr = '';
        }
        if ($arr) {
            echoMsg('200', '获取成功', $arr);
        } else {
            echoMsg('0', '获取失败');
        }
    }

    /*
     *note:发送
     *auth:gzb
     *date:2018/01/08
     */
    private function push_post($url, $post)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, 0); // 过滤HTTP头
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 显示输出结果
        curl_setopt($curl, CURLOPT_POST, true); // post传输数据
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post); // post传输数据
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //这个是重点。
        $response = curl_exec($curl);
        //var_dump(curl_error($curl));//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
        curl_close($curl);
        $data = json_decode($response, 1);
        if ($data['code'] == "SUCCESS") {
            if (isset($data['data'])) {
                return $data['data'];
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * 友好的时间显示
     *
     * @param int    $sTime 待显示的时间
     * @param string $type  类型. normal | mohu | full | ymd | other
     * @param string $alt   已失效
     * @return string
     */
    function friendlyDate($sTime, $type = 'normal', $alt = 'false')
    {
        if (!$sTime)
            return '';
        //sTime=源时间，cTime=当前时间，dTime=时间差
        $cTime = time();
        $dTime = $cTime - $sTime;
        $dDay = intval(date("z", $cTime)) - intval(date("z", $sTime));
        //$dDay     =   intval($dTime/3600/24);
        $dYear = intval(date("Y", $cTime)) - intval(date("Y", $sTime));
        //normal：n秒前，n分钟前，n小时前，日期
        if ($type == 'normal') {
            if ($dTime < 60) {
                if ($dTime < 10) {
                    return '刚刚';    //by yangjs
                } else {
                    return intval(floor($dTime / 10) * 10) . "秒前";
                }
            } elseif ($dTime < 3600) {
                return intval($dTime / 60) . "分钟前";
                //今天的数据.年份相同.日期相同.
            } elseif ($dYear == 0 && $dDay == 0) {
                //return intval($dTime/3600)."小时前";
                return '今天' . date('H:i', $sTime);
            } elseif ($dYear == 0) {
                return date("m月d日 H:i", $sTime);
            } else {
                return date("Y-m-d H:i", $sTime);
            }
        } elseif ($type == 'mohu') {
            if ($dTime < 60) {
                return $dTime . "秒前";
            } elseif ($dTime < 3600) {
                return intval($dTime / 60) . "分钟前";
            } elseif ($dTime >= 3600 && $dDay == 0) {
                return intval($dTime / 3600) . "小时前";
            } elseif ($dDay > 0 && $dDay <= 7) {
                return intval($dDay) . "天前";
            } elseif ($dDay > 7 && $dDay <= 30) {
                return intval($dDay / 7) . '周前';
            } elseif ($dDay > 30) {
                return intval($dDay / 30) . '个月前';
            }
            //full: Y-m-d , H:i:s
        } elseif ($type == 'full') {
            return date("Y-m-d , H:i:s", $sTime);
        } elseif ($type == 'ymd') {
            return date("Y-m-d", $sTime);
        } else {
            if ($dTime < 60) {
                return $dTime . "秒前";
            } elseif ($dTime < 3600) {
                return intval($dTime / 60) . "分钟前";
            } elseif ($dTime >= 3600 && $dDay == 0) {
                return intval($dTime / 3600) . "小时前";
            } elseif ($dYear == 0) {
                return date("Y-m-d H:i:s", $sTime);
            } else {
                return date("Y-m-d H:i:s", $sTime);
            }
        }
    }
}
