<?php
namespace app\uapi\controller\v3;

use think\Request;
use think\Db;


class Find extends Index
{

	/**
	 * 初始化
	 *
	 * @return \think\Response
	 */
	public function _initialize()
	{
		parent::_init();
	}

	/*
     *note:获取发现页分类列表
     *auth:彭桃
     *date:2019/02/20
     */
	public function gettype()
	{
		$uid = $this->request->post('uid');
		$where['tid'] = '-1';
		$where['status'] = '1';
		// 获取法条表分类和认证号内容分类表数据组合成一个数组
		$arr2 = $this->obj->table('fwy_lawyer_content_type')->field('id,name')->where($where)->order('sort desc')->select();
		foreach ($arr2 as $k => $v) {
			$arr2[$k]['tag'] = 'authentication';
		}
		// 前两个分类默认为关注、推荐
		$arr3 = array(
			'0' => array('id' => '0', 'name' => '关注', 'tag' => 'fans'), '1' => array('id' => '0', 'name' => '推荐', 'tag' => 'tuijian'), '2' => array('id' => '0', 'name' => '法条', 'tag' => 'fatiao')
		);
		$a = array_merge($arr3, $arr2);
		if ($a) {
			// 默认获取推荐内容
			self::returnMsg('10000', '', $a);
		} else {
			self::returnMsg('10001');
		}
	}

	/*
     *note:根据id获取分类下所有内容
     *auth:彭桃
     *date:2019/02/20
     */
	public function getdatabyid()
	{
		$post = $this->request->post();
		$tag = $post['tag'];
		if (empty($post['uid'])) {
			$post['uid'] = '';
		}
		// 判断页数是否为空
		if (empty($post['page'])) {
			$p = 1;
		} else {
			$p = $post['page'];
		}
		$c = '9';
		// 判断是法条还是认证号发布内容，tag标识区分去不同表获取数据
		if ($tag == 'fatiao') {
			$table = 'fwy_content';
			$w['sort'] = '1';
			$w['status'] = '1';
			$field = 'id,title,release_time,browse,thumbnail,author,section';
			// 获取数据
			$list = $this->obj->table($table)->field($field)->where($w)->page($p, $c)->select();
			if ($list) {
				$a['list'] = $list;
				$a['total'] = $this->obj->table($table)->where($w)->count();
				self::returnMsg('10000', '', $a);
			} else {
				self::returnMsg('10001', '没有找到相关数据！');
			}
		} else if ($tag == 'authentication') {
			$table = 'fwy_lawyer_content';
			// 获取自己所有下级分类id
			$id = $this->getallid($post['id'], array('0' => $post['id']), $post['tag']);
			if (empty($id)) {
				self::returnMsg('10001', '没有找到相关数据！');
			}
			// 拼装where条件
			$w['type'] = array('in', $id);
			$w['status'] = '1';
			// 获取数据
			$list = $this->obj->table($table)->where($w)->page($p, $c)->select();
			if ($list) {
				foreach ($list as $key => $value) {
					// 判断是否关注
					$ww['uid'] = $post['uid'];
					$ww['lid'] = $value['uid'];
					$fans = $this->obj->table('fwy_fans')->where($ww)->find();
					if ($fans) {
						$list[$key]['isguanzhu'] = '1';
					} else {
						$list[$key]['isguanzhu'] = '0';
					}
					$list[$key]['classify'] = '2';
					$list[$key]['weburl'] = $this->config['weburl'];
					// 获取律师信息
					$www['uid'] = $value['uid'];
					$list[$key]['nickname'] = $this->obj->table('fwy_lawyer')->where($www)->value('username');
					$list[$key]['face'] = $this->obj->table('fwy_lawyer')->where($www)->value('face');
				}
				$a['list'] = $list;
				$a['total'] = $this->obj->table($table)->where($w)->count();
				self::returnMsg('10000', '', $a);
			} else {
				self::returnMsg('10001', '没有找到相关数据！');
			}
		} else if ($tag == 'fans') { //如果是关注
			$wh['uid'] = $post['uid'];
			$wh['status'] = '1';
			$fans = $this->obj->table('fwy_fans')->where($wh)->select();
			if (empty($fans)) {
				self::returnMsg('10001', '没有找到相关数据！');
			} else {
				$arr = $this->getguanzhu($post['uid'], $p, '3');
				if (!empty($arr)) {
					return self::returnMsg('10000', '', $arr);
				} else {
					return self::returnMsg('10001', '没有找到相关数据！');
				}
			}
		} else if ($tag == 'tuijian') {
			$list = $this->gettuijian($post['uid'], $p, $c);
			if ($list) {
				self::returnMsg('10000', '', $list);
			} else {
				self::returnMsg('10001', '没有找到相关数据！');
			}
		} else {
			self::returnMsg('10004');
		}
	}

