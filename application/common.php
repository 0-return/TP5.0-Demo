<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
// 应用公共文件

/**
 * 获取ip地址
 * @param
 * @return void
 */
function getIP()
{
    if (getenv('HTTP_CLIENT_IP')) {
        $ip = getenv('HTTP_CLIENT_IP');
    }
    elseif (getenv('HTTP_X_FORWARDED_FOR')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
    }
    elseif (getenv('HTTP_X_FORWARDED')) {
        $ip = getenv('HTTP_X_FORWARDED');
    }
    elseif (getenv('HTTP_FORWARDED_FOR')) {
        $ip = getenv('HTTP_FORWARDED_FOR');

    }
    elseif (getenv('HTTP_FORWARDED')) {
        $ip = getenv('HTTP_FORWARDED');
    }
    else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}


/**
 * note: 获取字段信息（通用）
 * auth: YW
 * input $where[条件],$obj[模型],$fields[获取的字段][]
 * return array,str
 */
function getFields($obj = '',$where = '',$exp = array())
{
    $type = $exp['type'];
    $res = $obj->where($where)->$type($exp['fields']);
    return $res;

}

/**
 * note:（通用）
 * auth:YW
 * date:2018/06/25
 * return str
 */
function echoMsg($code = '',$message = '',$data = '',$exit = true)
{
    $msg['code'] = $code;
    $msg['msg'] = !empty($message)?$message : '';
    $msg['data'] = !empty($data)?$data : '';
    echo json($msg)->send();
    $exit?exit(0):'';
}
/**
 * 请求数据（含POST，GET模式）
 * @param  string $url [请求的URL地址]
 * @param  string $params [请求的参数]
 * @param  int $ipost [是否采用POST形式]
 * @return  string
 */
function Curl($url, $params = false, $ispost = 0)
{
    $httpInfo = array();
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    if ($ispost) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_URL, $url);
    } else {
        if ($params) {
            curl_setopt($ch, CURLOPT_URL, $url.'?'.$params);
        } else {
            curl_setopt($ch, CURLOPT_URL, $url);
        }
    }
    $response = curl_exec($ch);
    if ($response === FALSE) {
        //echo "cURL Error: " . curl_error($ch);
        return false;
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $httpInfo = array_merge($httpInfo, curl_getinfo($ch));
    curl_close($ch);
    return $response;
}

/**
 * note:无限分类(下拉样式)
 * auth:YW
 * input $obj 模型 $table对象 $current 选中的id $parentid父id $count累加次数 $field特殊字段
 * return htmlstr
 */
function cateTreeHtml($obj , $table,$field = array(), $parentid = '0', $count = 0, $current = '')
{

    $where[$field['pid']] = $parentid;
    $where[$field['status']] = '1';
    $res = $obj->table($table)->where($where)->select();
    if (empty($res)) return;
    $optionHtml = '';
    $linstr = str_repeat("——|", $count);
    foreach ($res as $key => $value) {
        if ($value[$field['pid']] == $parentid) {
            if ($value[$field['key']] == $current) {
                $optionHtml .= "<option selected value='{$value[$field['key']]}'>{$linstr} {$value[$field['title']]}</option>";
            } else {
                $optionHtml .= "<option value='{$value[$field['key']]}'>{$linstr} {$value[$field['title']]}</option>";
            }
            $optionHtml .= cateTreeHtml($obj,$table , $field, $value[$field['id']], $count + 1, $current);
        }
    }
    return $optionHtml;
}
/**
 * note:目录创建
 * auth:YW
 * date:2018/07/03
 */
function makedir( $dir , $mode = 0700 ) {
    if(strpos($dir , "/" )){
        $dir_path = "" ;
        $dir_info = explode ( "/" , $dir );
        foreach($dir_info   as   $key => $value ){
            $dir_path .= $value ;
            if (!file_exists($dir_path )){
                @mkdir ( $dir_path , $mode ) or die ( "建立文件夹时失败了" );
                @chmod ( $dir_path , $mode );
            } else {
                $dir_path .= "/" ;
                continue ;
            }
            $dir_path .= "/" ;
        }
        return   $dir_path ;
    } else {
        @mkdir( $dir , $mode ) or die( "建立失败了,请检查权限" );
        @chmod ( $dir , $mode );
        return   $dir ;
    }
}
/*******************************************20181117**********************************************************/
/**
 * note:6为码
 * auth:YW
 * date:2018/07/03
 */
function randCode($length = '')
{

    $code = mt_rand(pow(10, ($length - 1)), pow(10, $length) - 1);
    return $code;
}

/**
 * note:聚合短信
 * auth:YW
 * date:2018/07/03
 */
