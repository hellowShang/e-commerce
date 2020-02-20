<?php
namespace app\index\controller;

use think\console\Input;

class Login extends Common{

    // 注册
    public function register(){
        if(request()->isPost()&&request()->isAjax()){
            $data = input('post.');
            // dump($data);

            // 验证
            $validate = validate('User');
            $res = $validate-> scene('register')-> check($data);
            if(!$res){
                fail($validate->getError());exit;
            }

            // 入库
            $result = model('User')->allowField(true)->save($data);
            $user_id = model('User')->getLastInsID();
            if($result){
                $info = [
                    'user_id' => $user_id,
                    'user_name' => $data['user_email']
                ];
                session('info',$info);
                succession('注册成功');
            }else{
                fail('注册失败');
            }
        }else{
            // dump(session('info'));die;
            $this->view->engine->layout(false);
            return view();
        }
    }

    // 邮箱验证是否注册
    public function checkEmail(){
        $user_email = input('post.user_email');
        // echo $user_email;
        $where = [
            'user_email' => $user_email
        ];
        $res = model('User')->where($where)->find();
        if(empty($res)){
            echo 'ok';
        }else{
            echo 'no';
        }
    }

    // 邮箱发送
    public function sendEmail(){
        $data = input('post.');
        // dump($data) ;die;

        // 验证
        $validate = validate('User');
        $res = $validate-> scene('email')-> check($data);
        if(!$res){
            fail($validate->getError());exit;
        }

        // 生成随机验证码
        $code = rand(100000,999999);
        // echo $code;

        // 邮箱发送
        $res = sendEmail($data['user_email'],$code);
        if($res){
            session('userInfo',['userTime' => time(),'userEmail' => $data['user_email'],'userCode' => $code]);
            succession('发送成功');
        }else{
            fail('发送失败');
        }
    }

    // 手机号发送
    public function sendTel(){
        dump(sendSms('13473136348','123123'));
    }

    // 登录
    public function login(){
        if(request()->isAjax()&&request()->isPost()){
            $data = input('post.');
            // dump($data);
            $where = [
                'user_email' => $data['account'],
                'user_tel' => $data['account'],
            ];
            // dump($where);
            $arr = model('User')->whereOr($where)->find();
            // dump($arr->toArray());die;
            // 错误次数
            $error_num = $arr['error_num'];

            // 最后一次错误时间
            $last_error_time = $arr['last_error_time'];

            // 当前时间
            $now = time();

            // 条件
            $updateWhere = [
                'user_id' => $arr['user_id']
            ];

            // 账号密码判断
            if($arr){
                if($arr['user_pwd'] == md5($data['user_pwd'])){
                    // 判断账号是否在锁定中
                    if($error_num >= 3&&$now-$last_error_time>3600){
                        $lastTime = 60-(ceil(($now-$last_error_time)/60));
                        fail('账号锁定中，请您于'.$lastTime.'分钟后登录！');
                    }else{
                        // 密码正确  错误次数清零 错误时间为空
                        $updateValue = [
                            'error_num' => 0,
                            'last_error_time' => null
                        ];
                        $res = model('User')-> where($updateWhere)->update($updateValue);

                        // 同步浏览记录
                        $this-> syncHistory($arr['user_id']);


                        // 同步购物车记录
                        $this ->syncCart($arr['user_id']);

                        // 判断账号密码是否记录10天
                        if($data['remember_me'] == 'true'){
                        //存cookie
                            $sec = 60*60*24*10;
                            cookie('account',$data['account'],$sec);
                            cookie('user_pwd',$data['user_pwd'],$sec);
                        }

                        // 存sessio
                        $info = [
                            'user_id' => $arr['user_id'],
                            'user_name' => $data['account']
                        ];
                        session('info',$info);

                        // 提示登录成功
                        succession('登录成功');
                    }
                }else{
                    // 密码错误
                    if($now-$last_error_time>3600){
                        // 锁定时间过后密码出错 错误次数为1 错误时间为当前时间 并提示可操作次数
                        $updateValue = [
                            'error_num' => 1,
                            'last_error_time' => $now
                        ];
                        $res = model('User')-> where($updateWhere)->update($updateValue);
                        // dump($res);
                        fail('账号或密码错误，你还有2次机会登录');
                    }else{
                        // 密码错误3次时，提示账号锁定与多长时间后可再登录
                        if($error_num >= 3){
                            $lastTime = 60-(ceil(($now-$last_error_time)/60));
                            fail('账号已锁定，请您于'.$lastTime.'分钟后登录！');
                        }else{
                            // 密码错误，次数+1 ，最后一次错误时间入库
                            $updateValue = [
                                'error_num' => $error_num+1,
                                'last_error_time' => $now
                            ];
                            $res = model('User')-> where($updateWhere)->update($updateValue);
                            // dump($res);
                            // 剩余可操作次数
                            $num = 3-($error_num+1);
                            if($num > 0){
                                fail('账号或密码错误，你还有'.$num.'次机会登录');
                            }else{
                                fail('账号已锁定，请您于一小时后登录！');
                            }
                        }
                    }
                }
            }else{
                fail('账号错误');
            }
        }else{
            $this->view->engine->layout(false);
            return view();
        }
    }

    // 退出
    public function quit(){
       $session = session('?info');
        if(empty($session)){
            fail('登录后才能退出哦');
        }else{
            session('info',null);
            succession('退出成功');
        }

    }

