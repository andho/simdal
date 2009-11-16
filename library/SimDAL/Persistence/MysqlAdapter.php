<?php

class SimDAL_Persistence_MysqlAdapter implements SimDAL_Persistence_AdapterInterface {
	
	private $_host;
	private $_username;
	private $_password;
	private $_database;
	private $_conn;
	
	public function __construct($host, $username, $password, $database) {
		$this->_host = $host;
		$this->_username = $username;
		$this->_password = $password;
		$this->_database = $database;
	}
	
	protected function _connect() {
		if (!is_null($this->_conn)) {
			return true;
		}
		
		$this->_conn = mysql_pconnect($this->_host, $this->_username, $this->_password);
		mysql_select_db($this->_database);
	}
	
	public function insert($table, $data) {
		$this->_connect();
		
		/*$multiple = false;
		
		if (isset($data[0]) AND is_array($data[0])) {
			$multiple = true;
		}
		
		if (!$multiple) {*/
			$sql = "INSERT INTO `$table` (`" . implode("`,`", array_keys($data)) . "`) VALUES ('" . implode("','", $data) . "')";
		/*} else {
			$sql = "INSERT INTO `$table` (`" . implode("`,`", array_keys($data[0])) . "`) VALUES ";
			
			foreach ($data as $row) {
				$sql .= "('" . implode("','", $row) . "'),";
			}
			$sql = substr($sql, 0, -1);
		}*/
			
		mysql_query($sql, $this->_conn);
		
		return mysql_insert_id($this->_conn);
	}
	
	public function update($table, $data, $id) {
		$this->_connect();
		
		$sql = "UPDATE `$table` SET ";
		foreach ($data as $key=>$value) {
			$sql .= "`$key`='$value',";
		}
		$sql = substr($sql, 0, -1) . " WHERE `id`=$id";
		
		mysql_query($sql, $this->_conn);
		
		return mysql_affected_rows($this->_conn);
	}
	
	public function delete($table, $id) {
		$this->_connect();
		
		$sql = "DELETE FROM `$table` WHERE `id`=$id";
		
		mysql_query($sql, $this->_conn);
		
		return mysql_affected_rows($this->_conn);
	}
	
	public function findById($table, $id) {
		$this->_connect();
		
		$sql = "SELECT * FROM `$table` WHERE `id` = $id";
		$query = mysql_query($sql, $this->_conn);
		if (mysql_num_rows($query) <= 0) {
			return null;
		}
		$row = mysql_fetch_assoc($query);
		
		return $row;
	}
	
	public function query($sql) {
		
	}
	
}