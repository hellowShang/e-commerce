<?php
namespace app\admin\model;
use think\Model;

class Goods extends Model{
       
    public function getIsUpAttr($value){
        $arr = [1=> '√',2=> '×'];
        return $arr[$value];
    }
    public function getIsNewAttr($value){
        $arr = [1=> '√',2=> '×'];
        return $arr[$value];
    }
    public function getIsBestAttr($value){
        $arr = [1=> '√',2=> '×'];
        return $arr[$value];
    }
    public function getIsHotAttr($value){
        $arr = [1=> '√',2=> '×'];
        return $arr[$value];
    }
}

?>