    // 忘记密码
    public function forgetPwd(){
        return view();
    }

    // 查看邮箱信息
    public function findEmail(){
        $email = input('post.email');
        $userInfo = model('User')->where(['user_email' =>$email])->find();
        $string =md5($userInfo['user_id'].'+'.$userInfo['user_email'].'+'.$userInfo['user_pwd']);
        $userInfo = base64_encode($userInfo['user_id'].".".$string);

        // 发送邮件
        $res = $this-> sendEmailFind($email,$userInfo);
        if($res){
            succession('发送成功，请查看邮箱');
        }else{
            fail('发送失败，请重新操作');
        }
    }

    // 密码找回
    public function sendEmailFind($email,$code){
        //实例化PHPMailer核心类
        $mail = new \email\PHPMailer();


        //是否启用smtp的debug进行调试 开发环境建议开启 生产环境注释掉即可 默认关闭debug调试模式
        $mail->SMTPDebug =1;

        //使用smtp鉴权方式发送邮件
        $mail->isSMTP();

        //smtp需要鉴权 这个必须是true
        $mail->SMTPAuth=true;

        //链接qq域名邮箱的服务器地址
        $mail->Host = 'smtp.163.com';//163邮箱：smtp.163.com

        //设置使用ssl加密方式登录鉴权
        $mail->SMTPSecure = 'ssl';//163邮箱就注释

        //设置ssl连接smtp服务器的远程服务器端口号，以前的默认是25，但是现在新的好像已经不可用了 可选465或587
        $mail->Port = 465;//163邮箱：25

        //设置smtp的helo消息头 这个可有可无 内容任意
        // $mail->Helo = 'Hello smtp.qq.com Server';

        //设置发件人的主机域 可有可无 默认为localhost 内容任意，建议使用你的域名
        //$mail->Hostname = 'http://localhost/';

        //设置发送的邮件的编码 可选GB2312 我喜欢utf-8 据说utf8在某些客户端收信下会乱码
        $mail->CharSet = 'UTF-8';

        //设置发件人姓名（昵称） 任意内容，显示在收件人邮件的发件人邮箱地址前的发件人姓名
        $mail->FromName = 'shang';

        //smtp登录的账号 这里填入字符串格式的qq号即可
        $mail->Username ='13473136348@163.com';

        //smtp登录的密码 使用生成的授权码（就刚才叫你保存的最新的授权码）
        $mail->Password = 'lab061012';//163邮箱也有授权码 进入163邮箱帐号获取

        //设置发件人邮箱地址 这里填入上述提到的“发件人邮箱”
        $mail->From = '13473136348@163.com';

        //邮件正文是否为html编码 注意此处是一个方法 不再是属性 true或false
        $mail->isHTML(true);

        //设置收件人邮箱地址 该方法有两个参数 第一个参数为收件人邮箱地址 第二参数为给该地址设置的昵称 不同的邮箱系统会自动进行处理变动 这里第二个参数的意义不大
        $mail->addAddress($email);

        //添加多个收件人 则多次调用方法即可
        // $mail->addAddress('xxx@163.com','爱代码，爱生活世界');

        //添加该邮件的主题
        $mail->Subject = '密码找回信息';

        //添加邮件正文 上方将isHTML设置成了true，则可以是完整的html字符串 如：使用file_get_contents函数读取本地的html文件
        $mail->Body = "<a>确认密码找回</a>，或者复制此链接到浏览器http://localhost/month5/shop/public/index.php/login/resetUserPwd.html?p=".$code;

        //为该邮件添加附件 该方法也有两个参数 第一个参数为附件存放的目录（相对目录、或绝对目录均可） 第二参数为在邮件附件中该附件的名称
        // $mail->addAttachment('./d.jpg','mm.jpg');
        //同样该方法可以多次调用 上传多个附件
        // $mail->addAttachment('./Jlib-1.1.0.js','Jlib.js');

        $status = $mail->send();

        //简单的判断与提示信息
        if($status) {
            return true;
        }else{
            return false;
        }
    }

    // 密码重置
    public function resetUserPwd(){
        if(request()->isPost()&&request()->isAjax()){
            $user_pwd = input('post.pwd');
            $user_id = input('post.user_id');
            if(empty($user_id)){
                $this->error('操作错误',url("Login/forgetPwd"));exit;
            }
            if(empty($user_pwd)){
                $this->error('操作错误',url("Login/forgetPwd"));exit;
            }
            $res = model('User')->save(['user_pwd' => $user_pwd],['user_id' => $user_id]);
            if($res){
                succession('修改成功');
            }else{
                fail('修改失败');
            }
        }else{
            $p = input('get.p');
            if(empty($p)){
                $this->error('操作错误',url("Login/forgetPwd"));exit;
            }
            $str = base64_decode($p);
            $arr = explode('.',$str);
            $where = [
                'user_id' => $arr[0]
            ];
            $userInfo = model('User')->where($where)->find();
            $user_pwd = md5($userInfo['user_id'].'+'.$userInfo['user_email'].'+'.$userInfo['user_pwd']);
            if($arr[1] === $user_pwd){
                $this->assign('userInfo',$userInfo);
                return view();
            }else{
                $this->error('操作错误');
            }
        }
    }
}
?>