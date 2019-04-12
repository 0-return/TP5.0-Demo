<?php
namespace app\admin\controller;

use app\admin\common\controller\Init;
use think\Db;

class Userdata extends Init
{

    private $industry;
    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'] . 'member';
        $this->lawyertable = $this->config['prefix'] . 'lawyer';
    }

    public function index()
    {
        $db = $this->obj;
        # 必须用上月份最初  算得才是一个月后  不然会按现在的时候算
        date_default_timezone_set('Asia/Shanghai');
        $first_day_of_month = date('Y-m', time());
        $t = strtotime($first_day_of_month);
        for ($i = 0; $i < 12; $i++) { //注册统计12个月
            if ($i == 0) {
                $after_time = time();
            } else {
                $after_time = strtotime(date("Y-m-01 00:00:00", strtotime(1 - $i . ' month', $t)));
            }
            $before_time = strtotime(date("Y-m-01 00:00:00", strtotime(-$i . ' month', $t)));

            $m['add_time']  = array('between', array($before_time, $after_time));
            $lc = $db->table($this->lawyertable)->where($m)->count(); //律师
            $uc = $db->table($this->table)->where($m)->count(); //用户

            // $lc_1=$db->table($this->lawyertable)->where("")->sum("");//律师日活跃
            // $uc_1=$db->table($this->table)->where($m)->count();//用户日活跃

            $uc_active[$i] = (int)$uc;
            $lc_active[$i] = (int)$lc;
            $month[$i] = date('y-m', $before_time);
        }
        //用户、律师用户占比
        $count_u = $db->table($this->table)->where('status=1')->count(); //用户
        $count_l = $db->table($this->lawyertable)->where('status=2')->count(); //律师
        $array_count_u[] = '用户';
        $array_count_u[] = (int)round($count_u / ($count_l + $count_u) * 100);
        $array_count_l[] = '律师';
        $array_count_l[] = (int)round($count_l / ($count_l + $count_u) * 100);
        $array_count[] = $array_count_u;
        $array_count[] = $array_count_l;

        //用户、律师地区分布占比
        $region = $db->table($this->config['prefix'] . 'region')->field('id,region_name')->where('parent_id = 1')->select();
        $renum = count($region);
        for ($i = 0; $i < $renum; $i++) {
            $w['province'] = $region[$i]['id'];
            $w['status'] = 1;
            $me_c = $db->table($this->table)->where($w)->count();
            $area_count[$i][] = $region[$i]['region_name'];
            $area_count[$i][] = (int)$me_c;
        }

        //安卓用户端版本分布占比
        $a_version = $db->table($this->table)->field('id,version')->where("status=1 and user_type='android' and version is not null")->group('version')->select();

        for ($i = 0; $i < count($a_version); $i++) {
            $a['version'] = $a_version[$i]['version'];
            $v_c = $db->table($this->table)->where($a)->count();
            $version_count[$i][] = '版本号' . $a_version[$i]['version'];
            $version_count[$i][] = (int)$v_c;
        }
        // echo "<pre>";
        // var_dump($version_count);exit;
        //苹果用户端版本分布占比
        $a_version = $db->table($this->table)->field('id,version')->where("status=1 and user_type='ios' and version is not null")->group('version')->select();

        for ($i = 0; $i < count($a_version); $i++) {
            $ios['version'] = $a_version[$i]['version'];
            $ios_c = $db->table($this->table)->where($ios)->count();
            $ios_count[$i][] = '版本号' . $a_version[$i]['version'];
            $ios_count[$i][] = (int)$ios_c;
        }


        //安卓律师端版本分布占比
        $a_version = $db->table($this->lawyertable)->field('id,version')->where("status=2 and user_type='android' and version is not null")->group('version')->select();
        for ($i = 0; $i < count($a_version); $i++) {
            $a['version'] = $a_version[$i]['version'];
            $a['status'] = 2;
            $a['user_type'] = 'android';
            $lv_c = $db->table($this->lawyertable)->where($a)->count();
            $version_l_count[$i][] = '版本号' . $a_version[$i]['version'];
            $version_l_count[$i][] = (int)$lv_c;
        }

        //苹果律师端版本分布占比
        $a_version = $db->table($this->lawyertable)->field('id,version')->where("status=2 and user_type='ios' and version is not null")->group('version')->select();
        for ($i = 0; $i < count($a_version); $i++) {
            $ios['version'] = $a_version[$i]['version'];
            $ios['status'] = 2;
            $ios['user_type'] = 'ios';
            $lios_c = $db->table($this->lawyertable)->where($ios)->count();
            $ios_l_count[$i][] = '版本号' . $a_version[$i]['version'];
            $ios_l_count[$i][] = (int)$lios_c;
        }

        // 用户总数
        $ww['status'] = '1';
        $alluser = $db->table($this->table)->where($ww)->count();
        // 当前在线人数
        $whw['online'] = '1';
        $whw['status'] = '1';
        $onlineuser = $db->table($this->table)->where($whw)->count();
        // 总律师数
        $whe['status'] = '2';
        $alllawyer = $db->table($this->lawyertable)->where($whe)->count();
        // 当前在线律师人数
        $wher['online'] = '1';
        $wher['status'] = '2';
        $onlinelawyer = $db->table($this->lawyertable)->where($wher)->count();

        $this->assign('alluser', $alluser);
        $this->assign('onlineuser', $onlineuser);
        $this->assign('alllawyer', $alllawyer);
        $this->assign('onlinelawyer', $onlinelawyer);


        $this->assign('area_count', json_encode($area_count)); //分布占比
        $this->assign('array_count', json_encode($array_count)); //用户占比
        $this->assign('month', json_encode(array_reverse($month))); //注册统计月份
        $this->assign('uc_active', json_encode(array_reverse($uc_active))); //注册统计
        $this->assign('lc_active', json_encode(array_reverse($lc_active))); //注册统计

        $this->assign('version_count', json_encode($version_count)); //安卓用户端版本分布占比
        $this->assign('ios_count', json_encode($ios_count)); //安卓用户端版本分布占比

        $this->assign('version_l_count', json_encode($version_l_count)); //安卓律师端版本分布占比
        $this->assign('ios_l_count', json_encode($ios_l_count)); //安卓律师端版本分布占比
        return view();
    }
}
