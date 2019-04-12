<?php
namespace app\uapi\controller\v3;
use app\common\controller\Common;
use think\Db;
/**
 * auth YW
 * note 推送
 * date 2018-08-06
 */
class Special extends Index{

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
     * auth PT
     * note 专题页获取全部数据
     * page,uid,lid
     * date 2019-02-18
     */
    public function index(){
    	$post = $this->request->post();
        $p = isset($post['page']) ? $post['page'] :'1';
        $c = '10';
    	$where['uid'] = $post['lid'];
    	$arr = $this->obj->table('fwy_lawyer')->where($where)->find();
    	if ($arr) {
            $industry = explode(',',$arr['industryid']);
            if ($industry) {
                foreach ($industry as $k => $v) {
                    $w['id'] = $v;
                    $industry[$k] = $this->obj->table('fwy_goods_type')->where($w)->value('name');
                }
                $arr['industry'] = $industry;
            }
            // 判断是否关注
            $ww['uid'] = isset($post['uid']) ? $post['uid'] : '';
            $ww['lid'] = $post['lid'];
            $fans = $this->obj->table('fwy_fans')->where($ww)->find();
            if ($fans) {
                $arr['isguanzhu'] = '1';
            }else{
                $arr['isguanzhu'] = '0';
            }
            $wh['a.status'] = '1';
            $wh['a.lid'] = $post['lid'];
            $arr['list'] = $this->obj->table('fwy_lawyer_evaluate')->alias('a')->join('fwy_member b','a.uid=b.uid')->field('a.content,b.nickname,b.face,b.province_cn,b.city_cn')->where($wh)->page($p,$c)->order('a.add_time desc')->select();
            $arr['weburl'] = $this->config['weburl'];
            $arr['total'] = $this->obj->table('fwy_lawyer_evaluate')->alias('a')->join('fwy_member b','a.uid=b.uid')->field('a.content,b.nickname,b.face,b.province_cn,b.city_cn')->where($wh)->count();
    		self::returnMsgAndToken('10000','',$arr);
    	}else{
    		self::returnMsgAndToken('10001','没有找到相关数据！');
    	}
    }

     /**
     * auth PT
     * note 专题页切换查看视频、文章、音频
     * 参数：page、lid、flag、uid
     * date 2019-02-19
     */
    public function getbyflag(){
    	$post = $this->request->post();
        if (empty($post['flag']) || empty($post['lid'])) {
            self::returnMsg('10004');
        }
        $p = isset($post['page']) ? $post['page'] :'1';
        $c = '5';
        if (isset($post['flag']) && $post['flag'] == 'article') {
            $lid['uid'] = $post['lid'];
            $lid['status'] = '1';
            $con = $this->obj->table('fwy_lawyer_content')->where($lid)->page($p, $c)->order('add_time desc')->select();
            foreach ($con as $key => $value) {
                $con[$key]['classify'] = '2'; //长文章
            }

            $aa = $this->obj->table('fwy_lawyer_shortxt')->where($lid)->page($p, $c)->order('add_time desc')->select();
            foreach ($aa as $key => $value) {
                $aa[$key]['classify'] = '6'; //短文章
            }
            $list = array_merge($con,$aa);
            if ($list) {
                foreach ($list as $k => $va) {
                    if (!empty($va['thumbnail'])) {
                        $list[$k]['thumbnail'] = json_decode($va['thumbnail']);
                    }
                    $l['uid'] = $va['uid'];
                    $lawyer = $this->obj->table('fwy_lawyer')->where($l)->find();
                    $list[$k]['username'] = $lawyer['username'];
                    $list[$k]['face'] = $lawyer['face'];
                    $list[$k]['weburl'] = $this->config['weburl'];
                    // 判断是否关注
                    $ww['uid'] = isset($post['uid']) ? $post['uid'] : '';
                    $ww['lid'] = $post['lid'];
                    $fans = $this->obj->table('fwy_fans')->where($ww)->find();
                    if ($fans) {
                        $list[$k]['isguanzhu'] = '1';
                    }else{
                        $list[$k]['isguanzhu'] = '0';
                    }
                }
                $concount = $this->obj->table('fwy_lawyer_content')->where($lid)->count();
                $shortcount = $this->obj->table('fwy_lawyer_shortxt')->where($lid)->count();
                // 记录总条数
                $arr['total'] = $concount + $shortcount;
                $arr['list'] = $list;
            }
        }else if (isset($post['flag']) && $post['flag'] == 'video') {
            $lid['uid'] = $post['lid'];
            $lid['status'] = '1';
            $video = $this->obj->table('fwy_lawyer_video')->where($lid)->page($p, $c)->order('add_time desc')->select();
            foreach ($video as $key => $value) {
                $video[$key]['classify'] = '3'; //视频
                if (!empty($value['thumbnail'])) {
                    $video[$key]['thumbnail'] = json_decode($value['thumbnail']);
                }
                if (!empty($value['path'])) {
                    $video[$key]['path'] = json_decode($value['path']);
                }
                $video[$key]['weburl'] = $this->config['weburl'];
                $l['uid'] = $value['uid'];
                $lawyer = $this->obj->table('fwy_lawyer')->where($l)->find();
                $video[$key]['username'] = $lawyer['username'];
                $video[$key]['face'] = $lawyer['face'];
                // 判断是否关注
                $ww['uid'] = isset($post['uid']) ? $post['uid'] : '';
                $ww['lid'] = $post['lid'];
                $fans = $this->obj->table('fwy_fans')->where($ww)->find();
                if ($fans) {
                    $video[$key]['isguanzhu'] = '1';
                }else{
                    $video[$key]['isguanzhu'] = '0';
                }
            }
            $count = $this->obj->table('fwy_lawyer_video')->where($lid)->count();
            // 记录总条数
            $arr['total'] = $count;
            $arr['list'] = $video;
        }else{
            self::returnMsgAndToken('10004');
        }
        if ($arr) {
            self::returnMsgAndToken('10000','',$arr);
        }else{
            self::returnMsgAndToken('10001','没有找到相关数据！');
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