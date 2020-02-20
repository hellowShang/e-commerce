<?php
namespace app\index\controller;

use think\Collection;

header("content-type:text/html;charset=utf-8");

class Cart extends Common
{
    // 加入购物车
    public function cartAdd()
    {
        // 验证
        $goods_id = input('post.goods_id', 0, 'intval');
        $buy_number = input('post.buy_number', 0, 'intval');
        if (empty($goods_id)) {
            fail('请选择一件商品');
        } else {
            $where = [
                'goods_id' => $goods_id
            ];
            $goodsInfo = model('Goods')->where($where)->find();
            // dump($goodsInfo);die;
            if (empty($goodsInfo) || ($goodsInfo['is_up'] != '√')) {
                fail('该商品暂无，可能已经下架了哦！');
            }
        }

        if (empty($buy_number)) {
            fail('请选择购买数量');
        }

        // 加入购物车
        if (session('?info')) {
            // 购物车数据入库
            $this->getCart($goods_id, $buy_number);
        } else {
            // 购物车数据存入cookie
            $this->getCartCookie($goods_id, $buy_number);
        }
    }

    // 购物车数据入数据库
    public function getCart($goods_id,$buy_number){
        // 判断数据库有没有该用户的该条商品购买记录
        $cartWhere = [
            'user_id' => session('info.user_id'),
            'goods_id' => $goods_id,
            'cart_status' => 1
        ];
        // dump($cartWhere);die;
        $cartInfo = model('Cart')-> where($cartWhere)-> find();
        // dump($cartInfo);die;

        // 没有
        if(empty($cartInfo)){
            // 检测是否超过库存数量
            $this-> checkGoodsNum($goods_id,0,$buy_number);
            // 添加
            $data = [
                'goods_id' => $goods_id,
                'user_id' => session('info.user_id'),
                'buy_number' => $buy_number
            ];
            $res = model('Cart')->save($data);
        // 有
        }else{
            // 检测是否超过库存数量
            $this-> checkGoodsNum($goods_id,$cartInfo['buy_number'],$buy_number);
            // 修改
            $data = [
                'buy_number' => $cartInfo['buy_number']+$buy_number
            ];
            $res = model('Cart')->save($data,$cartWhere);
        }
        // dump($res);die;
        // 提示
        if($res){
            succession('加入购物车成功');
        }else{
            fail('加入购物车失败');
        }
    }

    // 购物车数据存入cookie中
    public function getCartCookie($goods_id,$buy_number){
        $cookie = cookie('cartInfo');
        $info = [];
        if(empty($cookie)){
            // 检测是否超过库存数量
            $this-> checkGoodsNum($goods_id,0,$buy_number);
            $info[] = [
                'goods_id' => $goods_id,
                'buy_number' => $buy_number,
                'create_time' => time()
            ];
            $str = base64_encode(serialize($info));
            cookie('cartInfo',$str);
        }else{
            $info = unserialize(base64_decode(cookie('cartInfo')));
            $flag = 0;

            // 累加
            foreach($info as $k=>$v){
                // 检测是否超过库存数量
                $this-> checkGoodsNum($goods_id,$v['buy_number'],$buy_number);
                if($v['goods_id'] == $goods_id){
                    $info[$k]['buy_number'] = $v['buy_number']+$buy_number;
                    $info[$k]['create_time'] = time();
                    $str = base64_encode(serialize($info));
                    cookie('cartInfo',$str);
                    $flag = 1;
                }
            }

            // 追加
            if($flag == 0){
                // 检测是否超过库存数量
                $this-> checkGoodsNum($goods_id,0,$buy_number);
                $info[] = [
                    'goods_id' => $goods_id,
                    'buy_number' => $buy_number,
                    'create_time' => time()
                ];
                $str = base64_encode(serialize($info));
                cookie('cartInfo',$str);
            }
        }
        succession('加入购物车成功');
    }

    // 购物车信息展示
    public function cartList()
    {

        // 左侧分类导航
        $this->getLeftCateInfo();

        // 检测是否登录
        if (session('?info')) {
            // 登录    数据库查询信息
            $cartInfo = $this->getCartInfo();
        } else {
            // 没登录    cookie查询信息
            $cartInfo = $this->getCartInfoCookie();
        }

        // dump(collection($cartInfo)->toArray());die;
        $this->assign('cartInfo', $cartInfo);
        return view();
    }

    // 数据库获取购物车数据
    public function getCartInfo(){
        $where = [
            'user_id' => session('info.user_id')
        ];
        $goods_id = model('Cart')-> where($where)->order('create_time','desc')-> column('goods_id');
        // echo model('Cart')->getLastSql();die;

        // 加入收藏
        $collectWhere = [
            'goods_id' => ['in',$goods_id],
            'user_id' =>session('info.user_id')
        ];
        $collectGoodsId = model('Collect')->where($collectWhere)->column('goods_id');
        //  dump(collection($collectGoodsId)->toArray());die;
        $cartInfo = $this-> CartInfo($goods_id,1);
            foreach($cartInfo as $k=>$v){
                if(in_array($v['goods_id'],$collectGoodsId)){
                    $cartInfo[$k]['is_collect'] = '已收藏';
                }else{
                    $cartInfo[$k]['is_collect'] = '加入收藏';
                }
            }
        // dump(collection($collectGoodsId)->toArray());die;
        return $cartInfo;
    }

