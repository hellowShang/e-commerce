<?php
namespace app\admin\controller;

class Cate extends Common{
    public function cateList(){

        // 第一种---路径
        $sql = "select *,concat(path,'-',cate_id) as new_path from shop_cate order by new_path asc";
        $cateInfo = model('Cate')->query($sql);
        $this-> assign('cateInfo',$cateInfo);

        // 第二种---递归
        // 看category

        return view();
    }
}


?>