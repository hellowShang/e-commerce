<?php
namespace app\index\controller;

class UserCommon extends Common
{
    public function  __construct()
    {
        parent::__construct();

        // 防非法登录
        // dump(session('?adminInfo'));die;
        if(!session('?info')){
            $this-> error('请先登录',url('Login/login'));
        }
    }

    /**
     * 获取收货地址数据
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAddressInfo(){
        $where = [
            'user_id' => session('info.user_id'),
            'address_status' => 1
        ];
        $addressInfo = model('Address') -> where($where)->select();
        // print_r($areaInfo);die;
        if(!empty($addressInfo)){
            foreach($addressInfo as $k=> $v){
                $addressInfo[$k]['province'] = model('Area')->where(['id'=>$v['province']])-> value('name');
                $addressInfo[$k]['city'] = model('Area')->where(['id'=>$v['city']])-> value('name');
                $addressInfo[$k]['area'] = model('Area')->where(['id'=>$v['area']])-> value('name');
            }
            return $addressInfo;
        }else{
            return false;
        }
    }

}
