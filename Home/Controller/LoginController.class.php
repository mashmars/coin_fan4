<?php
namespace Home\Controller;
use Think\Controller;

class LoginController extends Controller
{

    /**
     * 手机登录
     */
	 

    /**
     * 登录发送验证码
     */
    public function ajax_send_sms_login()
    {
        $phone = I('post.phone');
        //判断手机号是否存在
        $id = M('user')->where(array('phone' => $phone))->getField('id');
        if (!$id) {
            echo ajax_return(0, L('phone_not_exist'));
            exit;
        }
        $code = mt_rand(10000, 99999);
        $result = send_sms('72923', $phone, $code);
        if ($result['info'] == 'success') {
            session($phone . 'login', $code);
            echo ajax_return(1, L('send'));
        } else {
            echo ajax_return(0, $result['msg']);
        }
    }

    /**
     * 手机号验证码登录
     */
    public function ajax_login_phone()
    {
        $phone = I('post.phone');
        $sms = I('post.sms');

        if ($sms != session($phone . 'login')) {
            echo ajax_return(0, L('sms_set_error'));
            exit;
        }
        //判断手机号是否存在
        $id = M('user')->where(array('phone' => $phone))->getField('id');
        if (!$id) {
            echo ajax_return(0, L('phone_not_exist'));
            exit;
        }
        //可以登录
        echo ajax_return(1, L('success'));
        session('userid', $id);
        session('phone', $phone);
        session($phone . 'login', null);
    }

    /**
     * 手机号密码登录
     */
    public function ajax_login_password()
    {
        $phone = I('post.phone');
        $password = I('post.password');

        if ($password == '') {
            echo ajax_return(0, L('password_set_empty'));
            exit;
        }
        //判断手机号是否存在
        $info = M('user')->where(array('phone' => $phone))->find();
        if (!$info) {
            echo ajax_return(0, L('phone_not_exist'));
            exit;
        }
        if ($info['password'] != md5($password)) {
            echo ajax_return(0, L('password_is_wrong'));
            exit;
        }
        //可以登录
        echo ajax_return(1, L('success'));
        session('userid', $info['id']);
        session('phone', $phone);
    }

    /**
     * 找回密码发送短信验证码
     */
    public function ajax_send_sms_find()
    {
        $phone = I('post.phone');
        //判断手机号是否存在
        $id = M('user')->where(array('phone' => $phone))->getField('id');
        if (!$id) {
            echo ajax_return(0, L('phone_not_exist'));
            exit;
        }
        $code = mt_rand(10000, 99999);
        $result = send_sms('72713', $phone, $code);
        if ($result['info'] == 'success') {
            session($phone . 'find', $code);
            echo ajax_return(1, L('send'));
        } else {
            echo ajax_return(0, $result['msg']);
        }
    }

    /**
     * 找回密码提交
     */
    public function ajax_find_password()
    {
        $phone = I('post.phone');
        $sms = I('post.sms');
        $newpassword = I('post.newpassword');
        $newpassword2 = I('post.newpassword2');
        if ($sms != session($phone . 'find')) {
            echo ajax_return(0, L('sms_is_wrong'));
            exit;
        }
        if($newpassword != $newpassword2 || $newpassword == ''){
            echo ajax_return(0,L('newpassword2_set_noequal'));exit;
        }
        //判断手机号是否存在
        $info = M('user')->where(array('phone' => $phone))->find();
        if (!$info) {
            echo ajax_return(0, L('phone_not_exist'));
            exit;
        }
        //可以更改
        $res = M('user')->where(array('id'=>$info['id']))->setField(array('password'=>md5($newpassword)));
        if($res){
            echo ajax_return(1, L('success'));
            session($phone . 'find',null);
        }else{
            echo ajax_return(0, L('error'));
        }
    }

