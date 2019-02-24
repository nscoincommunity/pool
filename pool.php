<?php
include __DIR__.'/class/MainSQLpdo.php';
include __DIR__.'/lib/OriginSql.lib.php';
include __DIR__.'/class/cache.php';
class pooll {
	public $config = array();
	function __construct(){
		$this->config = include __DIR__ . '/config/config.php';
	}
	public function submitnode(){


	}

	public function getnodeminingwork(){
		$res = file_get_contents($this->config['node']."/Uinterface.php?m=getminingwork");
		$info = json_decode($res, true);
        if (!isset($info['result']) or $info['error']!='') {
        	return false;
        }
        $info = $info['result'];
        $info['limit']=$this->config['limit'];
        $info['public_key']=$this->config['public_key'];
        cache::set('mine_data',$info,0);
        cache::set('last_getnodeminingwork_time',time(),0);
        return true;
	}
	public function getminingwork(){
		$tt=cache::get('last_getnodeminingwork_time');
		if (!$tt or (time()-$tt>2)) {
			$this->getnodeminingwork();
		}

		//get mine data
		$d=cache::get('mine_data');
		if ($d) {
			return $d;
		}else{
			return false;
		}
	}

	public function submitNonce($argon,$nonce,$address,$yourwork){
		$argon1=$argon;	$nonce1=$nonce;
		//get resust
		$d=cache::get('mine_data');
		if (!$d) {
			return false;
		}
		$argon = '$argon2i$v=19$m=16384,t=4,p=4'.$argon;
		$base = $this->config['public_key']."-".$nonce."-".$d['block']."-".$d['difficulty'];
        if (!password_verify($base, $argon)) {
            return false;
        }
        $hash = $base.$argon;
        $hash = hash("sha512", $hash, true);
        $hash = hash("sha512", $hash);
        $m = str_split($hash, 2);
        $duration = hexdec($m[10]).hexdec($m[15]).hexdec($m[20]).hexdec($m[23]).hexdec($m[31]).hexdec($m[40]).hexdec($m[45]).hexdec($m[55]);
        $duration = ltrim($duration, '0');
        $result = gmp_div_q($duration, $d['difficulty']);


        if ($result >$this->config['limit']) {
            return false;
        }

        //user
		$sql=OriginSql::getInstance();
		$userr=$sql->select('user','*',1,array("address='".$address."'"),'',1);
		if (!$userr) {
			$sql->add('user',array('address'=>$address,'balance'=>0));
			$userr=$sql->select('user','*',1,array("address='".$address."'"),'',1);
		}
		if (!$userr) {
			return false;
		}

		//work
		$workk=$sql->select('work','*',1,array("userid=".$userr['id'],"work='".$yourwork."'"),'',1);
		if (!$workk) {
			$sql->add('work',array('userid'=>$userr['id'],'work'=>$yourwork,'height'=>$d['height']));
		}else{
			$sql->update('work',array('height'=>$d['height']),array("userid=".$userr['id'],"work='".$yourwork."'"));
		}

		//tdata
		$tdataa=$sql->select('tdata','*',1,array("userid=".$userr['id']),'',1);
		if (!$tdataa) {
			$sql->add(
				'tdata',array(
				'userid'=>$userr['id'],
				'rate'=>0,
				'height'=>$d['height'],
				'dl'=>$result,
				'argon'=>$argon1,
				'nonce'=>$nonce1
			));
		}else{
			if ($tdataa['dl']>$result or $tdataa['height']!=$d['height']) {
				$sql->update('tdata',array(
					'dl'=>$result,
					'height'=>$d['height'],
					'argon'=>$argon1,
					'nonce'=>$nonce1
				),array("userid=".$userr['id']));
			}
		}

		//rate
		$summ=$sql->sum('tdata','dl',array("height>=".($d['height']-1)));
		$res=$sql->select('tdata','*',0,array("height=".$d['height']),'',0);
		if ($summ and $res) {
			foreach ($res as $value) {
				$your_rate=($summ-$value['dl'])/$summ;
				if ($your_rate==0) {
					$your_rate=1;
				}
				$your_rate=sprintf("%.2f",$your_rate);
				if ($your_rate!=$value['rate']) {
					$sql->update('tdata',array('rate'=>$your_rate),array("id=".$value['id']));
				}
			}
		}

		//submit
		system($this->config['php_path'].'php loop.php '.escapeshellarg($userr['id']).' '.escapeshellarg($argon1).' '.escapeshellarg($nonce1).' '.$result.' > /dev/null 2>&1  &');
		

		return true;

	}



	





}
// $sql=OriginSql::getInstance();

// echo_array($sql->select('ownblock','*',0,array(),'',0));
// exit;

if (!isset($_GET['m'])) {
	echo return_json('','method is not found');
	exit;
}
$method = $_GET['m'];

switch ($method) {
	case 'getminingwork':
		$pooll=new pooll();
		$res=$pooll->getminingwork();
		if ($res) {
			echo return_json($res,'');
		}else{
			echo return_json('','Do not get mine data!!!');
		}
		break;
	case 'submitNonce':

	 // yP3M42Y4jrs8hksYBA7bwx1fwJia48fCvFevptcd0g / $TGlJZEhWNGduRndxV0dqRA$fmpusvpsDvFV3CxjF6PYXof0YTwtm9dJshNjLiwIEFw

		// $noce='yP3M42Y4jrs8hksYBA7bwx1fwJia48fCvFevptcd0g';
		// $argon='$TGlJZEhWNGduRndxV0dqRA$fmpusvpsDvFV3CxjF6PYXof0YTwtm9dJshNjLiwIEFw';
		// $address='4ZkYd18RvepLB566ZB2drf9fQVrJCsABetBMGmKAsGtzJCQhmPoHf6BpxpTFcNszcUVSafTjTHnGBxjkSbUUWMKC';
		// $yourwork='haha1';

		// $pooll=new pooll();
		// $res=$pooll->submitNonce($argon,$noce,$address,$yourwork);
		// 	if ($res==true) {
		// 		echo return_json('confirmed','');
		// 	}else{
		// 		echo return_json('','RJ');
		// 	}
		if (isset($_POST['argon']) and isset($_POST['nonce']) and isset($_POST['address']) and isset($_POST['yourwork'])) {
			$pooll=new pooll();
			$res=$pooll->submitNonce($_POST['argon'],$_POST['nonce'] ,$_POST['address'],$_POST['yourwork']);
			if ($res) {
				echo return_json('ok','');
			}else{
				echo return_json('','RJ');
			}
		}else{
			echo return_json('','error!!!');
		}
		break;
	case 'index':
		# code...
		break;

	default:
		# code...
		break;
}




function return_json($result,$error){
	return json_encode(array('result' => $result,'error'=>$error));
}
function echo_array($a) { echo "<pre>"; print_r($a); echo "</pre>"; }
?>