<?php
namespace app\admin\validate;
use think\Validate;

class Brand extends Validate{
    // 规则验证
    protected $rule = [
        'brand_name' => 'require|checkName|unique:brand',
        'brand_url' => 'require|url|unique:brand',
    ];

    // 错误提示
    protected $message = [
        'brand_name.require' => '品牌必填',
        'brand_name.unique' => '品牌名称已存在',
        'brand_url.require' => '品牌网址必填',
        'brand_url.url' => '请输入正确的品牌网址',
        'brand_url.unique' => '品牌网址已存在',
    ];

    // 验证场景
    protected $scene = [
        'editbrand_name' => ['brand_name'],
        'editbrand_url' => ['brand_url'],
    ];

    // 验证品牌名
    public function checkName($value,$rule,$data){
        $reg = "/^([\x{4e00}-\x{9fa5}]|[a-z0-9A-Z]){2,10}$/u";
        if(!preg_match($reg,$value)){
            return '品牌名称汉字、字母、数字2-10位';
        }else{
            return true;
        }
    }
}

?>