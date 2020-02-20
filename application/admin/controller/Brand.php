<?php
namespace app\admin\controller;
use think\File;
class Brand extends Common{
    // 验证品牌名称唯一性
    public function checkName(){
        $brand_name = input('post.brand_name');
        $type = input('post.type');
        // 判断添加还是修改
        if($type == 1){
            $where = [
                'brand_name' => $brand_name
            ];
        }else{
            $where = [
                'brand_id' => ['neq',input('post.brand_id')],
                'brand_name' => $brand_name
            ];
        }
        // dump($where);die;
        $res = model('Brand')->where($where)->find();
        if(empty($res)){
            echo 'ok';
        }else{
            echo 'no';
        }

    }

    // 验证品牌网址唯一性
    public function checkUrl(){
        $brand_url = input('post.brand_url');
        $type = input('post.type');
        // 判断添加还是修改
        if($type == 1){
            $where = [
                'brand_url' => $brand_url
            ];
        }else{
            $where = [
                'brand_id' => ['neq',input('post.brand_id')],
                'brand_url' => $brand_url
            ];
        }
        // dump($where);die;
        $res = model('Brand')->where($where)->find();
        if(empty($res)){
            echo 'ok';
        }else{
            echo 'no';
        }

    }

    // 品牌logo上传
    public function brandLogoUpload(){
        // dump($_FILES);die;
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads' .DS. 'brandlogo');
        if($info){
            $brand_id =input('get.brand_id','');
            // 成功上传后 获取上传信息
            // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
            $info = [
                "code"=> 1,
                "font"=> "上传成功",
                "src"=> $info->getSaveName()     
            ];
            echo json_encode($info);
        }else{
            // 上传失败获取错误信息
            $this->fail($file->getError());
        }

    }

    // 品牌添加
    public function brandAdd(){
        if(request()->isPost()&&request()->isAjax()){
            $data = input('post.');
            // dump($data);
            // 验证
            $validate = validate('Brand');
            $result = $validate-> check($data);
            if(empty($result)){
                fail($validate->getError());
            }

            $res = model('Brand')->allowField(true)-> save($data);
            if($res){
                succession('添加成功');
            }else{
                fail('添加失败');
            }
        }else{
            return view();
        }
    }

    // 品牌列表
    public function brandList(){
        return view();
    }

    // 品牌信息
    public function brandInfo(){
       // 当前页码
       $p = input('get.page',1);
       // 每页显示条数
       $page_num = input('get.limit',3);
       // 条件
       $brand_name = input('get.brand_name','');
       $where = [
           'brand_name' => ['like',"%$brand_name%"],
       ];
       $brandInfo = model('Brand')->where($where)->page($p,$page_num)-> select();
        //    dump($brandInfo);die;
       // 总条数
       $count = model('Brand')->where($where)->count();
       $info = ['code'=> 0,'msg'=> '','count' => $count,'data' =>$brandInfo];
       echo json_encode($info);
    }

    // 即点即改
    public function brandEdit(){
        $brand_id = input('post.brand_id');
        $value = input('post.value');
        $field = input('post.field');
        // echo $brand_id;
        // echo $value;
        // echo $field;die;
        $where = [
            'brand_id' => $brand_id
        ];
        $data = [
            $field => $value
        ];

        $scene = 'edit'.$field;
        $result = validate('Brand')->scene($scene)->check($data);
        if(empty($result)){
            fail(validate('Brand')->getError());
        }
        $res = model('Brand')-> where($where)-> update($data);
        if($res){
            succession('修改成功');
        }else{
            fail('修改失败');
        }
    }

    // 品牌删除
    public function brandDel(){
        $brand_id = input('post.brand_id','','intval');
        // echo  $brand_id;
        if(empty($brand_id)){
            $this-> error('请正常操作');
        }
        $where = [
            'brand_id' => $brand_id
        ];
        // 根据id查询图片
        $logo_url = model('Brand')->where($where)->value('brand_logo');
        // dump($data);die;
        $res = model('Brand')-> where($where)-> delete();

        if($res){
            // 拼接路径
            $path = "uploads/brandlogo/".$logo_url;
            // echo $path;die;
            // 删除图片
            unlink($path);
            succession('删除成功');
        }else{
            fail('删除失败');
        }
    }

    // 品牌修改
    public function brandUpd(){
        $brand_id = input('get.brand_id','','intval');
        // echo  $brand_id;
        if(empty($brand_id)){
            $this-> error('请正常操作');
        }
        $where = [
            'brand_id' => $brand_id
        ];
        $brandInfo = model('Brand')-> where($where)-> find();
        if(empty($brandInfo)){
            $this-> error('请正常操作');
        }
        $this-> assign('brandInfo',$brandInfo);
        return view();
    }

    // 品牌修改执行
    public function brandUpdDo(){
        $data = input('post.');
        // dump($data);
        // 验证
        $validate = validate('Brand');
        $result = $validate->check($data);
        if(empty($result)){
            fail($validate->getError());
        }
        $where = [
            'brand_id' => $data['brand_id']
        ];
        // dump($data);
        // dump($where);die;
        $logo_url = model('Brand')-> where($where)-> value('brand_logo');
        $res = model('Brand')->allowField(true)-> save($data,$where);
        if($res){
            // 如果新图片上传   则删除旧图片 如果没上传，则不删
            if($logo_url != $data['brand_logo']){
                $path = "uploads/brandlogo/".$logo_url;
                unlink($path);
            }
            succession('修改成功');
        }else{
            fail('修改失败');
        }
    }
}




?>