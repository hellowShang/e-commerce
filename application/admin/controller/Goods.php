<?php
namespace app\admin\controller;

header("content-type:text/html;charset=utf-8");

class Goods extends Common{
    // 商品名称唯一性验证
    public function checkName(){
        $goods_name = input('post.goods_name');
        $type = input('post.type');
        if($type == 1){
            $where = [
                'goods_name' => $goods_name 
            ];
        }else{
            $where = [
                'goods_id' => ['neq',input('post.goods_id')],
                'goods_name' => $goods_name 
            ];
        }
        $res = model('Goods')-> where($where)-> find();
        if(empty($res)){
            echo 'ok';
        }else{
            echo 'no';
        }
    }

    // 富文本图片上传
    public function goodsEditUpload(){
        // dump($_FILES);die;
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads' .DS. 'goodsimgs');
        if($info){
            // 成功上传后 获取上传信息
            // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
            $info = [
                "code"=> 0,
                "font"=> "上传成功",
                "data"=> [
                    'src' => "/uploads/goodsimgs/".$info->getSaveName(),
                    'title' => ''
                ]     
            ];
            echo json_encode($info);
        }else{
            // 上传失败获取错误信息
            fail($file->getError());
        }
    }

    // 商品图片上传
    public function goodsImgUpload(){
        // dump($_FILES);die;
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads' .DS. 'goodsimgs');
        if($info){
            // 成功上传后 获取上传信息
            // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
            $info = [
                "code"=> 1,
                "font"=> "上传成功",
                'src' =>$info->getSaveName(),  
            ];
            echo json_encode($info);
        }else{
            // 上传失败获取错误信息
            fail($file->getError());
        }
    }

    // 商品添加
    public function goodsAdd(){
        if(request()->isPost()&&request()->isAjax()){
            $data = input('post.');
            // dump($data);
            // 验证
            $validate = validate('Goods');
            $result = $validate->check($data);
            if(empty($result)){
                fail($validate->getError());
            }
            
            $res = model('Goods')->allowField(true)->save($data);
            if($res){
                succession('添加成功');
            }else{
                fail('添加失败');
            }
        }else{
            // 商品信息
            $brandInfo = model('Brand')-> select();
            $this->assign('brandInfo',$brandInfo);

            // 分类信息
            $cateInfo = $this->cateInfo();
            $this->assign('cateInfo',$cateInfo);

            return view();
        }
    }

    // 商品列表
    public function goodsList(){
        // 商品信息
        $brandInfo = model('Brand')-> select();
        $this->assign('brandInfo',$brandInfo);

        // 分类信息
        $cateInfo = $this->cateInfo();
        $this->assign('cateInfo',$cateInfo);
        return view();
    }

    // 商品信息
    public function getGoodsInfo(){
        $p = input('get.page',1);
        $page_num = input('get.limit',3);
        $goods_name = input('get.goods_name');
        $cate_id = input('get.cate_id');
        $brand_name = input('get.brand_name');
        $is_up = input('get.is_up');

        // echo $goods_name;
        // echo $cate_id;
        // echo $brand_name;
        // echo $is_up;die;
        $where =[];
        if(!empty($goods_name)){
            $where['goods_name'] = ['like',"%$goods_name%"];
        }
        if(!empty($cate_id)){
            $cateWhere = [
                'pid' => $cate_id
            ];
            $cate_count = model('Category')->where($cateWhere)->count();
            // echo $cate_count;die;
            if($cate_count > 0){
                $cateInfo = model('Category')->select();
                // dump($cateInfo);die;

                $c_id =  getSonId($cateInfo,$cate_id);
                // $c_id = collection($c_id)->toArray();
                // print_r($c_id);die;
                $where['g.cate_id'] = ['in',$c_id];
            }else{
                $where['g.cate_id'] = $cate_id;
            }
            
        }
        if(!empty($brand_name)){
            $where['brand_name'] = $brand_name;
        }
        if(!empty($is_up)){
            $where['is_up'] = $is_up;
        }
        // dump($where);

        $goodsInfo = model('Goods')
        ->field('g.*,c.cate_name,b.brand_name')
        ->alias('g')
        ->join('shop_category c','g.cate_id=c.cate_id')
        ->join('shop_brand b','g.brand_id = b.brand_id')
        ->where($where)
        ->order('goods_id','asc')
        ->page($p,$page_num)
        ->select();
        // $goodsInfo = collection($goodsInfo)->toArray();
        // dump($goodsInfo);die;
        // echo model('Goods')->getLastSql();
        
        $count = model('Goods')
        ->alias('g')
        ->join('shop_category c','g.cate_id=c.cate_id')
        ->join('shop_brand b','g.brand_id = b.brand_id')
        ->where($where)
        ->count();
        // echo $count;

        $info = [
            'code' => 0,
            'msg' => '',
            'count' => $count,
            'data' => $goodsInfo
        ];
        echo json_encode($info);
    }

