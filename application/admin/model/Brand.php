<?php
namespace app\admin\model;
use think\Model;

class Brand extends Model{
    // 获取器 获取数据的字段值后自动进行处理（转换）
    public function getBrandShowAttr($value){
        $arr = [1=>'√',2=>'×'];
        return $arr[$value];
    }
}
?>