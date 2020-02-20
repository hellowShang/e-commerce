<?php
namespace app\admin\controller;

header("content-type:text/html;charset=utf-8");


class Category extends Common{
    // 唯一性验证
    public function checkName(){
        $cate_name = input('post.cate_name');
        $type = input('post.type');
        if($type == 1){
            $where = [
                'cate_name' => $cate_name
            ];
        }else{
            $where = [
                'cate_id' => ['neq',input('post.cate_id')],
                'cate_name' => $cate_name
            ];
        }
        
        // echo $cate_name;
        $res = model('Category')->where($where)->find();
        if(empty($res)){
            echo 'ok';
        }else{
            echo 'no';
        }
    }

    // 分类添加
    public function categoryAdd(){
        if(request()->isAjax()&&request()->isPost()){
            $data = input('post.');
            $validate = validate('Category');
            $result = $validate-> check($data);
            if(empty($result)){
                fail($validate-> getError());
            }
            $res = model('Category')->save($data);
            if($res){
                succession('添加成功');
            }else{
                fail('添加失败');
            }
        }else{
            // 查询数据库 做下拉的父分类
            $arr = $this-> cateInfo();
            $this-> assign('arr',$arr);
            return view();
        }
    }

    // 分类列表
    public function categoryList(){
        $arr = $this-> cateInfo();
//        dump(json_decode(json_encode($arr), true));die;
        $this-> assign('arr',$arr);
        return view();
    }

    // 分类删除
    public function categoryDel(){
        $cate_id = input('get.id','','intval');
        // echo  $cate_id;die;
        if(empty($cate_id)){
            $this-> error('请正常操作');
        }
        
        // 查询此分类下是否有子类
        $cateWhere = [
            'pid' => $cate_id
        ];
        $count1 = model('Category')-> where($cateWhere)-> count();
        if($count1>0){
            $this-> error('此分类下有子类或商品，无法删除');
        }

        // 查询此分类下是否有商品
        $goodsWhere = [
            'cate_id' => $cate_id
        ];
        $count2 = model('Goods')-> where($goodsWhere)-> count();
        if($count2>0){
            $this-> error('此分类下有子类或商品，无法删除');
        }

        $res = model('Category')-> where($goodsWhere)-> delete();
        if($res){
            $this-> success('删除成功');
        }else{
            $this-> error('删除失败');
        }
    }

    // 即点即改
    public function categoryEdit(){
        $arr = input('post.');
        // dump($arr);
        $where = [
            'cate_id' => $arr['cate_id']
        ];
        $data = [
            'cate_id' => $arr['cate_id'],
            $arr['field'] => $arr['value']
        ];
        // dump($data);die;
        if($arr['field'] == 'cate_name'){
            $validate = validate('Category');
            $result = $validate->scene('edit')->check($data);
            if(empty($result)){
                fail($validate-> getError());
            }
        }
        
        $res = model("Category")-> where($where)->update($data);
        if($res === false){
            fail('操作失败');
        }else if($res == 0){
            fail('内容未更改');
        }else{
            succession("操作成功");
        }
    }

    // 分类修改
    public function categoryUpd(){
        if(request()->isAjax()&&request()->isPost()){
            $data = input('post.');
            // dump($data);
            $result = validate('Category')->check($data);
            if(empty($result)){
                fail(validate("Category")->getError());
            }
            $where = [
                'cate_id' => $data['cate_id']
            ];
            $res = model('Category')-> save($data,$where);
            if($res){
                succession('修改成功');
            }else{
                fail('修改失败');
            }
        }else{
            $cate_id = input('get.id');

            // 查询数据库 做下拉的父分类
            $arr = $this-> cateInfo();
            $this-> assign('arr',$arr);

            $where = [
                'cate_id' => $cate_id
            ];
            $cateInfo = model('Category')-> where($where)-> find();
            // print_r($cateInfo->toArray());die;
            $this->assign('cateInfo',$cateInfo);
            return view();
        }
    }
}


?>