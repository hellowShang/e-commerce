<?php
namespace app\index\validate;
use think\Validate;

class User extends Validate{
    // 规则验证
    protected $rule = [
        'user_email' => 'require|email|unique:user|checkEmail',
        'user_code' => 'require|checkCode',
        'user_pwd' => 'require|checkPwd',
        'user_pwd1' => 'require|checkPwd1',
    ];

    // 错误提示
    protected $message = [
        'user_email.require' => '邮箱账号必填！',
        'user_email.email' => '邮箱格式不正确！',
        'user_email.unique' => '该邮箱账号已注册过了哦！',
        'user_code.require' => '验证码必填！',
        'user_pwd.require' => '密码必填！',
        'user_pwd1.require' => '确认密码必填！',
    ];

    // 验证场景
    protected $scene = [
        'email' => ['user_email' => 'require|email|unique:user'],
        'register' => ['user_email','user_code','user_pwd','user_pwd1'],

    ];

    // 验证邮箱账号
    public function checkEmail($value,$rule,$data){
        if($value != session('userInfo.userEmail')){
            return '前后俩次邮箱账号不一致';
        }else{
            return true;
        }
    }

    // 验证码验证
    public function checkCode($value,$rule,$data){
        if($value != session('userInfo.userCode')) {
            return '验证码不正确';
        }else if(time()-session('userInfo.userTime')>300){
            return '验证码已失效，五分钟内有效';
        }else{
            return true;
        }
    }

    // 验证密码
    public function checkPwd($value,$rule,$data){
        $reg = "/^.{6,12}$/";
        if(!preg_match($reg,$value)){
            return '密码格式不正确';
        }else{
            return true;
        }
    }

    // 验证确认密码
    public function checkPwd1($value,$rule,$data){

        if($value != $data['user_pwd']){
            return '俩次密码输入不一致';
        }else{
            return true;
        }
    }
}