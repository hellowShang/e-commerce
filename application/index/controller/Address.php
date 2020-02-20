<?php
namespace app\index\controller;

class Address extends UserCommon
{
    // 添加、展示
    public function address()
    {
        // 收货人地址添加
        if(request()->isPost()&&request()->isAjax()){
            $data = input('post.');

            // 验证
            $validate = validate('Address');
            $result = $validate -> check($data);
            if(empty($result)){
                fail($validate-> getError());
            }
            // 入库
            $address_model = model('Address');
            if($data['is_default'] == 1){
                // 开启事务
                $address_model ->startTrans();
                $where = [
                    'user_id' => session('info.user_id')
                ];
                $result = $address_model->save(['is_default' => 2],$where);
                $res = $address_model->isUpdate(false)->save($data);
                if($res&&$result !== false){
                    //提交
                    $address_model->commit();
                    succession('添加成功');
                }else{
                    // 回滚  驳回
                    $address_model->rollback();
                    fail('添加失败');
                }
            }else{
                $res = $address_model->save($data);
                if($res){
                    succession('添加成功');
                }else{
                    fail('添加失败');
                }
            }
        }else{
            // 三级联动 省数据查询
            $province = $this-> province(0);

            // 获取收货地址数据
            $addressInfo = $this->getAddressInfo();

            $this->assign('province',$province);
            $this->assign('addressInfo',$addressInfo);
            return view();
        }
    }

    // 三级联动 省数据查询
    public function province($pid){
        $provinceInfo = model("Area")-> where(['pid' =>$pid])->select();
        if(empty($provinceInfo)){
            return false;
        }else{
            return $provinceInfo;
        }
    }

    // 市、县数据获取
    public function getArea(){
        $id = input('post.id');
            if(empty($id)){
                exit;
            }
        $areaInfo = model('Area')->where(['pid' =>$id])->select();
        // echo model('Area')->getLastSql();die;
        // print_r($areaInfo);die;
        if(count($areaInfo)){
            echo json_encode(['areaInfo' => $areaInfo,'code' => 6]);
        }else{
            exit;
        }
    }

    // 删除
    public function addressDel(){
        $address_id = input('post.address_id',0,'intval');
        if(!empty($address_id)){
            $res = model('Address')->save(['address_status' => 2],['address_id' => $address_id]);
                if($res){
                    succession('删除成功');
                }else{
                    fail('删除失败');
                }
        }else{
            fail('额，出错了');
        }
    }

    // 设为默认
    public function addressDefault(){
        $address_id = input('post.address_id',0,'intval');
        if(!empty($address_id)){
            $address_model = model('Address');
            // 事务处理
            $address_model->startTrans();
            $result = $address_model->save(['is_default' => 2],['user_id' => session('info.user_id')]);
            $res = $address_model->save(['is_default' => 1],['address_id' => $address_id]);
            if($res&&$result !== false){
                // 提交
                $address_model->commit();
                succession('设为默认成功');
            }else{
                // 回滚  驳回
                $address_model-> rollback();
                fail('设为默认失败');
            }
        }else{
            fail('额，出错了');
        }
    }

    // 编辑
    public function addressUpd(){
        if(request()->isAjax()&&request()->isPost()){
            $data = input('post.');

            // 验证
            $validate = validate('Address');
            $result = $validate -> check($data);
            if(empty($result)){
                fail($validate-> getError());
            }

            // 入库
            $address_model = model('Address');
            $updateWhere = [
                'address_id'=> $data['address_id']
            ];
            if($data['is_default'] == 1){
                // 开启事务
                $address_model ->startTrans();
                $where = [
                    'user_id' => session('info.user_id')
                ];
                $result = $address_model->save(['is_default' => 2],$where);
                $res = $address_model->save($data,$updateWhere);
                if($res !== false &&$result !== false){
                    //提交
                    $address_model->commit();
                    succession('修改成功');
                }else{
                    // 回滚  驳回
                    $address_model->rollback();
                    fail('修改失败');
                }
            }else{
                $res = $address_model->save($data,$updateWhere);
                if($res !== false){
                    succession('修改成功');
                }else{
                    fail('修改失败');
                }
            }
        }else{
            $address_id = input('get.address_id',0,'intval');
            if(!empty($address_id)){
                $where = [
                    'address_id'=> $address_id,
                    'address_status' => 1
                ];

                $addressInfo = model('Address')-> where($where)->find();

                // 三级联动 省数据查询
                $province = $this-> province(0);
                $cityInfo = $this-> province($addressInfo['province']);
                $areaInfo = $this-> province($addressInfo['city']);
                // print_r(collection($areaInfo)->toArray());die;

                // print_r($addressInfo);die;
                $this ->assign('addressInfo',$addressInfo);
                $this ->assign('province',$province);
                $this ->assign('cityInfo',$cityInfo);
                $this ->assign('areaInfo',$areaInfo);
            }else{
                fail('额，出错了');
            }
            return view();
        }
    }
}
