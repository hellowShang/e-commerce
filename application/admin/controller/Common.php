<?php
namespace app\admin\controller;
use think\Controller;

class Common extends Controller{

    
    public function __construct(){
        
        // 继承父类的构造方法，方便后边的继承本类的类使用本类父类的东西
        parent::__construct();

        // 防非法登录
        // dump(session('?adminInfo'));die;
        if(!session('?adminInfo')){
            $this-> error('请先登录',url('Login/login'));
        }
    }

    // 品牌分类查询
    public function cateInfo(){
        $cateInfo = model('Category')-> select();
        // $cateInfo = collection($cateInfo)->toArray();
        // print_r($cateInfo);die;
        $arr = getCate($cateInfo);
        // print_r(collection($res)->toArray());die;
        return $arr;
    }

}
?>