<?php
namespace app\admin\validate;
use think\Validate;

class Category extends Validate{
    // 规则验证
    protected $rule = [
        'cate_name' => 'require|checkName|unique:category',
    ];

    // 错误提示
    protected $message = [
        'cate_name.require' => '分类必填',
        'cate_name.unique' => '分类名称已存在',
    ];

    // 验证场景
    protected $scene = [
        'edit' => ['cate_name']
    ];

    // 验证分类名
    public function checkName($value,$rule,$data){
        $reg = "/^([\x{4e00}-\x{9fa5}]|[a-z0-9A-Z]){2,10}$/u";
        if(!preg_match($reg,$value)){
            return '分类名称汉字、字母、数字2-10位';
        }else{
            return true;
        }
    }
}

?>