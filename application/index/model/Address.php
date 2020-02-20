<?php
namespace app\index\model;
use think\Model;

class Address extends Model{
    // 关闭自动写入update_time字段
    protected $updateTime = false;

    // 数据完成
    protected $insert = ['user_id'];
    public function setUserIdAttr(){
        return session('info.user_id');
    }
}

?>