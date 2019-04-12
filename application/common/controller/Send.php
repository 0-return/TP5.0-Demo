<?php
namespace app\common\controller;

trait Send
{
    /**
     * 返回成功
     */
    public static function returnMsg($code = 10000,$message = '',$data = [],$header = [])
    {   
        //http_response_code($code);    //设置返回头部
        $res['code'] = (int)$code;
        $res['message'] = !empty($message)?'提示：'.$message:'';
        $res['data'] = is_array($data) ? $data : ['info'=>$data];

        echo json_encode($res); exit(0);
    }
    /**
     * 返回成功带token
     */
    public static function returnMsgAndToken($code = 10000,$message = '',$data = [],$header = [])
    {
        //http_response_code($code);    //设置返回头部
        $res['code'] = (int)$code;
        $res['message'] = !empty($message)?'提示：'.$message:'';
        $res['data'] = is_array($data) ? $data : ['info'=>$data];

        $user = cookie('user');
        $res['uid'] = $user['uid'];
        $res['token'] = $user['token'];
        echo json_encode($res); exit(0);
    }
}

