<?php
namespace app\index\controller;

use email\Exception;
use think\Collection;

header("content-type:text/html;charset=utf-8");

class Order extends UserCommon
{
    // 检查是否登录
    public  function  checkLogin(){
        $session = session('?info');
        echo json_encode(['status' => $session]);
    }

    // 确认订单页面
    public function confirmCount(){
        // 左侧分类导航
        $this->getLeftCateInfo();

        if(session('?info')){
            // 获取商品数据
            $goods_id = input('get.goods_id');
            if(empty($goods_id)){
                $this-> error('至少选择一件商品才能结算了哦');
            }else{
                $goodsInfo = $this-> CartInfo($goods_id,1);
                $addressInfo = $this-> getAddressInfo();
                $count = 0;
                $score = 0;
                if(!empty($goodsInfo)){
                    // 商品总价
                    foreach($goodsInfo as $k=> $v){
                        $count += $v['self_price']*$v['buy_number'];
                    }

                    // 总积分
                    foreach($goodsInfo as $k=> $v){
                        $score += $v['goods_score']*$v['buy_number'];
                    }
                }
                // dump(collection($addressInfo)->toArray());die;
                $this->assign('count',$count);
                $this->assign('score',$score);
                $this->assign('goodsInfo',$goodsInfo);
                $this->assign('addressInfo',$addressInfo);
            }
        }else{
            $this->error('登录后才能结算商品了哦',url('Login/login'));
        }

        return view();
    }

    // 提交订单页面
    public function submitOrder(){
        if(session('?info')){
            // 接收值并验证
            $goods_id = input('post.goods_id');
            if(empty($goods_id)){
                fail('请选择商品');
            }

            $address_id = input('post.address_id',0,'intval');
            if(empty($address_id)){
                fail('请选择收货地址');
            }

            $pay = [1,2,3];
            $pay_type = input('post.pay_type',0,'intval');
            if(!in_array($pay_type,$pay)){
                fail('请选择支付方式');
            }

            $content = input('post.content');

            // 开启事务
            $order_model = model('Order');
            $order_model->startTrans();

            // 捕获异常
            try{
                $user_id = session('info.user_id');
                // 订单信息 存入订单表
                $orderInfo['order_no'] = $this-> orderNumber();
                $goodsInfo = $this->cartInfo($goods_id,1);
                $order_amount = 0;
                foreach($goodsInfo as $k=> $v){
                    $order_amount += $v['self_price']*$v['buy_number'];
                }
                $orderInfo['order_amount'] = $order_amount;
                $orderInfo['user_id'] = $user_id;
                $orderInfo['pay_way'] = $pay_type;
                $orderInfo['order_text'] = $content;
//                dump($orderInfo);die;
                $res1 = $order_model->save($orderInfo);
                if(!$res1){
                    throw new Exception('订单信息入库失败');
                }

                $order_id = $order_model-> getLastInsID();
                // 订单商品信息 存入订单详情表
                $goodsInfo = collection($goodsInfo)->toArray();
                foreach($goodsInfo as $k=>$v){
                    $res2 = $this->checkGoodsNum($goods_id,0,$v['buy_number'],2);
                    if(!$res2){
                        throw new Exception($v['goods_name'].'库存不足,请调整后在提交');
                    }
                    $goodsInfo[$k]['user_id'] = $user_id;
                    $goodsInfo[$k]['order_id'] = $order_id;
                    $goodsInfo[$k]['create_time'] = time();
                    $goodsInfo[$k]['update_time'] = time();
                }
                // dump($goodsInfo);die;
                $res3 = model('OrderDetail')->allowField(true)->saveAll($goodsInfo);
                if(!$res3){
                    throw new Exception('订单商品信息入库失败');
                }

                // 订单收货地址  存入订单收货地址表
                $addressWhere = [
                    'address_id' => $address_id
                ];
                $addressInfo = model('Address')->where($addressWhere)->find();
                // dump($addressInfo);die;
                $addressInfo = $addressInfo->toArray();
                $addressInfo['create_time'] = time();
                $addressInfo['update_time'] = time();
                $addressInfo['order_id'] = $order_id;
                $res4 = model("OrderAddress")->allowField(true)->save($addressInfo);
                if(!$res4){
                    throw new Exception('收货地址入库失败');
                }

                // 清空当前用户的购物车数据
                $cartWhere = [
                    'goods_id' => ['in',$goods_id],
                    'user_id' => $user_id
                ];
                $res5 = model('Cart')->save(['cart_status'=>2],$cartWhere);
                if(!$res5){
                    throw new Exception('清空购物车数据失败');
                }

                // 减少商品的库存
                foreach($goodsInfo as $k=>$v){
                    $goodsWhere = [
                        'goods_id' => $v['goods_id']
                    ];
                    $goodsNum = [
                        'goods_num' =>$v['goods_num']-$v['buy_number']
                    ];
                    $res6 = model('Goods')->save($goodsNum,$goodsWhere);
                    if(!$res6){
                        throw new Exception('库存修改失败');
                    }
                }
                $order_model->commit();

                session('order_id',$order_id);
                succession('下单成功');
            }catch (\Exception $e){
                $order_model->rollback();
                fail($e->getMessage());
            }

        }else{
            fail("登录后才能操作哦");
        }
    }

    // 订单号
    public function orderNumber(){
        // 用户id+时间+4位随机数
        return session('?info').time().rand(1111,9999);
    }

    // 订单提交成功
    public function submitOrderdSuccess(){
        // 左侧分类导航
        $this->getLeftCateInfo();

        $order_id = session('order_id');
        if(empty($order_id)){
            $this-> error('提交订单操作失败，请重新操作');exit;
        }
        $orderInfo = model('Order')->where(['order_id' =>$order_id])->find();
        if(empty($orderInfo)){
            $this-> error('提交订单操作失败，请重新操作');exit;
        }
        $this-> assign('orderInfo',$orderInfo);
        return view();
    }
}
