<?php
namespace app\admin\controller;
use think\Controller;

class Login extends Controller{
    // 登录
    public function login(){
        if(request()->isAjax()&&request()->isPost()){
            $data = input('post.');
            // dump($data);
            // 非空验证
            if(empty($data['admin_name'])){
                fail('账户必填');
            }
            if(empty($data['admin_pwd'])){
                fail('密码必填');
            }
//            if(empty($data['code'])){
//                fail('验证码必填');
//            }else if(!captcha_check($data['code'])){
//                fail('验证码不正确');
//            }
            // 条件
            $where = [
                'admin_name' => $data['admin_name'],
            ];
            $arr = model('Admin')-> where($where)-> find();
            // dump(json_decode(json_encode($arr),true));die;
            if(empty($arr)){
                fail('账号或密码不正确');
            }else{
                // 密码加密与数据库的比较
                $salt = $arr['salt'];
                $pwd = createPwd($data['admin_pwd'],$salt);
//                dump($salt)."<br />";
//                dump($pwd);die;
                if($pwd === $arr['admin_pwd']){
                    // 登录成功  session赋值
                    $adminInfo = [
                        'admin_id' => $arr['admin_id'],
                        'admin_name' => $arr['admin_name']
                    ];
                    session('adminInfo',$adminInfo);
                    succession('登录成功');
                }else{
                    fail('账号或密码不正确');
                }
            }
        }else{
            $this->view->engine->layout(false);
            return view();
        }
    }

    // 退出
    public function quit(){
        // 删除session
        session('adminInfo',null);
        $this->redirect(url('Login/login'));
    }
}

?>