	/*
     *note:根据id获取文章详情内容
     *auth:彭桃
     *date:2019/02/21
     */
	public function getdetail()
	{
		$post = $this->request->post();
		$tag = isset($post['tag']) ? $post['tag'] : '';
		$p = isset($post['page']) ? $post['page'] : '1';
		$c = '9';
		if (empty($post['uid'])) {
			$post['uid'] = '';
		}
		// 判断是法条还是认证号发布内容，tag标识区分去不同表获取数据
		if ($tag == 'fatiao') {
			$table = 'fwy_content';
			$ptable = '';
		} elseif ($tag == 'authentication') {
			$table = 'fwy_lawyer_content';
			$ptable = 'fwy_lawyer_content_comment';
		} elseif ($tag == 'tuijian' || $tag == 'fans') {
			// 判断类型（2长文章，3视频，6短文章）
			$type = $post['type'];
			if ($type == '2') {
				$table = 'fwy_lawyer_content';
				$ptable = 'fwy_lawyer_content_comment'; //评论表
				$where['type'] = '1';
			} else if ($type == '3') {
				$table = 'fwy_lawyer_video';
				$ptable = 'fwy_lawyer_video_comment';
			} else if ($type == '6') {
				$table = 'fwy_lawyer_shortxt';
				$ptable = 'fwy_lawyer_content_comment';
				$where['type'] = '0';
			}
		} else {
			self::returnMsg('10004', '缺少参数');
		}
		// 拼装where条件
		$w['id'] = isset($post['id']) ? $post['id'] : '';
		// 获取数据
		$list = $this->obj->table($table)->where($w)->find();

		if ($list) {
			// 点击数加一
			$ip = getIP();
			$options = [
				// redis缓存
				'redis'   =>  [
					// 驱动方式
					'type'   => 'redis',
					// 服务器地址
					'host'       => '127.0.0.1',
				],
			];
			// 缓存初始化
			// 不进行缓存初始化的话，默认使用配置文件中的缓存配置
			cache($options);
			// 获取缓存数据
			$cacheip = cache($ip);
			if (!$cacheip) {
				// 设置缓存数据
				$cacheip = cache($ip, $ip, 60);
				$this->obj->table($table)->where($w)->setInc('click', '1');
			}


			// 如果不是法条就获取评论
			if ($tag != 'fatiao') {
				// 判断类型（2长文章，3视频，6短文章）
				if ($tag == 'tuijian' || $tag == 'fans') {
					$type = $post['type'];
					if ($type == '2') {
						$list['classify'] = '2';
					} else if ($type == '3') {
						$list['classify'] = '3';
					} else if ($type == '6') {
						$list['classify'] = '6';
					}
				}
				if ($tag == 'authentication') {
					$list['classify'] = '2';
				}
				$list['weburl'] = $this->config['weburl'];
				// 处理视频路径
				if (isset($list['path'])) {
					$list['path'] = json_decode($list['path']);
					$list['thumbnail'] = json_decode($list['thumbnail']);
				}
				// 获取律师信息
				$wh['uid'] = $list['uid'];
				$list['face'] = $this->obj->table('fwy_lawyer')->where($wh)->value('face');
				$list['username'] = $this->obj->table('fwy_lawyer')->where($wh)->value('username');
				// 判断是否关注
				$ww['uid'] = $post['uid'];
				$ww['lid'] = $list['uid'];
				$fans = $this->obj->table('fwy_fans')->where($ww)->find();
				if ($fans) {
					$list['isguanzhu'] = '1';
				} else {
					$list['isguanzhu'] = '0';
				}
				$where['status'] = '1';
				$where['toid'] = $w['id'];
				$list['list'] = $this->obj->table($ptable)->where($where)->page($p, $c)->order('add_time desc')->select();
				// 有评论就获取用户信息
				if ($list['list']) {
					$list['total'] = $this->obj->table($ptable)->where($where)->page($p, $c)->order('add_time desc')->count();
					foreach ($list['list'] as $key => $value) {
						$wheres['uid'] = $value['uid'];
						$list['list'][$key]['nickname'] = $this->obj->table('fwy_member')->where($wheres)->value('nickname');
						$list['list'][$key]['face'] = $this->obj->table('fwy_member')->where($wheres)->value('face');
					}
				}
			}
			self::returnMsg('10000', '', $list);
		} else {
			self::returnMsg('10001', '没有找到相关数据！');
		}
	}

