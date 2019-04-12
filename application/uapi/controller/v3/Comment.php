<?php
namespace app\uapi\controller\v3;
use think\Db;
class Comment extends Index{
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
     *note:添加（评价，内容，星级）
     *auth:PT
     *date:2019/01/28
     * input lid【律师id】 uid【会员id】 content【内容】 star【星级】、order_no【订单号】、token
     */
    public function add()
    {

        $comment = $this->request->post();
        //参数验证
        $validate = new \app\uapi\validate\Comment;
        if(!$validate->check($comment)){
            self::returnMsgAndToken('10004',$validate->getError());
        }
        if (!empty($comment['uid']) && $comment) {
            $comment['ip'] = get_ip();
            $map['order_no']=$comment['order_no'];
            //检测订单当前状态
            $status = $this->obj->table('fwy_order')->where($map)->find();
           if($status['pay'] != '1'){
            	self::returnMsgAndTokenAndToken('10010','该订单未完成，还无法评价！');
            }else{
            	// 开启事务
                DB::startTrans();
                try{
                	// 判断lawyer_status是否为-1
	                if ($status['lawyer_status']!='-1') {
	                    $re['t4'] = $this->obj->table('fwy_order')->where($map)->setField('lawyer_status','4');
	                }
		                //修改订单状态为4
	                $data['status'] = '4';
	                $data['comment'] = '1';
	                $re['obj'] = $this->obj->table('fwy_order')->where($map)->update($data);     /*20181218 启用新订单状态*/
	                // var_dump($re['obj']);exit;
	                //数据处理
	                $comment['add_time'] = time();
	                $comment['status'] = '1';

	                // 添加评论
	                $lWhere['status'] = '1';
	                $lWhere['lid'] = $uwhere['uid'] = $comment['lid'];
	                //第一次评价前，数据库里没有评价信息，无法计算平均值。
	                $star = $this->obj->table('fwy_comment')->field('star')->where($lWhere)->select();

	                unset($comment['token']);
	                unset($comment['order_no']);
	                //添加评论/计算星级
	                if ($star)      //有评价
	                {
	                    //计算平均值
	                    $count = count($star);
	                    $num = 0;
	                    foreach ($star as $key => $value)
	                    {
	                        $num += $value['star'];
	                    }
	                    $average = number_format($num/$count,4);

	                    //写入星级到律师表
	                    $s['star'] = $average;
	                    $re['t2'] = $this->obj->table('fwy_lawyer')->where($uwhere)->update($s);

	                    //写入评价表
	                    $re['t3'] = $this->obj->table('fwy_comment')->insert($comment);

	                } else {        //无评价
						$s['star'] = $comment['star'];
	                    //写入星级到律师表
	                    $re['t2'] = $this->obj->table('fwy_lawyer')->where($uwhere)->update($s);

	                    //写入评价表
	                    $re['t3'] = $this->obj->table('fwy_comment')->insert($comment);

	                }
	                //任意一个表写入失败都会抛出异常：
		            if (in_array('0', $re)) {
                        Db::rollback();
		                self::returnMsgAndToken('10014','评价失败！');
		            }

                    //提交事务
                    Db::commit();
                    self::returnMsgAndToken('10000','评价成功！');
                }catch (\Exception $e) {
                    //回滚事务
                    Db::rollback();
                    self::returnMsgAndToken('10014','评价失败！');
                }



            }

        } else {
        	self::returnMsgAndToken('10004');
        }
    }

    /*
     *note:根据id获取
     *auth:PT
     *date:2019/01/28
     *
     */
    public function showbyid()
    {
    	$post = $this->request->post();
        if (!empty($post['uid'])) {
            $where['lid'] = $post['lid'];       //律师id
            $where['status'] = '1';
            $res = $this->obj->table('fwy_comment')->where($where)->select();
            if ($res) {
            	self::returnMsg('10000','',$res);
            } else {
            	self::returnMsg('10001','没有找到相关数据！');
            }
        } else {
        	self::returnMsg('10004');
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

    /*
     *note:评论文章、视频接口
     *auth:PT
     *参数：uid , token , content【内容】 , type（2长文章，3视频，6短文章）,toid(文章/视频id)
     *date:2019/04/01
     *
     */
    public function comment(){
        $post = $this->request->post();
        // 判断类型（2长文章，3视频，6短文章）
        $type = isset($post['type']) ? $post['type'] : '';
        if ($type == '2') {
            $ptable = 'fwy_lawyer_content_comment';//评论表
            $table = 'fwy_lawyer_content';
            $post['type'] = '1';
        }else if ($type == '3') {
            $ptable = 'fwy_lawyer_video_comment';
            unset($post['type']);
            $table = 'fwy_lawyer_video';
        }else if ($type == '6') {
            $ptable = 'fwy_lawyer_content_comment';
            $table = 'fwy_lawyer_shortxt';
            $post['type'] = '0';
        }else{
            self::returnMsg('10004', '缺少参数');
        }
        $post['add_time'] = time();
        $post['status'] = '1';
        unset($post['token']);
        $res = $this->obj->table($ptable)->insert($post);
        if ($res) {
            $w['id'] = $post['toid'];
            $this->obj->table($table)->where($w)->setInc('history_comment_count', '1');
            self::returnMsg('10000','评论成功');
        } else {
            self::returnMsg('10009','评论失败');
        }
    }


}
