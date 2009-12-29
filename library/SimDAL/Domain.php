<?php

class Domain extends SimDAL_ErrorTriggerer {
	
	static protected $_instance = null;
	
	protected $_user = null;
	
	protected $_commits = array();
	
	public function __construct($user) {
		$this->_user = $user;
	}
	
	static public function getInstance($user=null, $class='Domain') {
		if (is_null(self::$_instance)) {
			if (is_null($user)) {
				throw new Exception("User is null");
			}
			self::$_instance = new $class($user);
		}
		
		return self::$_instance;
	}

	protected function _setCommit($repo) {
		$this->_commits[] = $repo;
	}
	
	public function commit() {
		$repo = array_shift($this->_commits);
		if (!is_null($repo) && !$repo->commit()) {
			$this->_errorMessages = $repo->getErrorMessages();
			return false;
		}
		
		return true;
	}
	
	public function getError() {
		$msg = array_shift($this->_errorMessages);
		
		while (is_array($msg)) {
			$msg = array_shift($msg);
		}
		
		return $msg;
	}
	
}