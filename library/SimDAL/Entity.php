<?php

class SimDAL_Entity extends SimDAL_Validator {
	
	public function setData($data) {
		if (is_object($data)) {
			foreach ($this as $key=>$value) {
				if ($key == 'id') {
					continue;
				}
				$method = 'get' . ucfirst($key);
				if (method_exists($data, $method)) {
					$this->$key = $data->$method();
				}
			}
		} else if (is_array($data)) {
			foreach ($data as $key=>$value) {
				if ($key == 'id') {
					continue;
				}
				$method = 'set' . ucfirst($key);
				if (method_exists($this, $method)) {
					$this->$method($value);
				}
			}
		}
		
		return false;
	}
	
}