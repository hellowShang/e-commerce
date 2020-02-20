<?php
namespace app\admin\model;
use think\Model;

class Category extends Model{
    public function getCateShowAttr($value){
        $arr = [1=> '√',2=> '×'];
        return $arr[$value];
    }

    public function getCateNavshowAttr($value){
        $arr = [1=> '√',2=> '×'];
        return $arr[$value];
    }
}
?>