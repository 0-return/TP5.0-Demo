<?php
namespace app\admin\controller;
use app\common\controller\Email;
use app\common\controller\Excel;

/**
 * Create by .
 * Cser Administrator
 * Time 17:54
 */

class Test{

    function email()
    {

        $config = array(
            'host' => 'smtp.163.com',
            'port' => '25',
            'type' => 'SMTP',
            'username' => 'Y644503680@163.com',
            'password' => 'mywy8023',
            'subject' => '内部测试',
            'from' => 'Y644503680@163.com',
            'name' => '杨哥',
        );
        $email = new Email($config);
        $data = array(
            'content' => '收到请回答,收到请回答!',
            'user_email' => '2893097678@qq.com',
        );
        $res = $email->Send($data);
        var_dump($res);
    }

    public function sms()
    {

    }

    public function excel()
    {
        $excel = new Excel();
        $excel->title = '';
        $data = array('name' => 'tom','age' => '18','sex' => '男'
            /*array('name' => 'tom','age' => '18','sex' => '男'),
            array('name' => 'tom','age' => '18','sex' => '男'),
            array('name' => 'tom','age' => '18','sex' => '男'),
            array('name' => 'tom','age' => '18','sex' => '男'),*/
        );
        $title = array(
            '姓名','年龄','性别'
        );
        $excel->middle($data,$title);
    }



}


