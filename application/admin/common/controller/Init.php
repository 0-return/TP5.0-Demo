<?php
namespace app\admin\common\controller;
use app\common\controller\Common;
use think\Controller;
use think\Db;
use think\Hook;
use think\Cookie;
use think\Request;
use think\Config;

class Init extends Controller
{
    public $obj;                //系统对象
    public $config;                 //整体配置信息
    public $message;
    public $table;                 //数据库名称
    public $user;
    public $request;
    public $db;


    /**
     * @auth YW
     * @date 2017.12.2
     * @purpose 初始化
     * @return void
     */
    public function _init()
    {

        $this->db = [1 => 'DB_C1',2 => 'DB_C2',];                           //数据库
        $this->request = Request::instance();                               //请求对象
        $this->obj[1] = Db::connect(config("database.{$this->db[1]}"));
        $this->user = json_decode(Cookie::get($this->request->module().'_info'),1);

        $this->config = $this->obj[1]->table('db_config_system')->find();
        $config = Config::get('database');
        $this->config['prefix'] = $config[$this->db[1]]['prefix'];

        Hook::listen('app_msg',$this->message);
        Hook::listen('app_login',$this->request,$this->config);
        Hook::listen('app_rabc',$this->request);

        //控制器/方法
        $this->config['mca'] = strtolower('/'.$this->request->controller().'/'.$this->request->action());
        $this->assign('crumbs',$this->crumbs($this->config['mca']));

    }

    /**
     * @auth YW
     * @date 2018.11.8
     * @purpose 面包屑导航
     * @return void
     */
    private function crumbs($ca = '',$pid = '',$str = '')
    {

        if (!empty($ca))
        {
            $where['mca'] = $ca;
        }elseif(!empty($pid)){
            $where['id'] = $pid;
        }else{
            return ;
        }

        $res = $this->obj[1]->table($this->config['prefix'].'admin_auth')->where($where)->field('pid,title,mca')->find();

        if ($res)
        {
            $str .= $this->crumbs('',$res['pid'],$str);
            $str .= '<span class="c-gray en">&gt;</span><a href="javascript:;">'.$res['title'].'</a>';
        }

        return $str;
    }

