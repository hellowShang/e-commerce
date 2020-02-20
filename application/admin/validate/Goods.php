<?php
namespace app\admin\validate;
use think\Validate;

class Goods extends Validate{
    // 规则验证
    protected $rule = [
        'goods_name' => 'require|checkName|unique:goods',
        'self_price' => 'require|number',
        'market_price' => 'require|number',
        'goods_num' => 'require|number',
        'goods_score' => 'require|number',
        'goods_desc' => 'require',
    ];

    // 错误提示
    protected $message = [
        'goods_name.require' => '商品名称必填',
        'goods_name.unique' => '商品名称已存在',
        'self_price.require' => '本店售价必填',
        'self_price.number' => '本店售价为纯数字',
        'market_price.require' => '市场售价必填',
        'market_price.number' => '市场售价为纯数字',
        'goods_num.require' => '库存必填',
        'goods_num.number' => '库存为纯数字',
        'goods_score.require' => '赠送积分必填',
        'goods_score.number' => '赠送积分为纯数字',
        'goods_desc.require' => '商品详情必填',

    ];

    // 验证场景
    protected $scene = [
        'editgoods_name' => ['goods_name'],
        'editself_price' => ['self_price'],
        'editgoods_num' => ['goods_num'],
        'editgoods_score' => ['goods_score'],
    ];

    // 验证商品名
    public function checkName($value,$rule,$data){
        $reg = "/^([\x{4e00}-\x{9fa5}]|[\w|\-]){1,17}$/u";
        if(!preg_match($reg,$value)){
            return '商品名称汉字、字母最多17位';
        }else{
            return true;
        }
    }
}

?>