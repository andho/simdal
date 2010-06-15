<?php

class SimDAL_Entity extends SimDAL_Validator {
	
	public function setData($data) {
		if (!is_array($data) && !is_object($data)) {
			return false;
		}
		
		foreach ($data as $key=>$value) {
			$method = 'set' . ucfirst($key);
			if (method_exists($this, $method)) {
				$this->$method($value);
			}
		}
	}
	
}