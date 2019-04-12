<?php
namespace app\admin\controller;
use app\admin\common\controller\Init;
use think\Session;
use think\Db;
use think\Cookie;

/**
 * Create by .
 * Cser Administrator
 * Time 15:25
 * Note 资源管理里
 */

class Img extends Init
{
    function _initialize()
    {
        parent::_init();
        $this->table = $this->config['prefix'].'img';
    }

    /**
     * @auth YW
     * @date 2018.11.23
     * @purpose 图片
     * @return void
     */
    public function img()
    {

        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $where['where']['status'] = '1';
        $res = $this->this_list('',$map);

        $list = array(
            'page' => $res->render(),
            'list' => $res,
            'web_url' => $this->config['weburl'],
        );
        echoMsg('10000','',$list);
    }

    /**
     * @auth YW
     * @date 2018.11.19
     * @purpose 图片上传
     * @return void
     */
    public function upload()
    {
        $file = request()->file('image');
        $img = '';
        if (is_array($file))
        {
            foreach ($file as $key => $value)
            {
                $img[$key] = $this->mvfile($value);
            }
        }else{
            $img[] = $this->mvfile($file);
        }

        $imgArr = array(
            'url' => $this->config['web_url'],
            'img' => $img,
        );

        if ($this->savefile($img))
        {
            echoMsg('10000',$this->message['upload_success'],$imgArr);
        }else{
            echoMsg('10001',$this->message['upload_fail']);
        }

    }
    /**
     * @auth YW
     * @date 2018.11.21
     * @purpose 图片上传
     * @return void
     */
    private function mvfile(&$files)
    {
        $path = ROOT_PATH.strtolower($this->config['upload']).DS.date('Y-m-d',time());
        $info = $files->validate(['size'=>10240000,'ext'=>'jpg,png,gif'])->rule('uniqid')->move($path);
        if ($info)
        {
            $img['info'] = $info->getInfo();
            $img['path'] = DS.$this->config['upload'].DS.date('Y-m-d',time()).DS.$info->getSaveName();
            return $img;
        }else{

            echoMsg('10001',$info->getError());
        }
    }
    /**
     * @auth YW
     * @date 2018.11.22
     * @purpose 保存图片
     * @return void
     */
    private function savefile(&$list)
    {
        $count = count($list);
        $i = 0;
        foreach ($list as $ky => $vl)
        {
            $data['image'] = $vl['path'];
            $data['type'] = $vl['info']['type'];
            $data['key'] = $vl['info']['key'];
            $data['title'] = $vl['info']['name'];
            $data['size'] = $vl['info']['size'];
            $data['status'] = '1';
            $data['add_time'] = time();
            $res = $this->obj->table($this->table)->insert($data);
            if ($res)
            {
                $i++;
            }
        }
        if ($count == $i){
            return true;
        }
    }
    /**
     * @auth YW
     * @date 2018.11.28
     * @purpose 删除图片
     * @return void
     */
    private function delfile(&$list)
    {

    }

    /**
     * @auth YW
     * @date 2017.12.6
     * @purpose 查(分页，排序)
     * @return void
     */
    private function this_list($model = '', $map = '', $sortBy = '', $asc = false,$limit = 10){
        //如果没有设置模型对象，则获取当前的模型对象
        $model = $this->obj->table($this->table);
        //如果没有传入排序条件，默认根据id排序
        if(isset($_REQUEST['_order']))
        {
            $order = $_REQUEST['_order'];
        }else{
            $order = !empty($sortBy)?$sortBy:'id';
        }
        //如果没有传入排序条件，默认根据某种顺序排序
        if (isset($_REQUEST ['_sort'])) {
            $sort = $_REQUEST ['_sort'] ? 'asc' : 'desc';
        } else {
            $sort = $asc ? 'asc' : 'desc';
        }
        $page = $this->request->param('page');
        $list = $model
            ->where($map)
            ->order($order.' '.$sort)
            ->paginate(10,false,['type' => 'Bootstrap','var_page' => 'page','page' => $page,'path'=>'javascript:ajaxpage([PAGE])']);
        if(method_exists($this,"_after_list")){
            $list = $list->items();
            $this->_after_list($list);
        }

        return $list;
    }

    /**
     * @auth YW
     * @date 2019.02.28
     * @purpose 视频
     * @return void
     */
    public function vedio()
    {

    }
}