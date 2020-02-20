<?php
namespace app\index\controller;
use think\Controller;

class Common extends Controller
{
    public function  __construct()
    {
        parent::__construct();

        $controllerName = request()->controller();
        $this-> assign('controllerName',$controllerName);
    }

    // 左侧分类展示
    public function getLeftCateInfo(){

        // 获取数据库要展示在左侧分类信息
        $cateWhere = [
            "cate_show"=> 1
        ];
        $cateInfo = model('Category')->where($cateWhere)-> select();
        $cateInfo = getLeftCateInfo($cateInfo);
        // dump(collection($cateInfo)->toArray());
        // echo "<hr>";
        $this-> assign('cateInfo',$cateInfo);

        // 获取头部导航栏展示信息
        $navInfo = model('Category')->where(['cate_navshow'=>1])->select();
        // dump(collection($navInfo)->toArray());die;
        $this-> assign('navInfo',$navInfo);


    }

    // 检测数量是否超过库存
    public function checkGoodsNum($goods_id,$old_buy_number,$buy_number,$type=1){
        $where = [
            'goods_id' => $goods_id
        ];
        $goods_num = model('Goods')->where($where)->value('goods_num');
        if(($old_buy_number+$buy_number)>$goods_num){
            $n = $goods_num-$old_buy_number;
            if($type==1){
                fail('库存不足，最多还能购买'.$n.'件');
            }else{
                return false;
            }
        }else{
            return true;
        }
    }

    // 同步浏览记录
    public function syncHistory($user_id){
        $cookieArr = cookie('arr');
        // 检测登录前有没有浏览
        if(!empty($cookieArr)){
            $str = unserialize(base64_decode($cookieArr));
            // dump($str);die;
            foreach($str as $k=>$v){
                $str[$k]['user_id']=$user_id;
            }
            // print_r($str);
            $res = model('History')->saveAll($str);
            if($res){
                cookie('arr',null);
            }
        }
    }

    // 同步购物车记录
    public function syncCart($user_id){
        $cartCookie = cookie('cartInfo');
        // 检测登录前有没有加入购物车
        if(!empty($cartCookie)){
            $str = unserialize(base64_decode($cartCookie));
            // dump($str);
            foreach($str as $k=>$v){
                $where = [
                    'user_id' => $user_id,
                    'goods_id' => $v['goods_id']
                ];
                $cartArr = model('Cart')->where($where)->find();
                // dump($cartArr);
                if(empty($cartArr)){
                    $data = [
                        'goods_id' => $v['goods_id'],
                        'user_id' => $user_id,
                        'buy_number' => $v['buy_number'],
                        'create_time' => time(),
                        'update_time' => time()
                    ];
                    $res = model('Cart')-> insert($data);
                }else{
                    $dataUpdate = [
                        'buy_number' => $cartArr['buy_number']+$v['buy_number'],
                        'update_time' => time()
                    ];
                    $res = model('Cart')-> where($where)->update($dataUpdate);
                }
            }

            if($res){
                cookie('cartInfo',null);
            }
        }
    }

    /**
     * 商品数据获取
     * @param $goods_id
     * @param $type 1为数据库   2为本地cookie
     * @return bool
     */
    public function CartInfo($goods_id,$type){
        $where = [
            'shop_goods.goods_id' => ['in',$goods_id],
            'is_up' => '1'
        ];

        // 判断传过来的时数组还是字符串
        if(is_array($goods_id)){
            $orderWhere = implode(',',$goods_id);
        }else{
            $orderWhere = $goods_id;
        }
        // dump($orderWhere);die;
        if($type == 1){
            $where['user_id'] = session('info.user_id');
            $where['cart_status'] = 1;
            $goodsInfo = model('Goods')
                ->alias('g')
                ->join('shop_cart c','g.goods_id = c.goods_id')
                ->where($where)
                ->order("field(c.goods_id,$orderWhere)")
                ->select();
            // echo model('Goods')->getLastSql();die;
        }else{
            $goodsInfo = model('Goods')
                -> where($where)
                -> order("field(goods_id,$orderWhere)")
                ->select();
            // dump(Collection($goodsInfo)->toArray());die;
        }
        // dump(Collection($goodsInfo)->toArray());die;
        if(empty($goodsInfo)){
            return false;
        }else{
            return $goodsInfo;
        }
    }
}
