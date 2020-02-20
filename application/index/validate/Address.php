<?php
namespace app\index\validate;
use think\Validate;

class Address extends Validate{
    // 规则验证
    protected $rule = [
        'address_name' => 'require|checkName',
        'address_tel' => 'require|number|checkTel',
        'province' => 'require',
        'city' => 'require',
        'area' => 'require',
        'address_add' => 'require',
    ];

    // 错误提示
    protected $message = [
        'address_name.require' => '收货人姓名不能为空',
        'address_tel.require' => '联系方式不能为空',
        'address_tel.number' => '联系方式格式不正确',
        'address_tel.notBetween' => '联系方式6-11位',
        'province.require' => '省不能为空',
        'city.require' => '市不能为空',
        'area.require' => '县不能为空',
        'address_add.require' => '详细地址不能为空',
    ];

    // 验证场景
    protected $scene = [

    ];


    // 验证收货人姓名
    public function checkName($value,$rule,$data){
        $reg = "/^[\x{4e00}-\x{9fa5}]{2,4}$/u";
        if(!preg_match($reg,$value)){
            return '收货人姓名2-4位';
        }else{
            return true;
        }
    }

    // 验证联系方式
    public function checkTel($value,$rule,$data){
        $reg = "/^\d{6,11}$/";
        if(!preg_match($reg,$value)){
            return '联系方式6-11位';
        }else{
            return true;
        }
    }
}