function JhSms(&$data,&$config)
{
    //$obj = new Jhsms($config);
    $obj = new \app\common\controller\Jhsms($config);
    $sms = array(
        'mobile' => $data['phone'],
        'tpl_id' => $data['tpl_id'],
    );
    if (isset($data['tpl_value']) && !empty($data['tpl_value']))
    {
        $sms['tpl_value'] = $data['tpl_value'];
    }

    $res = $obj->Jh_send($sms);
    return $res;
}

/**
 * note:token生成
 * auth:YW
 * date:2018/12/09
 * return: str
 */
function makeToken(){
    $str = md5(uniqid(md5(microtime(true)), true)); //生成一个不会重复的字符串
    $str = sha1($str); //加密
    return $str;
}
/**
 * note:验证token
 * auth:YW
 * date:2018/12/14
 * return: arr&bool
 */
function verifyToken($obj,$table = '',$where = '',$list,$config,$ischeck = false){

    $res = $table?$obj->table($table)->where($where)->field('id,token')->find():$obj->where($where)->field('id,token')->find(); unset($where);
    if ($res)
    {
        if ($ischeck)               //动态变更
        {
            $data['token'] = makeToken();
            $where['uid'] = $list['uid'];
            $res = $table?$obj->table($table)->where($where)->save($data):$obj->where($where)->save($data);
        }else{                      //登录变更
            $data['token'] = $list['token'];
            $res = true;
        }

        if ($res)
        {
            $user = array('uid' => $list['uid'], 'token' => $data['token']);
            $timeout = isset($config['mtimeout']) && !empty($config['mtimeout'])?$config['mtimeout']:604800;
            cookie('user',$user,$timeout);
            return $data;
        }else{
            return false;
        }
    }else{
        return false;
    }
}
/**
 * note:将空数据信息改成''或""
 * auth:YW
 * date:2018/12/14
 * return: arr&bool
 */
function data2empty($data = '')
{
    if (is_array($data))
    {
        foreach ($data as $key => $value)
        {

            if (is_array($value))
            {

                $res[$key] = data2empty($value);
            }else{
                $res[$key] = $value == '' || $value == null?'':$value;

            }
        }
    }else{
        $res =  $data == '' || $data == null?'':$data;
    }
    return $res;

}


/**
 * note:获取用户信息
 * auth:YW
 * date:2018/01/07
 * input: $module 模块名称
 * return: array
 */
function getUser($module = '')
{
    $user = json_decode(cookie($module . '_info'),1);
    return $user;
}

/**
 * note:存储用户信息
 * auth:yw
 * date:2018/09/13
 * input: $module 模块名称，$data 用户信息，$config 配置文件
 * return: void
 */
function putUser($module = '',$data = '',$config = '')
{
    if (empty($config['timeout'])) $config['timeout'] = '30';
    $conf['expire'] = intval(time()+3600*$config['timeout']);
    $conf['path'] = '/';
    cookie($module.'_info',json_encode($data),$conf);
    session($module.'_token',$data['token']);
}

/**
 * auth YW
 * note 文件名
 * date 2018-08-23
 */
function createFile($path,$name)
{
    if (!is_dir($path)) mkdir($path);
    if (!file_exists($path.$name))
    {
        if (!is_writable($path.$name)) chmod($path.$name, 0777);
        $res = @file_put_contents($path.$name, '--'.PHP_EOL, FILE_APPEND);
    }
    if ($res > 0)
    {
        return true;
    }
}


/**
 * note:删除空的数组（最高支持三维数组）
 * auth:YW
 * date:2018/07/13
 */
function paramFormart($data)
{

    foreach ($data as $key => $value)
    {
        if (is_array($value))
        {

            foreach ($value as $ky => $vl)
            {
                if ($vl = '' || $vl = "")
                {
                    unset($value[$vl]);
                }else{
                    $value[$ky] = $vl;
                }
            }
            $data[$key] = $value;
        }else{
            if ($value == '' || $vl = "")
            {

                unset($data[$key]);
            }else{

                $data[$key] = $value;
            }

        }
    }
    return $data;
}
/**
 * note:订单编号
 * auth:杨炜
 * return string
 */
function get_str_guid()
{
    $charid = strtoupper(md5(uniqid(mt_rand(), true)));

    $hyphen = chr(45);// "-"
    $uuid = substr($charid, 0, 8) . $hyphen
        . substr($charid, 8, 4) . $hyphen
        . substr($charid, 12, 4) . $hyphen
        . substr($charid, 16, 4);
    return $uuid;
}




/**
* note:获取用户信息
* $obj 模型对象,$uid 用户id
* auth:YW
* date:2018/02/1
*/
function get_name($obj, $whrer, $field = 'username')
{
    $res = $obj->where($whrer)->value($field);
    return $res;
}

/**
 * note:无限分类(下拉样式)
 * auth:杨炜
 * input $obj 模型 $table对象 $current 选中的id $parentid父id $count累加次数 $field特殊字段
 * return htmlstr
 */
