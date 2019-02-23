<?php
include __DIR__.'/class/MainSQLpdo.php';
include __DIR__.'/lib/OriginSql.lib.php';
include __DIR__.'/class/cache.php';
class search{
	public $config = array();
	function __construct(){
		$this->config = include __DIR__ . '/config/config.php';
	}

	public function index($address){
		$sql=OriginSql::getInstance();
		//user
		$res_user=$sql->select('user','*',1,array("address='".$address."'"),'',1);
		if (!$res_user) {
			echo_array('user is not true');
			exit;
		}
		//own
		$own_res=$sql->select('ownblock','*',0,array("ownerid=".$res_user['id']),'id DESC',15);
		if (!$own_res) {
			$own_res=[];
		}
		//send
		$send_res=$sql->select('send_','*',0,array("userid=".$res_user['id']),'id DESC',10);
		if (!$send_res) {
			$send_res=[];
		}
		//tdata
		$tdata_res=$sql->select('tdata','*',1,array("userid=".$res_user['id']),'',1);
		if (!$tdata_res) {
			$tdata_res=[];
		}
		//work
		$work_res=$sql->select('work','*',0,array("userid=".$res_user['id']),'',100);
		if (!$work_res) {
			$work_res=[];
		}
		include $this->echo_display('pool_search');
	}



    private function echo_display($name){
        return __DIR__ . "/templets/" . $name . ".html";
    }
}
if (!isset($_GET['address'])) {
    exit;
}
$address=trim($_GET['address']);
$search=new search;
$search->index($address);

function echo_array($a) { echo "<pre>"; print_r($a); echo "</pre>"; }
?>