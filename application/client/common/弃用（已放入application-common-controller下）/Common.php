<?php
namespace app\common\controller;
use Think\Controller;

/*
 * 公共操作
 * */
class Common extends Controller
{
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
     * auth YW
     * note 对用户钱包操作
     * @param  array $data 数据源
     * @param  array 配置文件
     * @param  string 操作行为
     * date 2018-12-27
     */
    public function wallet($data,$config,$avtive = 'setInc')
    {

        $obj = D('lawyer');
        $where['uid'] = $data['uid'];
        if ($data['total'] == 'coin' || $data['total'] == 'wallet')
        {
            if ($data['total'] == 'coin')
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
     * 上传文件类型控制 此方法仅限ajax上传使用
     * @param  string $path 字符串 保存文件路径示例： /Upload/image/
     * @param  string $format 文件格式限制
     * @param  integer $maxSize 允许的上传文件最大值 52428800
     * @return booler 返回ajax的json格式数据
     */
    public function upload($path = 'file', $format = 'empty', $maxSize = '52428800',$isreturn = true)
    {
        ini_set('max_execution_time', '0');
        $path = trim($path, '/');
        $path = strtolower(substr($path, 0, 6)) === 'Upload' ? ucfirst($path) : $this->config['upload'] . $path;

        $ext_arr = array(
            'image' => array('gif', 'jpg', 'jpeg', 'png', 'bmp'),
            'photo' => array('jpg', 'jpeg', 'png')
        );

        if (!empty($_FILES)) {
            $config = array(
                'maxSize' => $maxSize,                      // 上传文件最大为50M
                'rootPath' => './',                         // 文件上传保存的根路径
                'savePath' => './' . $path . '/',           // 文件上传的保存路径（相对于根路径）
                'saveName' => array('uniqid', ''),          // 上传文件的保存规则，支持数组和字符串方式定义
                'autoSub' => ture,                          // 自动使用子目录保存上传文件 默认为true
                'exts' => isset($ext_arr[$format]) ? $ext_arr[$format] : '',
            );

            $upload = new \Think\Upload($config);
            $info = $upload->upload();
            $data = array();
            if (!$info) {
                $error = $upload->getError();
                $data['error_info'] = $error;
                return $data;
            } else {

                foreach ($info as $key => $file) {
                    $data[$file['key']][] = trim($file['savepath'] . $file['savename'], '.');
                }
                return $data;
            }
        }
    }

}