	/*
     *note:获取分类所有下级id
     *auth:彭桃
     *date:2019/02/20
     */
	private function getallid($id, $array = array(), $tag)
	{
		if ($tag == 'authentication') {
			$table = 'fwy_lawyer_content_type';
		} else {
			return self::returnMsg('10004');
		}
		$where['tid'] = $id;
		$where['status'] = '1';
		$arr = $this->obj->table($table)->field('id')->where($where)->select();
		$arr = array_column($arr, 'id');
		$n = array_merge($arr, $array);
		if ($arr) {
			foreach ($arr as $k => $v) {
				$n = $this->getallid($v, $n, $tag);
			}
		}
		return $n;
	}

	/*
     *note:获取推荐内容
     *auth:彭桃
     *date:2019/02/20
     */
	private function gettuijian($uid, $p, $c = '9')
	{
		$w['status'] = '1';
		$w['tag'] = array('like', '%f%');
		$all = $this->obj->table('fwy_article_main')->where($w)->page($p, $c)->order('add_time desc')->select();
		// 记录总条数
		$acount = $this->obj->table('fwy_article_main')->where($w)->count();
		foreach ($all as $k => $v) {
			$where['id'] = $v['sid'];
			$whre['status'] = '1';
			$all[$k]['list'] = $content = $this->obj->table($v['table'])->where($where)->find();
			if ($v['table'] == 'fwy_lawyer_shortxt') {
				$all[$k]['list']['classify'] = '6'; //短文章
				if (!empty($value['thumbnail'])) {
					$all[$k]['list']['thumbnail'] = json_decode($content['thumbnail'], 1);
				}
			} else if ($v['table'] == 'fwy_lawyer_video') {
				$all[$k]['list']['classify'] = '3'; //视频
				$all[$k]['list']['path'] = json_decode($all[$k]['list']['path']);
				$all[$k]['list']['thumbnail'] = json_decode($all[$k]['list']['thumbnail']);
			} else if ($v['table'] == 'fwy_lawyer_content') {
				$all[$k]['list']['classify'] = '2'; //长文章
			}
		}
		$array = array_column($all, 'list');
		foreach ($array as $key => $value) {
			$array[$key]['weburl'] = $this->config['weburl'];
			// 获取律师信息
			$l['uid'] = $value['uid'];
			$lawyer = $this->obj->table('fwy_lawyer')->where($l)->find();
			$array[$key]['username'] = $lawyer['username'];
			$array[$key]['face'] = $lawyer['face'];
			// 判断是否关注
			$ww['uid'] = $uid;
			$ww['lid'] = $value['uid'];
			$fans = $this->obj->table('fwy_fans')->where($ww)->find();
			if ($fans) {
				$array[$key]['isguanzhu'] = '1';
			} else {
				$array[$key]['isguanzhu'] = '0';
			}
		}
		$arr['total'] = $acount;
		$arr['list'] = $array;
		return $arr;
	}

