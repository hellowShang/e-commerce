<?php
namespace app\index\controller;

header("content-type:text/html;charset=utf-8");

class Goods extends Common
{
    // 全部商品
    public function GoodsList()
    {
        // 左侧分类导航
        $this-> getLeftCateInfo();

        // 获取分类id
        $cate_id = input('get.cate_id');
        if(empty($cate_id)){
            session('cate_id',null);
            $brandWhere = 1;
            $priceWhere = 1;
        }else{
            session('cate_id',$cate_id);
            // 在分类表中获取当前分类下的所有子类id
            $cateInfo = model('Category')->select();
            // dump(collection($cateInfo)->toArray());
            $c_id = getSonId($cateInfo,$cate_id);
            // dump($c_id);exit;
            $priceWhere = [
                'cate_id' => ['in',$c_id]
            ];
            // 在商品表中根据查询的分类的id  查询品牌的id
            $cateWhere = [
                'cate_id' => ['in',$c_id]
            ];
            // dump($cateWhere);
            $brand_id = model('Goods')->where($cateWhere)->column('brand_id');

            // id去重
            $brand_id = array_unique($brand_id);
            // dump($brand_id);
            $brandWhere = [
                'brand_id' => ['in',$brand_id]
            ];
        }

        // 根据品牌id  查询品牌表获取品牌名称
        $brandInfo = model('Brand')->where($brandWhere)->select();

        // 获取商品最高价格
        $maxPrice = model('Goods')->where($priceWhere)->value('max(self_price)');
        // dump($maxPrice);die;
        $priceInfo =$this-> priceCut($maxPrice);

        // 获取第一页商品数据和分页
        $p = 1;
        $page_num =12;
        $goodsInfo = model('Goods')
            ->where($priceWhere)
            ->order('goods_num','desc')
            ->page($p,$page_num)
            ->select();

        $count = model('Goods')
            ->where($priceWhere)
            ->count();
        $page = new \page\AjaxPage();

        $str = $page::ajaxpager($p,$count,$page_num,url('Goods/goodsListPage'));

        $checkSession = session('info');
        if(!empty($checkSession)){
            // 从数据库浏览历史表中获取数据
            $historyInfo = $this-> getHistoryDb();
        }else{
            // 从cookie中获取数据
            $historyInfo =$this-> getHistoryCookieInfo();
        }



        $this->assign('brandInfo',$brandInfo);
        $this->assign('priceInfo',$priceInfo);
        $this->assign('goodsInfo',$goodsInfo);
        $this->assign('str',$str);
        $this->assign('count',$count);
        $this->assign('historyInfo',$historyInfo);

        return view();
    }

    // 价格等分为七份--价格区间
    public function priceCut($maxPrice){
        $price = [];
        $avgPrice = $maxPrice/7;
        for ($i=0;$i<6;$i++){
            $start = ceil($avgPrice*$i);
            $end = $avgPrice*($i+1)-0.01;
            // number_format   以千位分隔符方式格式化一个数字
            $price[] = number_format($start,2,'.',',').'-'.number_format($end,2,'.',',');
        }
        $price[] = number_format(ceil($avgPrice*6),2,'.',',').'以上';
        // dump($price);
        return $price;
    }

    // 重新获取价格
    public function getPriceInfo(){
        $brand_name = input('post.brand_name');
        // dump($brand_id);
        $cate_id = session('cate_id');

        $brand_id = model('Brand')->where(['brand_name'=>$brand_name])->value('brand_id');
        // dump($brand_id);die;
        if(empty($cate_id)){
            if(empty($brand_name)){
                $goodsWhere = 1;
            }else{
                $goodsWhere = [
                    'brand_id' => $brand_id
                ];
            }
        }else{
            $cateInfo = model('Category')->select();
            // dump(collection($cateInfo)->toArray());
            $c_id = getSonId($cateInfo,$cate_id);
            // dump($c_id);exit;

            if(empty($brand_name)){
                $goodsWhere = [
                    'cate_id' => ['in',$c_id]
                ];
            }else{
                $goodsWhere = [
                    'brand_id' => $brand_id,
                    'cate_id' => ['in',$c_id]
                ];
            }
        }
        // dump($goodsWhere);die
        $maxPrice = model('Goods')->where($goodsWhere)->value('max(self_price)');
        // dump($maxPrice);die;
        $priceInfo =$this-> priceCut($maxPrice);
        // dump($priceInfo);die;
        echo json_encode($priceInfo);
    }