function cate_tree_html($obj, $table = null, $field = array(), $parentid = '0', $count = 0, $current = '')
{

    $obj = empty($table) ? $obj : $obj->table($table);
    $where[$field['pid']] = $parentid;
    $where[$field['status']] = '1';
    $res = $obj->where($where)->select();
    if (empty($res)) return false;
    $optionHtml = '';
    $linstr = str_repeat("——|", $count);

    foreach ($res as $key => $value) {
        if ($value[$field['pid']] == $parentid) {
            //$optionHtml .= "<option value='{$value[$field['id']]}'>{$linstr} {$value[$field['title']]}</option>";
            if ($value['id'] == $current) {
                $optionHtml .= "<option selected value='{$value[$field['id']]}'>{$linstr} {$value[$field['title']]}</option>";
            } else {
                $optionHtml .= "<option value='{$value[$field['id']]}'>{$linstr} {$value[$field['title']]}</option>";
            }

            $optionHtml .= cate_tree_html($obj, $table, $field, $value[$field['id']], $count + 1, $current);
        }
    }
    return $optionHtml;
}


/**
 * note:生成指定长度的纯数字字符串
 * auth:杨炜
 * input 长度
 * return $iden 前缀标识 $len长度
 */
function rand_str($iden = '', $len = 16, $group = 4)
{
    $str = '';
    for ($j = 1; $j <= $len; $j++) {
        if ($j % $group == 0) {
            $str .= mt_rand(0, 9) . '-';
        } else {
            $str .= mt_rand(0, 9);
        }
    }
    $str = trim($str, '-');
    return $str;
}


/**
 * note:获取分类的顶级分类
 * auth:杨炜
 * input $obj 模型 $table对象 $where 条件
 * return htmlstr
 */
function get_top_type($obj, $table = null, $where = null)
{
    $obj = empty($table) ? $obj : $obj->table($table);
    $res = $obj->where($where)->select();
    return $res;
}

/**
*note:获取分类信息串（通用）
*auth:YW
*date:2018/06/25
*return str
*/
function get_type_str($obj, $table = '', $pid, $field, $field_str = array())
{

    $where[$field_str['condition']] = $pid;
    $str = '';
    if (empty($table))
    {
        $res = $obj->where($where)->field($field)->find();

    }else{
        $res = $obj->table($table)->where($where)->field($field)->find();

    }

    if ($res)
    {
        $str .= get_type_str($obj, $table, $res[$field_str['pid']], $field, $field_str).'>';
        $str .= $res[$field_str['flag']];

        return trim($str,'>');
    }

}

/**
 *note:二维数组随机合并
 *auth:YW
 *date:2018/06/25
 *return str
 */
function shuffleMergeArray($array1, $array2)
{
    $mergeArray = array();
    $sum = count($array1) + count($array2);
    for ($k = $sum; $k > 0; $k--) {
        $number = mt_rand(1, 2);
        if ($number == 1) {
            $mergeArray[] = $array2 ? array_shift($array2) : array_shift($array1);
        } else {
            $mergeArray[] = $array1 ? array_shift($array1) : array_shift($array2);
        }
    }
    return $mergeArray;
}

/**
 * @auth YW
 * @date 2019.03.27
 * @purpose 写入文章id到主表
 * @return void
 */
function addArticleMainId($obj,$table = '',$id = '',$data = '')
{
    $validate = array('fwy_lawyer_shortxt');
    $data_['sid'] = $id?$id:$obj->getLastInsID();
    $data_['add_time'] = time();
    $data_['table'] = 'fwy_'.$table;
    $data_['status'] = in_array($data_['table'],$validate)?'1':'0';
    $data_['tag'] = in_array($data_['table'],$validate)?'f':$data['tag'];
    $res = $obj->insert($data_);
    return $res;
}
/**
 * @auth YW
 * @date 2019.03.27
 * @purpose 修改文章主表
 * @return void
 */
function editArticleMainId($obj,$id,$status)
{
    $where['sid'] = array('in',$id);
    $data['status'] = $status;
    $res = $obj->where($where)->update($data);
    return $res;
}
/**
 * @auth YW
 * @date 2019.03.27
 * @purpose 删除文章主表信息
 * @return void
 */
function delArticleMainId($obj,$id)
{
    $where['sid'] = array('in',$id);
    $res = $obj->where($where)->delete();
    return $res;
}
/**
 * @auth YW
 * @date 2019.04.04
 * @purpose 数组排序
 * @return void
 */
function arraySort($data,$key,$sort = 'asc')
{

}

/**
 * @auth YW
 * @date 2019.04.14
 * @purpose 数组追加url
 * @return array
 */
function array2addUrl($data,$param)
{
    foreach ($data as $key => $value)
    {
        $data[$key]['url'] = $param['weburl'];
    }
    return $data;
}
