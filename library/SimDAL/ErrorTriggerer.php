<?php

class SimDAL_ErrorTriggerer {
	
	protected $_errorMessages = array();

	protected function _error($msg, $key=null) {
		if (!is_null($key)) {
			$this->_errorMessages[$key] = $msg;
		} else {
			$this->_errorMessages[] = $msg;
		}
	}
	
	public function getErrorMessages() {
		return $this->_errorMessages;
	}
	
	public function getErrorMessage($key) {
		if (!array_key_exists($key, $this->_errorMessages)) {
			return false;
		}
		return $this->_errorMessages[$key];
	}
	
	public function isError() {
		return count($this->_errorMessages) > 0;
	}
	
}