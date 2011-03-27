<?php

class SimDAL_Persistence_AdapterException extends SimDAL_Exception {
	
	protected $adapter;
	
	public function __construct(SimDAL_Persistence_AdapterAbstract $adapter, $message, $code=null) {
		$this->adapter = $adapter;
		parent::__construct($message, $code);
	}
	
}