<?php

class SimDAL_ErrorTriggerer {
	
	protected $_errorMessages = array();
	protected $_message = array();

	protected function _error($msg, $key=null) {
		if (!is_null($key)) {
			$this->_errorMessages[$key] = $msg;
		} else {
			$this->_errorMessages[] = $msg;
		}
	}
	
	protected function _message($msg, $key=null) {
		if (!is_null($key)) {
			$this->_messages[$key] = $msg;
		} else {
			$this->_messages[] = $msg;
		}
	}
	
	public function getErrorMessages() {
		return $this->_errorMessages;
	}
	
	public function getMessages() {
		return $this->_messages;
	}
	
	public function getErrorMessage($key) {
		if (!array_key_exists($key, $this->_errorMessages)) {
			return false;
		}
		return $this->_errorMessages[$key];
	}
	
	public function getOrphanErrorMessages() {
		$msgs = array();
		foreach ($this->_errorMessages as $key=>$msg) {
			if (is_numeric($key)) {
				$msgs[] = $msg;
			}
		}
		
		return $msgs;
	}
	
	public function isError() {
		return count($this->_errorMessages) > 0;
	}
	
	public function clearErrors() {
		$this->_errorMessages = array();
	}
	
	public function hasMessages() {
		return count($this->_messages) > 0;
	}
	
}