<?php
namespace app\lapi\controller\v3;
use app\common\controller\Common;
/**
 * Create by .
 * Cser Administrator
 * Time 11:53
 * Note 说明
 */
class Cert extends Index implements Itf
{


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
     * auth YW
     * note 资料上传
     * date 2018-12-17
     * param
     * param 地区信息province,city,area,和province_cn,city_cn,area_cn,
     */
    public function add()
    {
        if ($_POST)
        {
            $post = $this->request->post();
            $where['uid'] = $post['uid'];
            $res = $this->obj->table('fwy_lawyer')->where($where)->find();
            unset($where);
            if ($res['status'] == '1') {
                return self::returnMsgAndToken('10010','资料审核中,请勿重复上传');
            }

            switch ($post['cert_type'])
            {
                case '1':       //个人认证，律师事务所名称，执业执照，手持身份证，单价，简介
                    $_data['price'] = $post['price'];                //单价(如果没有可以从后台获取默认)
                    break;
                case '2':       //企业认证，企业名称，营业执照，负责人身份证，单价0，简介

                    break;
                case '3':       //机构认证，机构名称，负责人身份证，单价0，简介
                    $_data['price'] = '0';
                    break;
                default:
                    self::returnMsgAndToken('10011','暂时不支持其他认证方式');
                    break;
            }

            $_data['introduction'] = isset($post['introduction'])?$post['introduction']:'';  //简介
            $_data['status'] = 1;                            //审核状态
            if ($_FILES)
            {
                //保存路径
                $path = $this->config['upload'].DS."auth";
                //图片名称
                $obj = new Common();
                $this->config['field'] = 'images';
                $res = $obj->upload($path , $format = 'empty', $maxSize = '52428800', $this->config ,false);
                if (!empty($res[0])) $_data['certa'] = $res[0];
                if (!empty($res[1])) $_data['certb'] = $res[1];
                if (!empty($res[2])) $_data['certc'] = $res[2];
            }
            //验证
            $validate = new \app\lapi\validate\Cert;
            if(!$validate->check($post)){
                self::returnMsgAndToken('10004',$validate->getError());
            }

            $this->obj->startTrans();
            $where['uid'] = $post['uid'];
            $lawyer = $this->obj->table('fwy_lawyer')->where($where)->update($_data);unset($where);

            //添加律师事务所信息到事务所表（添加之前判断是否有相同律师事务所信息，有则把律师事务所id赋给律师）
            $where['name'] = $post['practicelaw'];
            $res = $this->obj->table('fwy_lawfirm')->where($where)->find(); unset($where);
            if (!empty($post['province'])) $data_['province'] = $post['province'];  //城市信息
            if (!empty($post['city'])) $data_['city'] = $post['city'];  //城市信息
            if (!empty($post['area'])) $data_['area'] = $post['area'];  //城市信息
            if (!empty($post['province_cn'])) $data_['province_cn'] = $post['province_cn'];  //城市信息
            if (!empty($post['city_cn'])) $data_['city_cn'] = $post['city_cn'];  //城市信息
            if (!empty($post['area_cn'])) $data_['area_cn'] = $post['area_cn'];  //城市信息

            $data_['extractrate'] = '2';             //抽成
            $data_['name'] = $post['practicelaw'];   //律师事务所名称
            $where['uid'] = $post['uid'];
            if (!$res)
            {
                //添加新的律师事务所信息
                $lawfirm = $this->obj->table('fwy_lawfirm')->insert($data_);
                //把律师事务所id赋给律师
                $lawfirm = $this->obj->where($where)->table('fwy_lawyer')->update(array('lawfirm_id'=>$lawfirm));

            }else{
                //把律师事务所id赋给律师
                $lawfirm = $this->obj->where($where)->table('fwy_lawyer')->update(array('lawfirm_id'=>$res['id']));

            }

            if ($lawyer && $lawfirm) {

                $this->obj->commit();
                self::returnMsgAndToken('10000','上传成功，等待审核');
            } else {
                $this->obj->rollback();
                self::returnMsgAndToken('10013','认证资料提交失败，请重新上传');

            }
        }else{
            self::returnMsgAndToken('10004','请填写认证信息在提交');
        }

    }

    public function del()
    {
        // TODO: Implement del() method.
    }

    public function delall()
    {
        // TODO: Implement delall() method.
    }

    public function edit()
    {
        // TODO: Implement edit() method.
    }
    /**
     * auth YW
     * note 获取行业分类
     * date 2019-01-03
     */
    public function show()
    {
        $post = $this->request->post();
        $post['name_en'] = 'quick';
        if (!$post)
        {
            return self::returnMsgAndToken('10001','缺少参数');
        }
        $where['name_en'] = array('like',"%{$post['name_en']}%");
        $where['status'] = '1';
        $where['iid'] = $this->obj->table('fwy_goods_type')->where($where)->value('id');unset($where['name_en']);
        $res = $this->obj->table('fwy_goods_type')->where($where)->select();
        if ($res)
        {
            foreach ($res as $key => $value)
            {
                $res[$key]['weburl'] = $this->config['weburl'];
            }
            self::returnMsgAndToken('10000','获取成功',$res);
        }else{

            self::returnMsgAndToken('10001','没有找到相关数据！');
        }
    }
    public function showall()
    {
        // TODO: Implement showall() method.
    }

    public function serch()
    {
        // TODO: Implement serch() method.
    }

    /**
     * auth YW
     * note 根据关键字获取信息
     * date 2018-12-17
     * param 关键字[keywords]
     */
    public function getstr()
    {
        $post = $this->request->post();
        if ($post)
        {
            if (!empty(trim(isset($post['keywords'])))) {
                $where['name'] = array('like',"%{$post['keywords']}%");
                $where['status'] = '1';
                $res = $this->obj->table('fwy_lawfirm')->field('id,name')->where($where)->select();
            }
            $res = data2empty($res);
            if ($res) {

                self::returnMsgAndToken('10000','',$res);
            } else {

                self::returnMsgAndToken('10001','没有找到相关数据！');
            }

        }else{

            self::returnMsgAndToken('10004','请输入查询的内容');
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