<?php
namespace app\admin\controller;

class Admin extends Common{
    // 管理员名称唯一性验证
    public function checkName(){
        $admin_name = input('post.admin_name');
        $admin_model = model('Admin');
        // 检测类型  1为添加   2为修改
        $type = input('post.type');
        if($type == 1){
            $where = [
                'admin_name' => $admin_name,
            ];
        }else{
            $where = [
                // 不包含本身的唯一性验证
                'admin_id' => ['neq',input('post.admin_id')],
                'admin_name' => $admin_name
            ];
        }
       
        $arr = $admin_model-> where($where)->find();
        if(empty($arr)){
            echo 'ok';
        }else{
            echo 'no';
        }
    }

    // 管理员添加
    public function adminAdd(){
        if(request()->isPost()&&request()->isAjax()){
            // 接收name属性值
            $data = input('post.');

            // validate验证器验证
            $validate = validate("Admin");
            // 验证name属性值
            $result = $validate ->scene('add')->check($data);
            // 返回错误信息
            if(empty($result)){
                // 自己封装的函数 common.php中
                fail($validate-> getError());
            }
            $admin_model = model('Admin');
            $res = $admin_model-> save($data);
            if($res){
                succession('添加成功');
            }else{
                fail('添加失败');
            }
        }else{
            return view();
        }
    }

    // 管理员列表
    public function adminList(){
        return view();
    }

    // 管理员信息
    public function adminInfo(){
        // 当前页码
        $p = input('get.page',1);
        // 每页显示条数
        $page_num = input('get.limit',3);
        // 条件
        $admin_name = input('get.admin_name','');
        $where = [
            'admin_name' => ['like',"%$admin_name%"],
        ];
        $adminInfo = model('Admin')->where($where)->page($p,$page_num)-> select();
        // dump($adminInfo);die;
        // 总条数
        $count = model('Admin')->where($where)->count();
        $info = ['code'=> 0,'msg'=> '','count' => $count,'data' =>$adminInfo];
        echo json_encode($info);
    }

    // 管理员列表即点即改
    public function adminEdit(){
        $value = input('post.value');
        $admin_id = input('post.admin_id');
        $field = input('post.field');

         // 条件
         $where = [
            'admin_id' => $admin_id
        ];
        // 字段和新值
        $data = [
            $field => $value
        ];


        // 验证
            $scene = 'upd'.$field;

            $validate = validate('Admin');
            $result = $validate->scene($scene)->check($data);
            if(empty($result)){
                fail($validate->getError());
            }

        $res = model('Admin')-> where($where)-> update($data);
        if($res){
            succession('操作成功');
        }else{
            fail('操作失败');
        }
    }

    // 管理员删除
    public function adminDel(){
        $admin_id = input('post.admin_id','','intval');
        // 防止id非法篡改
        if(empty($admin_id)){
            $this-> error('请正确操作 - . -#');die;
        }

        $where = [
            'admin_id' => $admin_id
        ];
        $res = model('Admin')-> where($where)-> delete();
        if($res){
            succession('删除成功');
        }else{
            fail('删除失败');
        }
    }

    // 管理员修改
    public function adminUpdate(){
        $admin_id = input('get.admin_id','','intval');

        // 如果id为空 或者不是数字 intval强制转为0并提示
        if(empty($admin_id)){
            $this->error('请正确操作 - . -#');
        }

        $where = [
            'admin_id'=> $admin_id
        ];
        $adminInfo = model('Admin')->where($where)->find();

        // 如果根据id查数据库没有  提示
        if(empty($adminInfo)){
            $this->error('请正确操作 - . -#');
        }
        $this-> assign('adminInfo',$adminInfo);

        return view();
    }

    // 管理员修改执行
    public function adminUpdateDo(){
        $data = input('post.');
        // dump($data);
        // echo $data['admin_id'];die;

        $where = [
            'admin_id' => $data['admin_id'],
        ];
        // dump($where);die;
        // 验证
        $validate = validate('Admin');
        $result = $validate-> scene('update')->check($data);
        if(empty($result)){
           fail($validate->getError());
        }

        $res = model('Admin')->save($data,$where);
        // echo  model('Admin')->getLastSql();die;
        // echo $res;die;
        if(!($res === false)){
            succession('修改成功');
        }else{
            fail('修改失败');
        }

    }

    
}


?>