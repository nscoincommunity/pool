<?php
include __DIR__.'/class/MainSQLpdo.php';
include __DIR__.'/lib/OriginSql.lib.php';
include __DIR__.'/class/cache.php';
class loop{
	public $config = array();
	function __construct(){
		$this->config = include __DIR__ . '/config/config.php';
	}
	private function check_lock(){
		$res=cache::get('loop_lock');
		if ($res=='lock') {
			return false;
		}else{
			return true;
		}

	}
	private function set_new_lock(){
		cache::set('loop_lock','lock',900);
	}

	private function un_lock(){
		cache::set('loop_lock','unlock',900);
	}
    private function peer_post($url, $data = [], $timeout = 60){
        if ($timeout==='') {
            $timeout=60;
        }
        $postdata = http_build_query(
            [
                'data' => json_encode($data),
                "coin" => 'origin',
            ]
        );

        $opts = [
            'http' =>
                [
                    'timeout' => $timeout,
                    'method'  => 'POST',
                    'header'  => 'Content-type: application/x-www-form-urlencoded',
                    'content' => $postdata,
                ],
        ];

        $context = stream_context_create($opts);
        $result = file_get_contents($url, false, $context);
        if ($result==false) {
            return false;
        }
        $res = json_decode($result, true);

        // the function will return false if something goes wrong
        if ($res) {
            return $res;
        }else{
            return false;
        }  
    }

    private function submit($nonce,$argon){
        $postData = http_build_query(
	            [
	                'argon'       => $argon,
	                'nonce'       => $nonce,
	                'private_key' => $this->config['private_key'],
	                'public_key'  => $this->config['public_key'],
	            ]
	     );

        $opts = [
            'http' =>
                [
                    'method'  => 'POST',
                    'header'  => 'Content-type: application/x-www-form-urlencoded',
                    'content' => $postData,
                ],
        ];
        $context = stream_context_create($opts);
        $res = file_get_contents($this->config['node']."/Uinterface.php?m=submitNonce", false, $context);
        
        if ($res==false) {
            return false;
        }
        $data = json_decode($res, true);

        if ($data['result'] == 'ok') {
            return true;
        } else {
            return false;
        }
    }
	public function index($userid='',$argon='',$nonce='',$result=51){
		if ($this->check_lock()==false) {
			exit;
		}
		//
		$this->set_new_lock();

		//
		$d=cache::get('mine_data');
		$sql=OriginSql::getInstance();
		$tdataa=$sql->select('tdata','nonce,argon,userid',0,array("height=".$d['height'],'dl<=50'),'dl ASC',0);

		if ($userid!='' and $argon!='' and $nonce!='' and ($result-50)<=0) {
			$tdataa[]=[
				'userid'=>$userid,
				'argon'=>$argon,
				'nonce'=>$nonce
			];
		}
		//submit
		foreach ($tdataa as $key => $value) {

			$res=$this->submit($value['nonce'],$value['argon']);
			if ($res===true) {
				$sql->add('ownblock',array(
					'height'=>$d['height'],
					'ownerid'=>$value['userid'],
					'reward'=>$d['reward'],
					'already'=>0
				));
				break;
			}
		}
		//add user balance
		$ress=$sql->select('ownblock','*',0,array("height<=".($d['height']-10),"height>=".($d['height']-1000),'already=0'),'',0);
		foreach ($ress as $value) {
			$re=$this->peer_post($this->config['node'].'/Uinterface.php?m=getblockhash', ['height'=>$value['height']], 5);
			if (isset($re['result']) and $re['error']=='') {
				$hash=$re['result'];
				$re=$this->peer_post($this->config['node'].'/Uinterface.php?m=getblock', ['blockhash'=>$hash], 5);
				if (isset($re['result']) and $re['error']=='') {
					if ($re['result']['generator']==$this->config['address']) {
						// $sql->delete('ownblock',array("id=".$value['id']));
						$sql->update('ownblock',array('already'=>1),array("id=".$value['id']));

						$res_u=$sql->select('tdata','*',0,array("height=".$d['height']),'',0);
						if ($res_u) {
							foreach ($res_u as $value_u) {
								
								$your_reward=$value['reward']*$value_u['rate'];
								$your_reward=sprintf("%.2f",$your_reward);
								$uuu=$sql->select('user','*',1,array("id=".$value_u['userid']),'',1);
								$sql->update('user',array('balance'=>($uuu['balance']+$your_reward)),array("id=".$value_u['userid']));
								
							}
						}



					}
				}
			}
		}
		//
		//send coin to user
		$ress=$sql->select('user','*',0,array("balance>=".$this->config['min_pay']),'',0);
		foreach ($ress as $value) {
			$res=$sql->update('user',array('balance'=>0),array("id='".$value['id']."'"));
			$res_r=$this->peer_post($this->config['node'].'/Uinterface.php?m=sendtoaddressbyprivatekey',array('fromaddress' =>$this->config['address'],'toaddress'=>$value['address'],'privatekey'=>$this->config['private_key'],'amount'=>$value['balance']),5)
			if (isset($res_r['result']) and $res_r['result']=='ok') {
				$sql->add('send_',array('userid'=>$value['id'],'amount'=>$value['balance'],'timee'=>time()));
			}
		}else{
			cache::set('error',$res_r['error'],0);
		}
		//
		sleep(1);
		$this->un_lock();

	}



}
if (php_sapi_name() != 'cli') {
		exit;
}
$loop=new loop();
if (isset($argv[1]) and isset($argv[2]) and isset($argv[3]) and isset($argv[4])) {
	$loop->index($argv[1],$argv[2],$argv[3],$argv[4]);
}else{
	$loop->index();
}

function echo_array($a) { echo "<pre>"; print_r($a); echo "</pre>"; }
?>