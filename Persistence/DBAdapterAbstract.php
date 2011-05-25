<?php

abstract class SimDAL_Persistence_DBAdapterAbstract extends SimDAL_Persistence_AdapterAbstract {
	
	public function __construct(SimDAL_Mapper $mapper=null, SimDAL_Session $session=null, $conf) {
		$this->_conn = $connection;
	}
	
	public function __destruct() {
		$this->_disconnect();
	}
	
	abstract protected function _connect();
	abstract protected function _disconnect();
	
}