    // 重新获取商品信息和分页
    public function getGoodsInfo(){
        $p = input('post.p',1);
        $brand_name = input('post.brand_name');
        $price = input('post.price');
        $field = input('post.field');
        $order = input('post.order');
        $cate_id = session('cate_id');
        // dump(strpos($price,'-')) ;die;
        // echo $field;
        // echo '<br>';
        // echo $order;die;
        // 条件
        $where = [];
        // 品牌
        if(!empty($brand_name)){
            $brandWhere = [
                'brand_name' => $brand_name
            ];
            $brand_id = model('Brand')->where($brandWhere)->value('brand_id');
            // dump($brand_id);die;
            $where['brand_id']=$brand_id;
        }
        // 分类
        if(!empty($cate_id)){
            $cateInfo = model('Category')->select();
            // dump(collection($cateInfo)->toArray());
            $c_id = getSonId($cateInfo,$cate_id);
            // dump($c_id);exit;
            $where['cate_id']=['in',$c_id];
        }

        // 价格
        if(!empty($price)){
            if(strpos($price,'-') == false){
                $min = substr($price,0,strpos($price,'以上'));
                $self_price = str_replace(',','',$min);
                // echo $min;die;
                $where['self_price'] = ['egt',$self_price];
            }else{
                $self_price = explode('-',$price);
                $min = str_replace(',','',$self_price[0]);
                $max = str_replace(',','',$self_price[1]);
                $where['self_price']=['between',[$min,$max]];
            }
        }

        // 默认
        // 库存、价格
        if(!empty($field)&&!empty($order)){
            $screen = 1;
        }
        // 新品
        if(!empty($field)&&empty($order)){
            $where[$field] = 1;
        }
        // dump($where);die;
        $page_num =12;
        if($screen == 1){
            $goodsInfo = model('Goods')->where($where)-> order($field,$order)->page($p,$page_num)->select();
            // echo model('Goods')->getLastSql();die;
        }else{
            $goodsInfo = model('Goods')->where($where)->page($p,$page_num)->select();
        }
        $count = model('Goods')
            ->where($where)
            ->count();
        $page = new \page\AjaxPage();

        $str = $page::ajaxpager($p,$count,$page_num,url('Goods/goodsListPage'));

        $this->view->engine->layout(false);
        $this-> assign('goodsInfo',$goodsInfo);
        $this-> assign('str',$str);
        $this-> assign('count',$count);
        $this-> assign('p',$p);

        echo $this-> fetch('div');
    }

    // 商品详情
    public function GoodsDetail()
    {
        // 左侧分类导航
        $this-> getLeftCateInfo();

        // 商品详情查询、
        $goods_id = input('get.goods_id',0,'intval');
        // echo $goods_id;
        if(empty($goods_id)){
            $this-> error('请选择一件商品',url('Index/index'));exit;
        }
        $where = [
            'goods_id' => $goods_id
        ];
        $goodsDetail = model('Goods')
            ->join('shop_brand','shop_brand.brand_id=shop_goods.brand_id')
            ->join('shop_category','shop_category.cate_id=shop_goods.cate_id')
            ->where($where)
            ->find();
        if(empty($goodsDetail)){
            $this-> error('没查询到商品信息哦，亲',url('Index/index'));exit;
        }
        $goodsDetail['goods_imgs'] = explode('|',rtrim($goodsDetail['goods_imgs'],'|'));
        // dump($goodsDetail['goods_imgs']);die;
        $this-> assign('goodsDetail',$goodsDetail);

        // 浏览记录
        $checkSession = session('info');
        if(empty($checkSession)){
            // 浏览器存cookie
            $this-> getHistoryCookie($goods_id);
            // dump(cookie('arr'));
        }else{
            // 浏览记录入数据库
            $this-> getHistory($goods_id);
        }

        return view();
    }

    // 浏览记录存cookie
    public function getHistoryCookie($goods_id){
        $history = cookie('arr');
        $now = time();
        if(!empty($history)){
            $arr = unserialize(base64_decode(cookie('arr')));
            // dump($arr);die;
        }

        $arr[] = [
            'goods_id' => $goods_id,
            'create_time' => $now
        ];
        $str = base64_encode(serialize($arr));
        // dump($str);
        cookie('arr',$str);
    }

    // 浏览数据入库
    public function getHistory($goods_id){
        $user_id = session('info.user_id');
        $data = [
            'user_id' => $user_id,
            'goods_id' => $goods_id
        ];
        $res = model('History')->save($data);

    }

    //从数据库浏览历史表中获取数据
    public function getHistoryDb(){
        // 获取浏览记录的商品id
        $user_id = session('info.user_id');
        // dump($user_id);
        $where = [
            'user_id' => $user_id
        ];
        $goods_id = model('History')->where($where)->order('create_time','desc')->column('goods_id');
        // print_r($goods_id);die;
        if(empty($goods_id)){
            return false;
        }else{
            $goods_id = array_slice(array_unique($goods_id),0,4);
            //  print_r(collection($goods_id)->toArray());die;

            // 获取浏览记录的商品信息
            $goodsWhereId = implode(',',$goods_id);
            // echo $goodsWhereId;die;
            $goodsWhere = [
                'goods_id' => ['in',$goods_id]
            ];
            // dump($goodsWhere);die;
            // 根据条件排序
            $historyInfo = model('Goods')
                -> where($goodsWhere)
                ->order("field(goods_id,$goodsWhereId)",'asc')
                ->select();
            // echo model('Goods')->getLastSql();die;

            return $historyInfo;

        }
    }

    //从cookie中获取数据
    public function getHistoryCookieInfo(){
        $cookie = cookie('arr');
        // 获取浏览记录的商品id
        if(!empty($cookie)){
            $arr = unserialize(base64_decode($cookie));
            $goods_id =[];
            for($i=count($arr)-1;$i>=0;$i--){
                $goods_id[] = $arr[$i]['goods_id'];
            }

            $goods_id = array_slice(array_unique($goods_id),0,4);
            // dump($goods_id);

            // 获取浏览记录的商品信息
            $goodsWhereId = implode(',',$goods_id);
            // echo $goodsWhereId;die;
            $goodsWhere = [
                'goods_id' => ['in',$goods_id]
            ];
            // dump($goodsWhere);die;

            // 根据条件排序
            if(!empty($goodsWhereId)){
                $historyInfo = model('Goods')
                    -> where($goodsWhere)
                    ->order("field(goods_id,$goodsWhereId)",'asc')
                    ->select();
                // echo model('Goods')->getLastSql();die;
                return $historyInfo;
            }
        }
    }
}
