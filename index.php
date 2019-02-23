<?php
include __DIR__.'/class/MainSQLpdo.php';
include __DIR__.'/lib/OriginSql.lib.php';
include __DIR__.'/class/cache.php';
class index {
	public $config = array();
	
	function __construct(){
		$this->config = include __DIR__ . '/config/config.php';
	}


	public function index(){
		$d=cache::get('mine_data');
		$tt=cache::get('last_getnodeminingwork_time');

		$res_tdata=[];
		$res_own=[];
		//echo_array($d);
		if ($d and $tt) {
			$sql=OriginSql::getInstance();
			//tdata
			$res_tdata=$sql->select('tdata','*',0,array("height=".$d['height']),'',10);
			foreach ($res_tdata as $key=>$value) {
				$u_res=$sql->select('user','*',1,array("id=".$value['userid']),'',1);
				$res_tdata[$key]['user']=$u_res;
			}
			//own_block
			$res_own=$sql->select('ownblock','*',0,array(),'id DESC',10);
			foreach ($res_own as $key=>$value) {
				$u_res=$sql->select('user','*',1,array("id=".$value['ownerid']),'',1);
				$res_own[$key]['user']=$u_res;
			}


		}



		include $this->echo_display('pool_index');
	}
    private function echo_display($name){
        return __DIR__ . "/templets/" . $name . ".html";
    }
}
		$index=new index();
		$res=$index->index();
		function echo_array($a) { echo "<pre>"; print_r($a); echo "</pre>"; }
?>