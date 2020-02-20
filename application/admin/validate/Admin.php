<?php
namespace app\admin\validate;
use think\Validate;

class Admin extends Validate{
    // 规则验证
    protected $rule = [
        'admin_name' => 'require|checkName|unique:admin',
        'admin_pwd' => 'require|checkPwd',
        'admin_email' => 'require|email',
        'admin_tel' => 'require|checkTel',
    ];

    // 错误提示
    protected $message = [
        'admin_name.require' => '管理员名称必填',
        'admin_name.unique' => '管理员名称已存在',
        'admin_pwd.require' => '密码必填',
        'admin_email.require' => '邮箱必填',
        'admin_email.email' => '邮箱格式不正确',
        'admin_tel.require' => '手机号必填',
    ];

    // 验证场景
    protected $scene = [
        'add' => ['admin_name','admin_pwd','admin_email','admin_tel'],
        'updadmin_name' => ['admin_name'],
        'updadmin_email' => ['admin_email'],
        'updadmin_tel' => ['admin_tel'],
        'update' => ['admin_name','admin_email','admin_tel']
    ];

    // 管理员名称验证
    protected function checkName($value,$rule,$data){
        $reg = "/^[a-z_]\w{3,11}$/i";
        if(!preg_match($reg,$value)){
            return '管理员名称数字、字母、下划线组成非数字开头4-12位';
        }else{
            return true;
        }
    }

    // 验证密码
    protected function checkPwd($value,$rule,$data){
        $reg = "/^.{6,12}$/";
        if(!preg_match($reg,$value)){
            return "密码6-12位";
        }else{
            return true;
        }
    }

    // 验证手机号
    protected function checkTel($value,$rule,$data){
        $reg = "/^1[345678]\d{9}$/";
        if(!preg_match($reg,$value)){
            return "手机号格式不正确";
        }else{
            return true;
        }
    }
    
}

?>