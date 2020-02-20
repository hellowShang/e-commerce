<?php
namespace app\admin\controller;

class Article extends Common{
    // 标题唯一性验证
    public function checkName(){
        $title = input('post.article_title');
        // echo $title;
        $where = [
            'article_title' => $title
        ];
        $res = model('Article')->where($where)->find();
        // dump($res);
        if(empty($res)){
            echo 'ok';
        }else{
            echo 'no';
        }
    }
    // 文章添加
    public function articleAdd(){
        if(request()->isAjax()&&request()->isPost()){
            $data = input('post.');
            $res = model('Article')-> save($data);
            if($res){
                succession('添加成功');
            }else{
                fail('添加失败');
            }
        }else{
            return  view();
        }
    }

    // 文章展示
    public function articleList(){
        return  view();
    }

    // 文章信息
    public function articleInfo(){
        $p = input('get.page',1);
        $page_num = input('get.limit',3);
        $title = input('get.article_title','');
        $where = [
            'article_title|article_desc' => ['like',"%$title%"]
        ];
        $articleInfo = model('Article')->where($where)-> page($p,$page_num)->select();
        $count = model('Article')->where($where)-> count();
        // dump($articleInfo);
        $info = [
            'code' => 0,
            'msg' => '',
            'count' => $count,
            'data' => $articleInfo
        ];
        echo json_encode($info);
    }
}

?>