    /**
     * @auth YW
     * @date 2017.12.2
     * @purpose 首页
     * @return void
     */
    public function index() {
        $model = $this->getModel();
        $map = $this->_condition($model);

        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        if (!empty($model)) {
            $this->_list($model, $map);
        }
        return view();

    }
    /**
     * @auth YW
     * @date 2017.12.4
     * @purpose 任何操作[Ajax]
     * @return void
     */
    public function Anything()
    {

        if (method_exists($this, '_before_anything')) {
            $res = $this->_before_anything();
        }
        if (method_exists($this, '_after_anything')) {
            $this->_after_anything($res);
        }
        return view($res['tpl']);
    }
    /**
     * @auth YW
     * @date 2017.12.4
     * @purpose 添加[Ajax]
     * @return void
     */
    public function addByAjax()
    {

        if (method_exists($this, "_before_add")) {
            $this->_before_add($_POST);
        }

        if ($this->request->isPost()) {

            $model = !empty($model) ? $model : $this->getModel();

            $res = $model->insert($_POST);
            $id = $model->getLastInsID();
            if ($res) {                 //保存成功
                self::log();
                if (method_exists($this, "_after_add")) {
                    $this->_after_add($id);
                }else{
                    echoMsg('10000',$this->message['success']);
                }
            } else {
                echoMsg('10001',$this->message['error']);
            }
        }else{
            return view('add');
        }

    }
    /**
     * @auth YW
     * @date 2017.12.4
     * @purpose 删[Ajax]
     * @return void
     */
    public function deleteByAjax()
    {
        if(method_exists($this, "_before_delete")){
            $this->_before_delete($_REQUEST);
        }
        $model = !empty($model)?$model:$this->getModel();
        if (!empty($model)) {
            $pk = $model->getPk($this->table);
            $id = $_REQUEST [$pk];

            if (isset($id)) {
                if (is_array($id))
                {
                    $condition = array($pk => array('in', $id));
                }else{
                    $condition = array($pk => array('in', explode(',', $id)));
                }

                $list = $model->where($condition)->update(['status'=> -1]);
                if ($list !== false) {
                    if(method_exists($this, "_after_delete")){
                        self::log();
                        $this->_after_delete($id);
                    }else{
                        echoMsg('10000',$this->message['success']);
                    }
                } else {
                    echoMsg('10001',$this->message['error']);
                }
            } else {
                echoMsg('10001',$this->message['fail']);
                exit(0);
            }
        }
    }
    /**
     * @auth YW
     * @date 2017.12.4
     * @purpose 根据id显示
     * @return void
     */
    public function showById() {
        if(method_exists($this, "_before_edit")){
            $this->_before_edit($_REQUEST);
        }

        $model = !empty($model)?$model:$this->getModel();
        $id = $_REQUEST [$model->getPk($this->table)];
        $res = $model->getById($id);
        if(method_exists($this, "_after_edit")){
            $this->_after_edit($res);
        }
        $this->assign('vo', $res);
        return view('edit');
    }
    /**
     * @auth YW
     * @date 2017.12.4
     * @purpose 改[Ajax]
     * @return void
     */
    public function updateByAjax()
    {

        if(method_exists($this, "_before_update")){
            $this->_before_update($_POST);
        }

        $model = $this->getModel();
        $pk = $model->getPk($this->table);
        $map[$pk] = $_REQUEST[$pk];
        $model = $this->getModel();
        $list = $model->where($map)->update($_POST);
        //echo $model->getLastSql();exit;
        if (false !== $list) {
            self::log();
            if(method_exists($this, "_after_update")){
                $this->_after_update($_REQUEST[$pk]);
            }else{

                echoMsg('10000',$this->message['success']);
            }
        } else {
            echoMsg('10001',$this->message['error']);
        }
    }
    /**
     * @auth YW[可指定字段进行修改]
     * @date 2018.12.21
     * @purpose 数字计算
     * @type [setInc,setDec]
     * @return void
     */
    public function calByAjax($obj = '',$pk = '',$param = "")
    {
        if(method_exists($this, "_before_cal")){
            $this->_before_forbid($_REQUEST);
        }

        $model = empty($obj)?$this->getModel():$obj;
        if (!empty($model)) {
            if ($pk == '') $pk = $model->getPk($this->table);
            $id = $_REQUEST [$pk];
            $number = empty($param['number'])?$_REQUEST['number']:$param['number'];
            $type = empty($param['active'])?$_REQUEST['active']:$param['active'];
            $field = empty($param['field'])?$_REQUEST['field']:$param['field'];
            if (isset($id)) {
                if (is_array($id))
                {
                    $condition = array($pk => array('in', $id));
                }else{
                    $condition = array($pk => array('in', explode(',', $id)));
                }
                $list = $model->where($condition)->$type($field, $number);
                if ($list !== false) {
                    self::log();
                    if(method_exists($this, "_after_cal")){
                        $data['str'] = ($str = $type == 'setInc'?'+':'-').$number.' '.$field;
                        $this->_after_cal($data);
                    }else{

                        echoMsg('10000',$this->message['success']);
                    }
                } else {
                    echoMsg('10001',$this->message['error']);
                }
            } else {
                echoMsg('10000',$this->message['fail']);
            }
        }
    }
    /**
     * @auth YW[可指定字段进行修改]
     * @date 2018.11.20
     * @purpose 状态修改
     * @return void
     */
    public function forbid($field = "status"){

        if(method_exists($this, "_before_forbid")){
            $this->_before_forbid($field);
        }
        $model = $this->getModel();
        if (!empty($model)) {
            $pk = $model->getPk($this->table);
            $id = $_REQUEST [$pk];
            $status = $_REQUEST[$field];
            if (isset($id)) {
                if (is_array($id))
                {
                    $condition = array($pk => array('in', $id));
                }else{
                    $condition = array($pk => array('in', explode(',', $id)));
                }
                $list = $model->where($condition)->setField($field, $status);
                if ($list !== false) {
                    self::log();
                    if(method_exists($this, "_after_forbid")){
                        $this->_after_forbid($id);
                    }else{
                        echoMsg('10000',$this->message['success']);
                    }

                } else {
                    echoMsg('10001',$this->message['error']);
                }
            } else {
                echoMsg('10001',$this->message['fail']);
            }
        }
    }