	/*
     *note:获取我的关注
     *auth:彭桃
     *date:2019/02/20
     */
	private function getguanzhu($uid, $p, $c = '9')
	{
		$wh['uid'] = $uid;
		$wh['status'] = '1';
		$fans = $this->obj->table('fwy_fans')->where($wh)->select();
		if (empty($fans)) {
			return "";
		} else {
			$lidarr = array_column($fans, 'lid');
			$lid['a.uid'] = array('in', $lidarr);
			$lid['a.status'] = '1';
			$lid['b.status'] = '1';
			$lid['b.table'] = 'fwy_lawyer_content';
			$con = $this->obj->table('fwy_lawyer_content')->alias('a')->join('fwy_article_main b', 'a.id=b.sid')->where($lid)->page($p, $c)->order('a.add_time desc')->select();
			foreach ($con as $key => $value) {
				$con[$key]['classify'] = '2'; //长文章
				if (!empty($value['thumbnail'])) {
					$con[$key]['thumbnail'] = json_decode($value['thumbnail']);
				}
			}
			$lid['b.table'] = 'fwy_lawyer_video';
			$v = $this->obj->table('fwy_lawyer_video')->alias('a')->join('fwy_article_main b', 'a.id=b.sid')->where($lid)->page($p, $c)->order('a.add_time desc')->select();
			foreach ($v as $key => $value) {
				$v[$key]['classify'] = '3'; //视频
				$v[$key]['path'] = json_decode($value['path']);
				$v[$key]['thumbnail'] = json_decode($value['thumbnail']);
			}
			$lid['b.table'] = 'fwy_lawyer_shortxt';
			$aa = $this->obj->table('fwy_lawyer_shortxt')->alias('a')->join('fwy_article_main b', 'a.id=b.sid')->where($lid)->page($p, $c)->order('a.add_time desc')->select();
			foreach ($aa as $key => $value) {
				$aa[$key]['classify'] = '6'; //短文章
				if (!empty($value['thumbnail'])) {
					$aa[$key]['thumbnail'] = json_decode($value['thumbnail']);
				}
			}
			$list = array_merge($con, $v, $aa);
			if ($list) {
				foreach ($list as $k => $va) {
					$l['uid'] = $va['uid'];
					$lawyer = $this->obj->table('fwy_lawyer')->where($l)->find();
					$list[$k]['username'] = $lawyer['username'];
					$list[$k]['face'] = $lawyer['face'];
					$list[$k]['weburl'] = $this->config['weburl'];
					$list[$k]['id'] = $va['sid'];
				}
				$lid['b.table'] = 'fwy_lawyer_content';
				$concount = $this->obj->table('fwy_lawyer_content')->alias('a')->join('fwy_article_main b', 'a.id=b.sid')->where($lid)->count();
				$lid['b.table'] = 'fwy_lawyer_video';
				$vcount = $this->obj->table('fwy_lawyer_video')->alias('a')->join('fwy_article_main b', 'a.id=b.sid')->where($lid)->count();
				$lid['b.table'] = 'fwy_lawyer_shortxt';
				$shortcount = $this->obj->table('fwy_lawyer_shortxt')->alias('a')->join('fwy_article_main b', 'a.id=b.sid')->where($lid)->count();
				// 记录总条数
				$arr['total'] = $concount + $vcount + $shortcount;
				$arr['list'] = $list;
				return $arr;
			} else {
				return "";
			}
		}
	}


	/*
     *note:点赞接口
     *参数：type（1律师，2文章，3视频，4音频，5法条...）,uid,fid,token、lid
     *auth:彭桃
     *date:2019/02/21
     */
	public function zan()
	{
		$post = $this->request->post();
		if (empty($post['type']) || empty($post['token']) || empty($post['fid']) || empty($post['uid'])) {
			self::returnMsg('10004');
		}
		$post['add_time'] = time();
		$where['uid'] = $post['uid'];
		$where['fid'] = $post['fid'];
		$where['type'] = $post['type'];
		unset($post['token']);
		$zan = $this->obj->table('fwy_zan')->where($where)->find();
		if ($zan) {
			// 开启事务
			$this->obj->startTrans();
			try {
				$res = $this->obj->table('fwy_zan')->where($where)->delete();
				$w['uid'] = $post['lid'];
				$this->obj->table('fwy_lawyer')->where($w)->setDec('click_num', '1');
				$t['id'] = $post['fid'];
				if ($post['type'] == '2') {
					$this->obj->table('fwy_lawyer_content')->where($t)->setDec('histort_reward_count', '1');
				} elseif ($post['type'] == '3') {
					$this->obj->table('fwy_lawyer_video')->where($t)->setDec('histort_reward_count', '1');
				} elseif ($post['type'] == '6') {
					$this->obj->table('fwy_lawyer_shortxt')->where($t)->setDec('histort_reward_count', '1');
				}
				$d['flag'] = '0';
				//提交事务
				$this->obj->commit();
				self::returnMsg('10000', '取消点赞成功', $d);
			} catch (\PDOException $e) {
				//回滚事务
				$this->obj->rollback();
				self::returnMsg('10014', '取消点赞失败');
			}
		} else {
			// 开启事务
			$this->obj->startTrans();
			try {
				$res = $this->obj->table('fwy_zan')->insert($post);
				$w['uid'] = $post['lid'];
				$this->obj->table('fwy_lawyer')->where($w)->setInc('click_num', '1');
				$t['id'] = $post['fid'];
				if ($post['type'] == '2') {
					$this->obj->table('fwy_lawyer_content')->where($t)->setInc('histort_reward_count', '1');
				} elseif ($post['type'] == '3') {
					$this->obj->table('fwy_lawyer_video')->where($t)->setInc('histort_reward_count', '1');
				} elseif ($post['type'] == '6') {
					$this->obj->table('fwy_lawyer_shortxt')->where($t)->setInc('histort_reward_count', '1');
				}
				$d['flag'] = '1';
				//提交事务
				$this->obj->commit();
				self::returnMsg('10000', '点赞成功', $d);
			} catch (\PDOException $e) {
				//回滚事务
				$this->obj->rollback();
				self::returnMsg('10014', '点赞失败');
			}
		}
	}