    // 即点即改
    public function goodsEdit(){
        $arr = input('post.');
        // dump($arr);die;
        $where = [
            'goods_id' => $arr['goods_id']
        ];
        $data = [
            $arr['field'] => $arr['value']
        ];

        $scene = 'edit'.$arr['field'];
        $validate = validate('Goods');
        $result = $validate-> scene($scene)->check($data);
        if(empty($result)){
            fail($validate->getError());
        }

        $res = model('Goods')-> where($where)-> update($data);
        if($res === false){
            fail('操作失败');
        }else if($res == 0){
            fail('内容未变化');
        }else{
            succession('操作成功');
        }
    }

    // 商品删除
    public function goodsDel(){
        $goods_id = input('post.goods_id');
        // echo $goods_id;die;
        $where = [
            'goods_id' => $goods_id
        ];

        $goods_img = model('Goods')-> where($where)-> value('goods_img');
        // dump(model('Goods')->getLastSql());
        // dump($goods_img);die;
        $goods_imgs = model('Goods')->where($where)->value('goods_imgs');
        $arr = explode('|',$goods_imgs);
        $res = model('Goods')-> where($where)-> delete();
        if($res){
            // 删除商品图片
            $path = "uploads/goodsimgs/".$goods_img;
            unlink($path);
            // 删除商品相册
            foreach($arr as $k=>$v){
                if($v != ''){
                    unlink("uploads/goodsimgs/".$v);
                }
            }
            succession('删除成功');
        }else{
            fail('删除失败');
        }
    }

     // 商品修改
     public function goodsUpd(){
        $goods_id = input('get.goods_id','','intval');
        // echo  $goods_id;
        if(empty($goods_id)){
            $this-> error('请正常操作');
        }
        $where = [
            'goods_id' => $goods_id
        ];
        $goodsInfo = model('Goods')-> where($where)-> find();
        // dump($goodsInfo->toArray());die;
        if(empty($goodsInfo)){
            $this-> error('请正常操作');
        }
        $this-> assign('goodsInfo',$goodsInfo);
        
        // 商品信息
        $brandInfo = model('Brand')-> select();
        $this->assign('brandInfo',$brandInfo);

        // 分类信息
        $cateInfo = $this->cateInfo();
        $this->assign('cateInfo',$cateInfo);
        return view();
    }

    // 商品修改执行
    public function goodsUpdDo(){
        $data = input('post.');
        // dump($data);
        // 验证
        $validate = validate('Goods');
        $result = $validate->check($data);
        if(empty($result)){
            fail($validate->getError());
        }
        $where = [
            'goods_id' => $data['goods_id']
        ];
        // dump($data);
        // dump($where);die;
        $url = model('Goods')-> where($where)-> value('goods_img');
        $goods_imgs_url = model('Goods')-> where($where)-> value('goods_imgs');
        // echo $goods_imgs_url;die;
        $arr = explode('|',$goods_imgs_url);
        if($data['goods_imgs'] == ''){
            $data['goods_imgs'] = $data['old_goods_imgs'];
        }
        $res = model('Goods')->allowField(true)-> save($data,$where);
        if($res){
            // 如果新图片上传   则删除旧图片 如果没上传，则不删
            if($url != $data['goods_img']){
                $path = "uploads/goodsimgs/".$url;
                unlink($path);
            }

            if($goods_imgs_url != $data['goods_imgs']){
                foreach($arr as $k=>$v){
                    if($v != '' ){
                        $path = "uploads/goodsimgs/".$v;
                        unlink($path);
                    }
                }
            }
            succession('修改成功');
        }else{
            fail('修改失败');
        }
    }
}


?>