    /**
     * @auth YW
     * @date 2017.12.6
     * @purpose 查询
     * @return void
     */
    protected function _search() {
        $map = $this->_condition($this->getModel());
        return $map;
    }
    /**
     * @auth YW
     * @date 2017.12.6
     * @purpose 拼装搜寻条件
     * @return void
     */
    protected function checkSearch(&$map,$notlike = false){
        $post = $this->request->request();

        if(!empty($post['sfields'])) {
            $sfields = explode(',',trim($post['sfields']));
            $f = '';
            if (!empty($sfields))
            {
                foreach ($sfields as $key => $value)
                {
                    $f .= $value.'|';
                }
               $map[trim($f,'|')] = array('like','%'.trim($post['reunite']).'%');
            }else{
                $module = $this->getModel();
                $pk = $module->getPk($this->table);
                $map[$pk] = ['like',trim($post['reunite'])];
            }
        }else{
            return ;
        }
    }

    /**
     * 公共查询数据方法
     * @param string $modelStr 模型名称（表名称）
     * @param $_where_order_field （条件）
     * @param bool $isReturnResult  是否返回结果
     * @param string $count （总数）
     * @return array
     */
    public function _list($model, $_where, $isreturn = false, $sortBy = '', $asc = false,$limit = '10',$debug = false){
        $order = isset($_REQUEST['_order'])?$_REQUEST['_order']:!empty($sortBy)?$sortBy:'id';
        $sort = isset($_REQUEST ['_sort'])?$_REQUEST ['_sort']:$asc ? 'asc' : 'desc';
        $obj = $model = !empty($model)?$model:$this->getModel();
        //变量赋值
        if (isset($_where['where']))
        {
            $obj = $model->where($_where['where']);
        }
        if (isset($_where['field']))
        {
            $obj = $model->field($_where['field']);
        }
        if (isset($_where['union']))
        {
            $obj = $model->union([$_where['union']],true);
        }

        if (isset($_where['join']))
        {
            $obj = $model->join($_where['join']);

        }
        if (isset($_where['having']))
        {
            $obj = $model->having($_where['having']);
        }
        if (isset($_where['alias']))
        {
            $obj = $model->alias($_where['alias']);
        }
        if (isset($_where['group']))
        {
            $obj = $model->group($_where['group']);
        }
        $obj = $model->order($order.' '.$sort);
        //查询数据集合
        if ($limit == false || $limit == '0' || $limit == '')
        {
            $list = $obj->paginate();
        }else{
            $list = $obj->paginate($limit,false,['query'=>request()->param()]);
        }

        //echo $model->getLastSql();

        $page = $list->render();
        $count = $list->total();
        if(method_exists($this,"_after_list")){
            $list = $list->items();
            $this->_after_list($list);
        }
        if (!$isreturn)
        {

            $this->assign('count',$count);      //获取总记录数
            $this->assign('list', $list);
            $this->assign('page', $page);
        }else{
            return $list;
        }
    }
    /**
     * @auth YW
     * @date 2017.3.6
     * @purpose 获取数据库模型
     * @return void
     */
    public function getModel(){
        return isset($this->model)?$this->model:$this->obj[1]->table($this->table);
    }
    /**
     * @auth YW
     * @date 2017.3.8
     * @purpose 拼装where条件
     * @return void
     */
    private function _condition($model) {
        $map = array();

        foreach ($model->getTableFields() as $key => $val) {
            if (isset($_REQUEST[$val]) && $_REQUEST [$val] != '') {
                $map [$val] = $_REQUEST [$val];
            }
        }
        return $map;
    }
    /**
     * @auth YW
     * @date 2017.3.8
     * @purpose 检查唯一性
     * @return void
     */
    protected function checkUnique($field,$param){
        if(empty($field) or empty($param)){
            echoMsg('10001',$this->message['serch_check']);
            exit(0);
        }else{
            $model = $this->getModel();
            $pk = $model->getPk($this->table);
            $map[$field] = $param;
            $res = $model->field($pk)->where($map)->find();
            if(!empty($res)){

                echoMsg('10001',$this->message['add_check']);
                exit(0);
            }else{
                return true;
            }
        }
    }

    /**
     * @auth YW
     * @date 2018.11.28
     * @purpose 日志
     * @return void
     * $obj 数据模型，$request，$table
     */
    public function log($content = '')
    {
        $obj = new Common();
        $user = getUser($this->request->module());
        $data['uid'] = $user['id'];
        $data['username'] = $user['username'];
        $data['explain'] = 'sys';
        $obj->wLog($this->obj,$this->request,$data,$this->config,$content);
    }


}