	/*
     *note:关注接口
     *uid,lid,token
     *auth:彭桃
     *date:2019/02/21
     */
	public function fans()
	{
		$post = $this->request->post();
		if (empty($post['token']) || empty($post['lid']) || empty($post['uid'])) {
			self::returnMsg('10004', '缺少参数');
		}
		$post['add_time'] = time();
		$where['uid'] = $post['uid'];
		$where['lid'] = $w['uid'] = $post['lid'];
		unset($post['token']);
		$post['username'] = $this->obj->table('fwy_lawyer')->where($w)->value('username');
		$fans = $this->obj->table('fwy_fans')->where($where)->find();
		if ($fans) {
			$res = $this->obj->table('fwy_fans')->where($where)->delete();
			if ($res) {
				$d['flag'] = '0';
				$this->obj->table('fwy_lawyer')->where($w)->setDec('history_fansh_count', '1');
				return self::returnMsg('10000', '取消关注成功', $d);
			} else {
				return self::returnMsg('10014', '取消关注失败');
			}
		} else {
			$res = $this->obj->table('fwy_fans')->insert($post);
			if ($res) {
				$d['flag'] = '1';
				$this->obj->table('fwy_lawyer')->where($w)->setInc('history_fansh_count', '1');
				self::returnMsg('10000', '关注成功', $d);
			} else {
				self::returnMsg('10014', '关注失败');
			}
		}
	}

	/*
     *note:用户收藏
     *auth:PT
     *see：uid 、token 、type类型（1律师，2长文章，3视频，4音频，5法条，6短文章，7商品）,id
     *goods_id(商品id)、law_id(法条id)、content_id(长文章id 2) 、lawyer_id(律师id)、video_id(视频id)、audio_id(音频id)、article_id(短文章id)
     *date:2019/02/21
     */
	public function collection()
	{
		$post = $this->request->post();
		if (isset($post)) {
			unset($post['token']);
			if (empty($post['type'])) {
				self::returnMsg('10004');
			}
			if (empty($post['id'])) {
				self::returnMsg('10004');
			}
			if ($post['type'] == '1') {
				$where['lawyer_id'] = $data['lawyer_id'] = $post['id'];
			} elseif ($post['type'] == '2') {
				$where['content_id'] = $data['content_id'] = $post['id'];
			} elseif ($post['type'] == '3') {
				$where['video_id'] = $data['video_id'] = $post['id'];
			} elseif ($post['type'] == '4') {
				$where['audio_id'] = $data['audio_id'] = $post['id'];
			} elseif ($post['type'] == '5') {
				$where['law_id'] = $data['law_id'] = $post['id'];
			} elseif ($post['type'] == '6') {
				$where['article_id'] = $data['article_id'] = $post['id'];
			} elseif ($post['type'] == '7') {
				$where['goods_id'] = $data['goods_id'] = $post['id'];
			} else {
				self::returnMsg('10004');
			}
			$result = $this->obj->table('fwy_collection')->where($where)->find();
			if ($result) {
				//已收藏.即取消收藏
				if ($this->obj->table('fwy_collection')->where($where)->delete()) {
					self::returnMsg('10000', '取消收藏成功');
				} else {
					self::returnMsg('10014', '取消收藏失败');
				}
			} else {
				$data['add_time'] = time();
				$data['uid'] = $post['uid'];
				$data['status'] = '1';
				//无收藏.即添加收藏
				if ($this->obj->table('fwy_collection')->insert($data)) {
					self::returnMsg('10000', '收藏成功');
				} else {
					self::returnMsg('10014', '收藏失败');
				}
			}
		} else {
			self::returnMsg('10004');
		}
	}


