<?php
namespace Home\Controller;
use Think\Controller;

class IndexController extends Controller {
	public function index(){

	    $userid = session('userid');
	    if($userid){
	        redirect(U('user/index'));
        }else{
	        redirect(U('login/password'));
        }
		exit;

		Vendor("Move.ext.client");

		$client = new \client('12','123...', '127.0.0.1', 29316, 5, [], 1);
		if (!$client) {
			var_dump('aaa');
		}else{
			var_dump('a');
			echo '<pre>';
			//var_dump($client);
			//$res = $client->execute("listtransactions", ["*", 20, 0]);
			//$res = $client->execute("getinfo");
			//$res = $client->getnewaddress();//生成新地址			
			//var_dump($res);
			$res = $client->getaddressesbyaccount('15890143123');//获取新地址
			if(!$res){
				$qianbao_ad = $client->getnewaddress('15890143123');
				var_dump($qianbao_ad);
			}
				var_dump($res);
		}
		var_dump('dd');exit;
		$this->display();
	}
	
	
}