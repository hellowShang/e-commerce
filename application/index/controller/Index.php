<?php
namespace app\index\controller;

header("content-type:text/html;charset=utf-8");

class Index extends Common
{
    public function index()
    {
        // 查询分类列表 做左侧展示
        $this-> getLeftCateInfo();
        
        // 处理楼层
        $cate_id =1;
        $floorInfo = $this->getFloorInfo($cate_id);
        // dump(collection($floorInfo['goodsInfo'])->toArray());die;
        $this->assign('floorInfo',$floorInfo);
        return view(); 
    }

    // 处理楼层--一楼起
    public function getFloorInfo($cate_id){
        // 获取顶级分类--一楼的（一条）
        $topWhere=[
            'cate_id'=>$cate_id
        ];
        $data['topCate'] = model('Category')->field('cate_id,cate_name')->where($topWhere)->find();
        // dump($topCate->toArray());die;

        // 获取二级子类数据
        $data['cateInfo'] = model('Category')-> where(['pid'=> $cate_id])->select();
        // dump($data['cateInfo']);die;
        
        // 获取商品数据（获取当前分类下的所有子类id）
        $cateInfo = model('Category')->select();
        $c_id = getSonId($cateInfo,$cate_id);
        // dump(collection($info)->toArray());die;
        $goodsWhere = [
            'cate_id' => ['in',$c_id]
        ];
        // dump($goodsWhere);die;
        $data['goodsInfo'] = model('Goods')->where($goodsWhere)->select();
        // dump(collection($data['goodsInfo'])->toArray());die;

        return $data;
    }

    // 获取更多楼层数据
    public function getMoreFloorInfo(){
        $data = input('post.');
        $where = [
            'pid'=> 0,
            'cate_id' => ['>',$data['cate_id']]
        ];
        
        // 按升序方式取最小的顶级分类
        $cate_id = model('Category')->where($where)->order('cate_id','asc')->limit(1)->value('cate_id');
        if($cate_id == ''){
            echo 'no';die;
        }

        // 楼层信息
        $info = $this-> getFloorInfo($cate_id);
        // print_r(collection($info)->toArray());die;

        // 楼层数
        $floorNum = $data['floorNum']+1;
        $this-> assign('floorInfo',$info);
        $this-> assign('floorNum',$floorNum);
        
        // 临时关闭layout界面
        $this->view->engine->layout(false); 
        echo $this-> fetch('div');
    }

    // 首页点击进入详情页
    public function getClick(){
        $cate_name = input('post.cate_name');
        $where = [
            'cate_name' => $cate_name
        ];
        $goods_id = model('Category')
        ->join('shop_goods','shop_goods.cate_id=shop_category.cate_id')
        ->where($where)
        ->value('goods_id');
        echo $goods_id;
    }

}
