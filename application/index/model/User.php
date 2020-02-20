<?php
namespace app\index\model;
use think\Model;

class User extends Model{
    // 关闭自动写入update_time字段
    protected $updateTime = false;

    // 密码加密
    public function setUserPwdAttr($value)
    {
        return md5($value);
    }

}

?>