    // cookie中获取购物车数据
    public function getCartInfoCookie(){
        $cookie_str = cookie('cartInfo');
        if(!empty($cookie_str)){
            $cartInfo = unserialize(base64_decode($cookie_str));
            // dump($cartInfo);die;

           // 获取id
            $goods_id = [];
            for($i=count($cartInfo)-1;$i>=0;$i--){
                $goods_id[] = $cartInfo[$i]['goods_id'];
            }
            $goodsInfo = $this-> CartInfo($goods_id,2);
            // 处理俩个数组
            if(empty($goodsInfo)){
                return false;
            }else{
//                dump($cartInfo);
//                dump(Collection($goodsInfo)->toArray());die;
                foreach($goodsInfo as $k=>$v){
                    if(in_array($v['goods_id'],$goods_id)){
                        foreach($cartInfo as $key=> $val){
                            if($v['goods_id'] == $val['goods_id']){
                                $v['buy_number'] = $val['buy_number'];
                            }
                        }
                    }
                }
                // dump(Collection($goodsInfo)->toArray());die;
                return $goodsInfo;
            }
        }else{
            return false;
        }
    }

    // 购物车加号、减号点击数量改变
    public function changeNum(){
        $goods_id = input('post.goods_id');
        $buy_number = input('post.buy_number');
//        echo $goods_id;
//        echo '<br>';
//        echo $buy_number;
        if(session('?info')){
            // 改数据库
            // 检查库存
            $this->checkGoodsNum($goods_id,0,$buy_number);
            $where = [
                'user_id' => session('info.user_id'),
                'goods_id' => $goods_id
            ];
            $date = [
                'buy_number' => $buy_number
            ];
            $res = model('Cart')->save($date,$where);
        }else{
            // 改存在cookie中的数据
            $cookie_str = cookie('cartInfo');
            if(!empty($cookie_str)){
                $cartInfo = unserialize(base64_decode($cookie_str));
                //dump($cartInfo);die;
                foreach($cartInfo as $k=>$v){
                    if($v['goods_id'] == $goods_id){
                        // 检查库存
                        $this->checkGoodsNum($goods_id,0,$buy_number);
                        // 更改cookie中的购买数量
                        $cartInfo[$k]['buy_number'] =$buy_number;
                    }
                }
                $str = base64_encode(serialize($cartInfo));
                cookie('cartInfo',$str);
            };
        }
    }

    // 购物车结算时总价的计算
    public function getCountPrice(){
        $goods_id = input('post.goods_id');
        if(session('?info')){
            // 数据库的计算
            $where = [
                'c.goods_id' => ['in',$goods_id],
                'user_id' => session('info.user_id'),
            ];
            $info = model('Cart')
                ->field('buy_number,self_price')
                ->alias('c')
                ->join('shop_goods g','c.goods_id = g.goods_id')
                ->where($where)
                ->select();
            // echo model('Cart')-> getLastSql();die;
            // print_r(collection($info)->toArray());die;
            $count = 0;
            foreach($info as $k=>$v){
                $count+=$v['self_price']*$v['buy_number'];
            }
            echo $count;
        }else{
            // cookie的计算
            $cookie_str = cookie('cartInfo');
            if(!empty($cookie_str)){
                $cartInfo = unserialize(base64_decode($cookie_str));
                $goods_id = explode(',',$goods_id);
                $info = [];
                foreach($cartInfo as $k=>$v){
                    if(in_array($v['goods_id'],$goods_id)){
                        $info [] = $v;
                    }
                }

                $count =0;
                foreach($info as $k=> $v){
                    $where = [
                        'goods_id' => $v['goods_id']
                    ];
                    $self_price = model('Goods')->where($where)-> value('self_price');
                    $count += $self_price*$v['buy_number'];
                }
                echo $count;
            }
        }
    }

    // 购物车删除数据
    public function cartDel(){
        $goods_id = input('post.goods_id');
        if(session('?info')){
            $where = [
                'goods_id' => $goods_id,
                'user_id' => session('info.user_id')
            ];
            $date = [
                'cart_status' => 2
            ];
            $res = model('Cart')->where($where)-> update($date);
            if($res){
                succession('删除成功');
            }else{
                fail('删除失败');
            }
        }else{
            $cookie = cookie('cartInfo');
            if(!empty($cookie)){
                $cartInfo = unserialize(base64_decode($cookie));
                foreach($cartInfo as $k=>$v){
                    if($v['goods_id'] == $goods_id){
                        unset($cartInfo[$k]);
                    }
                }

                if(!empty($cartInfo)){
                    $str = base64_encode(serialize(array_values($cartInfo)));
                    cookie('cartInfo',$str);
                }else{
                    cookie('cartInfo',null);
                }
                succession('删除成功');
            }
        }
    }

    // 清空购物车
    public function cartAllDel(){
        if(session('?info')){
            $where = [
                'user_id' => session('info.user_id')
            ];
            $date = [
                'cart_status' => 2
            ];
            $res = model('Cart')->where($where)-> update($date);
            if($res){
                succession('你已成功清空购物车，不会有不开心了');
            }else{
                fail('购物车很干净了，不用再清了哦');
            }
        }else{
            $cookie = cookie('cartInfo');
            if(!empty($cookie)){
                cookie('cartInfo',null);
                succession('你已成功清空购物车，不会有不开心了');
            }else{
                fail('购物车很干净了，不用再清了哦');
            }
        }
    }

    // 收藏
    public function collect(){
        if(session('?info')){
            $goods_id = input('post.goods_id',0,'intval');
            if(!empty($goods_id)){
                $data = [
                    'goods_id' => $goods_id,
                    'user_id' => session('info.user_id')
                ];
                $result = model('Collect')->where($data)->find();
                if(!empty($result)){
                    fail('收藏失败');
                }else{
                    $res = model('Collect')-> save($data);
                    if($res){
                        succession('收藏成功');
                    }else{
                        fail('收藏失败');
                    }
                }
            }else{
                fail('额，出错了呢');
            }
        }else{
            $this-> error('请先登录',url('Login/login'));
        }
    }

}
