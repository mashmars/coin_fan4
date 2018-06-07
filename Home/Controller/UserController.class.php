<?php
namespace Home\Controller;
use Think\Controller;
use Home\Controller\CommonController;
class UserController extends CommonController {
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
    }

    public function index(){
		$userid = session('userid');
		$yeji = $this->get_xiaji($userid);
		$gonggao = M('gonggao')->find(1);
		if($gonggao['content']){
			$this->assign('flag',true);
		}
		$this->assign('gonggao',$gonggao);
		$this->assign('yeji',$yeji);
		$this->display();
	}
	 /**
     * 邀请好友
     */
    public function invite()
    {
        $phone = session('phone');
		$info = M('user')->find(session('userid'));
        $this->assign('phone',$phone);
        $this->assign('info',$info);
        $this->display();
    }
	public function qrcode(){
        vendor('phpqrcode.phpqrcode');
        $url = I('get.url');

        if (strpos($url, 'http')===false) {
            //if no http
            $url='http://'.$url;
        }
        \QRcode::png($url); 
    }
	//获取两条线 和queue一样
	public function get_xiaji($userid)
	{
		$users = M('user_zone')->where(array('pid'=>$userid))->field('zone,userid')->select();
		foreach($users as $user){
			if($user['zone'] == 1){
				//第一个区
				$users_a = $this->get_small_zone($user['userid']);
			}elseif($user['zone'] == 2){
				//第二个区
				$users_b = $this->get_small_zone($user['userid']);
			}
		}
		if($users_a){
			$qu_1_total = M('user_coin')->where(array('userid'=>array('in',$users_a)))->sum('lth');
		}
		if($users_b){
			$qu_2_total = M('user_coin')->where(array('userid'=>array('in',$users_b)))->sum('lth');
		}
		return array('yiqu'=>$qu_1_total,'erqu'=>$qu_2_total);
	}
	//获取小区用户
	public function get_small_zone($userid,$new=true)
	{
		static $users = array();
		if($new){
			$users = array();//必须释放
		}
		array_push($users,$userid);
		$user_xiaji = M('user_zone')->where(array('pid'=>$userid))->getField('userid',true);
		if($user_xiaji){
			foreach($user_xiaji as $user){
				$this->get_small_zone($user,false);
			}				
		}
		return $users;
	}

    /**
     * 个人资料
     */
    public function profile()
    {
        $userid = session('userid') ;
        $info = M('user')->where(array('id'=>$userid))->find();
        $this->assign('info',$info);
        $this->display();
    }
    /**
     * 个人资料修改
     */
    public function ajax_profile()
    {
        $userid = session('userid') ;
        $realname = I('post.realname');
        $idcard = I('post.idcard');
        $country = I('post.country');
        $province = I('post.province');
        $city = I('post.city');

        $info = M('User')->where(array('id'=>$userid))->save(array(
            'realname'=>$realname,'idcard'=>$idcard,
            'country'=>$country,'province'=>$province,'city'=>$city,
        ));
        if($info){
            echo ajax_return(1,L('success'));
        }else{
            echo ajax_return(0,L('error'));
        }
    }

    /**
     * 密码管理
     */
    public function password()
    {
        $this->display();
    }
	
	/**
     *发送修改登录密码短信验证
     */
    public function ajax_password_send_sms()
    {
        $phone = session('phone');
		if(session($phone.'password')){
			echo ajax_return(1,L('sended'));exit;
		}
        $code = mt_rand(10000,99999);
        $result = send_sms('72713',$phone,$code);
        if($result['info'] == 'success'){
            session($phone.'password',$code);
            echo ajax_return(1,L('send'));
        }else{
            echo ajax_return(0,$result['msg']);
        }
    }
    /**
     * 修改登录密码
     */
    public function ajax_password()
    {
        $userid = session('userid');
        
        $newpassword = I('post.newpassword');
        $newpassword2 = I('post.newpassword2');
		$password = I('post.password');
		$phone = session('phone');
        if( $newpassword == '' || $newpassword2 == '' || $newpassword != $newpassword2){
            echo ajax_return(0,L('password_set_error'));exit;
        }
        $password2 = M('user')->where(array('id'=>$userid))->getField('password');
        if($password2 != md5($password)){
			echo ajax_return(0,'老登录密码不正确');exit;
		}
        $info = M('user')->where(array('id'=>$userid))->setField(array('password'=>md5($newpassword)));
        if($info){
			session($phone.'password',null);
            echo ajax_return(1,L('success'));exit;
        }else{
            echo ajax_return(0,L('error'));
        }

    }
	/**
     *发送修改交易密码短信验证
     */
    public function ajax_paypassword_send_sms()
    {
        $phone = session('phone');
		if(session($phone.'paypassword')){
			echo ajax_return(1,L('sended'));exit;
		}
        $code = mt_rand(10000,99999);
        $result = send_sms('72713',$phone,$code);
        if($result['info'] == 'success'){
            session($phone.'paypassword',$code);
            echo ajax_return(1,L('send'));
        }else{
            echo ajax_return(0,$result['msg']);
        }
    }
    /**
     * 修改交易密码
     */
    public function ajax_paypassword()
    {
        $userid = session('userid') ;
       
        $newpassword = I('post.newpassword');
        $newpassword2 = I('post.newpassword2');
        $password = I('post.password');
		$phone = session('phone');
        if($newpassword == '' || $newpassword2 == '' || $newpassword != $newpassword2){
            echo ajax_return(0,L('password_set_error'));exit;
        }
		

        $password2 = M('user')->where(array('id'=>$userid))->getField('paypassword');
		if($password2 != md5($password)){
			echo ajax_return(0,'老交易密码不正确');exit;
		}
        $info = M('user')->where(array('id'=>$userid))->setField(array('paypassword'=>md5($newpassword)));
        if($info){
			session($phone.'paypassword',null);
            echo ajax_return(1,L('success'));exit;
        }else{
            echo ajax_return(0,L('error'));
        }

    }

    /**
     * 手机页面
     */
    public function phone()
    {
        $this->display();
    }

    /**
     *发送更换手机短信验证
     */
    public function ajax_send_sms()
    {
        $phone = I('post.phone');
        //判断手机号是否存在
        $id = M('user')->where(array('phone'=>$phone))->getField('id');
        if($id){
            echo ajax_return(0,L('phone_exist'));exit;
        }
		if(session($phone.'chg')){
			echo ajax_return(1,L('sended'));exit;
		}
        $code = mt_rand(10000,99999);
        $result = send_sms('72713',$phone,$code);
        if($result['info'] == 'success'){
            session($phone.'chg',$code);
            echo ajax_return(1,L('send'));
        }else{
            echo ajax_return(0,$result['msg']);
        }
    }

    /**
     * 更换手机号操作
     */
    public function ajax_change_phone()
    {
        $phone = I('post.phone');
        $sms = I('post.sms');

        $userid = session('userid') ;
        //判断短信吗是否正确
        if($sms != session($phone . 'chg')){
            echo ajax_return(0,L('sms_set_error'));exit;
        }
        //可以更换
        $info = M('user')->where(array('id'=>$userid))->setField(array('phone'=>$phone,'username'=>$phone));
        if($info){
            session('phone',$phone);
			session($phone.'chg',null);
            echo ajax_return(0,L('success'));
        }else{
            echo ajax_return(0,L('error'));
        }
    }

	/**
     * 退出登录
     */
	public function ajax_logout()
    {
        session(null);
        echo ajax_return(1,L('logout'));
    }

    
}