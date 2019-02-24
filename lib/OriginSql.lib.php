<?php
// version: 20190128 test
class OriginSql{
	private static $_instance = null;
	private static $_connect = null;
	private static $initt=array(
								'balance'=>array(
												'id' => 'num',
												'userid' => 'num',
												'balance' => 'num',
												'height' => 'num',
												),
								'config'=>array(
												'keyy' => 'string',
												'val' => 'string',
												),
								'ownblock'=>array(
												'id' => 'num',
												'height' => 'num',
												'ownerid' => 'num',
												'reward' => 'num',
												'already'=> 'num',
												),
								'send_'=>array(
												'id' => 'num',
												'userid' => 'num',
												'amount' => 'num',
												'timee' => 'num',
												),
								'tdata'=>array(
												'id' => 'num',
												'height' => 'num',
												'userid' => 'num',
												'rate' => 'num',
												'dl' => 'num',
												'argon' => 'string',
												'nonce' => 'string',
												),
								'user'=>array(
												'id' => 'num',
												'address' => 'string',
												'balance' => 'num',
												),
								'work'=>array(
												'id' => 'num',
												'height' => 'num',
												'userid' => 'num',
												'work' => 'string',
												),
								 );
	private static $tb = array(
			'balance' =>'balance',
			'config'=>'config',
			'ownblock'=>'ownblock',
			'send'=>'send_',
			'tdata'=>'tdata',
			'user'=>'user',
			'work'=>'work'
			 );
	function __construct(){
		# code...
	}
    public static function getInstance(){
    	if (self::$_connect === null) {
    		$config=include(__DIR__.'../../config/config_db.php');
	        $db_config = array(
	            'host'=>$config['db_host'],
	            'dbname'=>$config['db_dbname'],
	            'user'=>$config['db_user'],
	            'pwd'=>$config['db_pass']
	        );
	        $db = MainSQLpdo::getInstance();
	        $db->connect($db_config);
	        if (!$db) {   die("Could not connect to the DB backend.");    }else{	self::$_connect=$db;	}
    	}
        if(self::$_instance === null){
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function close(){
    	$db=MainSQLpdo::getInstance();
    	$db->close();
    	self::$_connect=null;
    	self::$_instance=null;
    }
	public function exec($sql){
		$db=MainSQLpdo::getInstance();
		return $db->exec($sql);
	}
    public function beginTransaction(){
    	$db=MainSQLpdo::getInstance();
        $db->beginTransaction();
    }

    public function rollback(){
    	$db=MainSQLpdo::getInstance();
        $db->rollback();
    }
    public function commit(){
    	$db=MainSQLpdo::getInstance();
        $db->commit();
    }
    public function lock_tables($tables='blocks,accounts,transactions,mempool,masternode,peers',$model='WRITE'){
    	if ($tables==='') {
    		$tables='blocks,accounts,transactions,mempool,masternode,peers';
    	}
    	if ($model==='') {
    		$model='WRITE';
    	}
    	$tables_str = explode(',', $tables);
    	$set='';
    	foreach ($tables_str as $value) {
    		$set=$set.','.$value.' '.$model;
    	}
		if (substr($set,0,1)==',') {
			$set=substr($set,1);
		}

		$db=MainSQLpdo::getInstance();
		$this->exec('LOCK TABLES '.$set);
    }
    public function unlock_tables(){
    	$db=MainSQLpdo::getInstance();
    	$this->exec('UNLOCK TABLES');
    }
	private function rename_tables($table){
		if ($table==='' or $table===NULL) {
			return 'XXX';
		}
		if (isset(self::$tb[$table])) {
			return self::$tb[$table];
		}else{
			return $table;
		}
	}

/*
	$data = array(
		'field1' => 'value1',
		'field2' => 'value2',
		'field3' => 'value3',
	 );

*/

	public function add($table,$data=array()){

		$table=$this->rename_tables($table);

		if (!isset(self::$initt[$table])) {
			return false;
		}
		if (!is_array($data)) {
			return false;
		}
		$set='';
		foreach ($data as $key => $value) {
			if (!isset(self::$initt[$table][$key])) {
				return false;
			}
			if (self::$initt[$table][$key]=='num') {
				$set=$set.',`'.$key.'`='.$value;
			}elseif(self::$initt[$table][$key]=='string'){
				if ($value=='NULL' or $value==NULL) {
					$set=$set.',`'.$key.'`=NULL';
				}else{
					$set=$set.',`'.$key.'`='."'".$value."'";
				}
			}elseif(self::$initt[$table][$key]=='deci'){
				$value=number_format($value, 8, ".", "");
				$set=$set.',`'.$key.'`='."'".$value."'";
			}
			
		}
		if (substr($set,0,1)==',') {
			$set=substr($set,1);
		}

		$db=MainSQLpdo::getInstance();
		$canshu = array(
		    'debug' => 0,  //0=不开启 1=开启 2=开启并终止程序
		    'mode' => 1, //0=无返回信息 1=返回执行条目数 2=返回最后一次插入记录的id
		    'table' => $table,
		    'set' => $set,
		 );
		return $db->insert($canshu);
		// if ($res===false) {
		// 	$canshu = array(
		// 	    'debug' => 2,  //0=不开启 1=开启 2=开启并终止程序
		// 	    'mode' => 1, //0=无返回信息 1=返回执行条目数 2=返回最后一次插入记录的id
		// 	    'table' => $table,
		// 	    'set' => $set,
		// 	 );
		// 	$db->insert($canshu);
		// 	return false;
		// }
		// return $res;
	}

	// 'mode' => 1, //0=多条 1=单条 2=返回行数
	// 'fields' => '*',
	// 'limit' => 1,  // mode=0时有效 0=返回所有 其余数字按照规格
	public function select($table,$fields='*',$mode=1,$sqlwhere=array(),$orderby='',$limit=1){
		if ($fields==='') {
			$fields='*';
		}
		if ($mode==='') {
			$mode=1;
		}
		if ($limit==='') {
			$limit=1;
		}
		$table=$this->rename_tables($table);
		if (!isset(self::$initt[$table])) {
			return false;
		}

		$db=MainSQLpdo::getInstance();
		$canshu = array(
		    'debug' => 0,  //0=不开启 1=开启 2=开启并终止程序
		    'mode' => $mode, //0=多条 1=单条 2=返回行数
		    'table' => $table,
		    'fields' => $fields,
		    'sqlwhere' => $sqlwhere,
		    'orderby' =>$orderby,
		    'limit' => $limit,  // mode=0时有效 0=返回所有 其余数字按照规格

		 );
		return $db->select($canshu);
	}
/*
	$data = array(
		'field1' => 'value1',
		'field2' => 'value2',
		'field3' => 'value3',
	 );

*/
	public function update($table,$data=array(),$sqlwhere=array()){
		$table=$this->rename_tables($table);
		if (!isset(self::$initt[$table])) {
			return false;
		}
		if (!is_array($data)) {
			return false;
		}
		$set='';
		foreach ($data as $key => $value) {
			if (!isset(self::$initt[$table][$key])) {
				return false;
			}
			if (self::$initt[$table][$key]=='num') {
				$set=$set.',`'.$key.'`='.$value;
			}elseif(self::$initt[$table][$key]=='string'){
				if ($value=='NULL' or $value==NULL) {
					$set=$set.',`'.$key.'`=NULL';
				}else{
					$set=$set.',`'.$key.'`='."'".$value."'";
				}
				
			}elseif(self::$initt[$table][$key]=='deci'){
				$value=number_format($value, 8, ".", "");
				$set=$set.',`'.$key.'`='."'".$value."'";
			}
			
		}
		if (substr($set,0,1)==',') {
			$set=substr($set,1);
		}


		$db=MainSQLpdo::getInstance();
		$canshu = array(
		    'debug' => 0,  //0=不开启 1=开启 2=开启并终止程序
		    'mode' => 1, //0=无返回信息 1=返回执行条目数
		    'table' => $table,
		    'set' => $set,
		    'sqlwhere' => $sqlwhere,

		 );
		return $db->update($canshu);
	}
	public function delete($table,$sqlwhere=array()){
		$table=$this->rename_tables($table);
		if (!isset(self::$initt[$table])) {
			return false;
		}


		$db=MainSQLpdo::getInstance();
		$canshu = array(
		    'debug' => 0,  //0=不开启 1=开启 2=开启并终止程序
		    'mode' => 1, //0=无返回信息 1=返回执行条目数
		    'table' => $table,
		    'sqlwhere' => $sqlwhere,

		 );

		return $db->delete($canshu);
	}

	public function sum($table,$fileds,$sqlwhere){
		$table=$this->rename_tables($table);
		if (!isset(self::$initt[$table])) {
			return false;
		}

		if (is_array($fileds)) {
			$fileds=implode('+', $fileds);
		}

        if(is_array($sqlwhere)){
            $sqlwhere = ' and '.implode(' and ', $sqlwhere);
        }else{
        	$sqlwhere = ' and '.$sqlwhere;
        }

		$sql='SELECT SUM('.$fileds.') as keyy FROM '.$table.' WHERE 1=1 '.$sqlwhere;

		$db=MainSQLpdo::getInstance();
		if ($db->query($sql)==true) {
			$row=$db->fetch();
			if ($row['keyy']==NULL or $row['keyy']=='') {
				return 0;
			}else{
				return $row['keyy'];
			}	
		}else{
			return false;
		}
		
	}

}


?>