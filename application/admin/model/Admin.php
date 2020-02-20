<?php
namespace app\admin\model;
use think\Model;

class Admin extends Model{
    // 添加盐值列名
    // 定义一个盐值属性  方便下边赋值
    protected $salt;

    // 密码加密
    public function setAdminPwdAttr($value){
        // 调用函数获取盐值
        $this->salt = $salt = createSalt();
//         echo $salt."<br>";
        
        // 特有方式加密密码
        $pwd = createPwd($value,$salt);
//         echo $pwd;die;
        return $pwd;
    }

    // 数据完成  盐值赋值
    public function setSaltAttr(){
        return $this->salt;
    }

}
?>