	/*
     *note:法条搜索
     *auth:PT
     *
     *date:2019/03/29
    */
	public function globalsearch()
	{
		$post = $this->request->post();
		$page = isset($post['page']) ? $post['page'] : '1';
		$key_words = isset($post['key_words']) ? $post['key_words'] : '决议';
		$where = [
			'section|title'  =>  ['like', '%' . $key_words . '%'],
			'status'    =>  '1',
			'sort' =>  '1'
		];

		$result = $this->obj->table('fwy_content')->field('id,title,release_time,section')->order('add_time desc')->where($where)->page($page, 10)->select();
		$count = $this->obj->table('fwy_content')->where($where)->count();
		$list['total'] = $count;
		$list['list'] = $result;
		if ($result) {
			self::returnMsg('10000', '', $list);
		} else {
			self::returnMsg('10001', '暂无数据');
		}
	}


	/*
     *note:分享接口
     *auth:PT
     *参数：type类型（1律师，2长文章，3视频，5法条，6短文章，7商品）,id
     *date:2019/04/10
    */
	public function share()
	{
		$post = $this->request->post();
		// 判断类型（2长文章，3视频，6短文章）
		$type = isset($post['type']) && !empty($post['type']) ? $post['type'] : '';
		if ($type == '1') {
			$table = 'fwy_lawyer';
			$field = 'id,username,face,introduction';
		} else if ($type == '2') {
			$table = 'fwy_lawyer_content';
			$field = 'id,thumbnail,title,describe';
		} else if ($type == '3') {
			$table = 'fwy_lawyer_video';
			$field = 'id,thumbnail,title';
		} else if ($type == '5') {
			$table = 'fwy_content';
			$field = 'id,thumbnail,title,content';
			$where['sort'] = '1'; //法条
		} else if ($type == '6') {
			$table = 'fwy_lawyer_shortxt';
			$field = 'id,thumbnail,content';
		} else if ($type == '7') {
			$table = 'fwy_goods';
			$field = 'id,goods_name,detail';
		} else {
			self::returnMsg('10004', '缺少参数');
		}
		if (isset($post['id']) && !empty($post['id'])) {
			$where['id'] = $post['id'];
		} else {
			self::returnMsg('10004', '缺少参数');
		}
		$res = $this->obj->table($table)->field($field)->where($where)->find();
		if ($res) {
			if ($type == '1') {
				$data['describe'] = $res['introduction'];
				$data['tile'] = $res['username'];
				$data['img'] = $this->config['weburl'] . $res['face'];
			} else if ($type == '3') {
				$data['describe'] = $res['title'];
				$data['tile'] = $res['title'];
			} else if ($type == '5') {
				$data['describe'] = substr(strip_tags($res['content']), 0, 300);
				$data['tile'] = $res['title'];
			} else if ($type == '6') {
				$data['describe'] = $res['content'];
				$data['tile'] = $res['content'];
			} else if ($type == '7') {
				$data['describe'] = substr(strip_tags($res['detail']), 0, 100);
				$data['tile'] = $res['goods_name'];
			} else {
				$data['describe'] = $res['describe'];
				$data['tile'] = $res['title'];
			}
			if (!empty($res['thumbnail']) && isset($res['thumbnail'])) {
				$data['img'] = $this->config['weburl'] . $res['thumbnail'];
			} else {
				$data['img'] = $this->config['weburl'] . '/upload/default/icon/icon.png';
			}
			self::returnMsg('10000', '', $data);
		} else {
			self::returnMsg('10001', '暂无数据');
		}
	}

	/**
	 * auth YW
	 * note 空操作
	 * date 2018-08-06
	 */
	public function _empty()
	{
		self::returnMsg('10107', '操作不合法');
	}
}