	public function register(){
		$refer = I('param.mobile');
		$zone = I('param.zone',1);
		$this->assign('refer',$refer);
		$this->assign('zone',$zone);
		$this->display();
	}
	//发送注册短信验证
    public function ajax_send_sms()
    {
        $phone = I('post.phone');
        //判断手机号是否存在
        $id = M('user')->where(array('phone'=>$phone))->getField('id');
        if($id){
            echo ajax_return(0,L('phone_exist'));exit;
        }
        $code = mt_rand(10000,99999);
        $result = send_sms('72695',$phone,$code);
        if($result['info'] == 'success'){
            session($phone.'reg',$code);
            echo ajax_return(1,L('send'));
        }else{
            echo ajax_return(0,$result['msg']);
        }
    }
	//注册前获取邀请人信息ajax获取到当前邀请人下面有人，就返回状态，让一区二区显示出来就可以了 //暂时不用
	public function ajax_getrefer()
	{
		$refer = I('post.refer');
		$id = M('user')->where(array('phone'=>$refer))->getField('id');
		if(!$id){
			echo json_encode(array('info'=>'other','msg'=>'邀请人手机号不正确'));exit;
		}else{
			//判断当前用户下面有人没 有的话 返回success 否则返回erroe
			$zone = M('user_zone')->where(array('pid'=>$id))->find();
			if($zone){
				echo ajax_return(1,'没人');
			}else{
				echo ajax_return(0,'有人');
			}
		}
	}
    //注册
    public function ajax_register()
    {
        $phone = I('post.phone');
       
        $realname = I('post.realname');
        $password = I('post.password');
        $paypassword = I('post.paypassword');
        $refer = I('post.refer');
        $zone = I('post.zone',1);

        /**
         * 注册流程，
         * 1，必要条件验证
         * 2，判断短信验证码是否存在
         * 3，判断手机号是否存在 以及上级手机号是否存在
         * 4，注册成功 同时建立资产表
         * 5, 注册成功 分区表（一直按下级的A区分配绑定）
         */
        //1
        if(!$phone){
            echo ajax_return(0,L('phone_set_empty'));exit;
        }
		
       
        if(!$password){
            echo ajax_return(0,L('password_set_empty'));exit;
        }
        if(!$paypassword){
            echo ajax_return(0,L('paypassword_set_empty'));exit;
        }
        if($password == $paypassword){
            echo ajax_return(0,L('not_same'));exit;
        }
        if($zone != 1 && $zone !=2){
            echo ajax_return(0,L('error'));exit;
        }
        
        //3
        $id = M('user')->where(array('phone'=>$phone))->getField('id');
        if($id){
            echo ajax_return(0,L('phone_exist'));exit;
        }
        $id = M('user')->where(array('phone'=>$refer))->getField('id');
		if(!$id){
			$pid = 0;
		}else{
			$pid = $id;
		}
        //4
        $mo = M();
        $mo->startTrans();
        $rs = array();
        //注册成功
        $rs[] = $mo->table('user')->add(array('phone'=>$phone,'username'=>$phone,'password'=>md5($password),'paypassword'=>md5($paypassword),'pid'=>$pid,'realname'=>$realname,'createdate'=>time()));
        //插入资产表
        $rs[] = $mo->table('user_coin')->add(array('userid'=>$rs[0]));
        //开始分区
        $rs[] = $this->user_zone($rs[0],$pid,$pid,$zone);

        if(check_arr($rs)){
            $mo->commit();
            session($phone . 'reg' ,null);
            echo ajax_return(1,L('success'));
        }else{
            $mo->rollback();
            echo ajax_return(0,L('error'));
        }

    }
    /**
     * 注册分区 规则如下
     * 注册的时候选择A区或B区，如果是A区，则放到下级的A区上，
     */
    private  function user_zone($userid,$ownid,$pid,$zone)
    {
        if($pid == 0){
            $res = M('user_zone')->add(array('userid'=>$userid,'ownid'=>0,'pid'=>0,'zone'=>0));

        }else{
			//新增如果推荐人下面没有人 不管提交的zone是哪个区 默认为1区
			$user_zone = M('user_zone')->where(array('pid'=>$pid))->find();
			if(!$user_zone){
				$zone = 1;
			}
            $res = $this->add_zone($userid,$ownid,$zone,$pid,$zone);

        }
        if(!$res){
            return false;
        }
        return true;
    }
    /**
     *判断上级的该区是否有人，如果没有人，满足条件直接分配上  如果有人，则遍历下级的一区是否有人 没人则满足条件分配上
     * （考虑个特殊情况，如果分配的是2区，要先判断同级的1区是否有安排人，没有则直接安排上不用再遍历下级了）
     * 如果同级的1区有人，则遍历属于这个人下级且是2区的下级1区是否有人，没人则分配安排上
     * 加行锁 防止同时两个人挂一个人下面的情况
     * $userid 注册会员id $ownid 推荐人id $init_zone注册选择的分区 $pid 节点人的id $zone 要找的哪个区 遍历用 （新增 只要推荐人下面没人 直接放一区 放上面了）
     */
    private function add_zone($userid,$ownid,$init_zone,$pid,$zone)
    {

        $info = M('user_zone')->where(array('pid'=>$pid,'zone'=>$zone))->lock(true)->find();
        if(!$info){
            $res = M('user_zone')->add(array('userid'=>$userid,'ownid'=>$ownid,'pid'=>$pid,'zone'=>$zone));
            if(!$res){
                return false;
            }
            return true;

        }else{

            if($init_zone == 2){
                $init_zone =1 ; //保证只找最初的一次
                //同级的一区是否有人 特殊情况
                $yiqu = M('user_zone')->where(array('pid'=>$pid,'zone'=>1))->lock(true)->find();
                if(!$yiqu){
                    $res = M('user_zone')->add(array('userid'=>$userid,'ownid'=>$ownid,'pid'=>$pid,'zone'=>1));
                    if(!$res){
                        return false;
                    }
                    return true;
                }
            }
            //遍历
           return $this->add_zone($userid,$ownid,$init_zone,$info['userid'],1);
        }
    }
}