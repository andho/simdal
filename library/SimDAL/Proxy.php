<?php

class SimDAL_Proxy {
	
	protected $_adapter = null;
	
	public function __construct(SimDAL_Persistence_AdapterAbstract $adapter) {
		$this->_adapter = $adapter;
	}
	
	public function setData($data) {
		if (!is_array($data) && !is_object($data)) {
			return false;
		}
		
		$class = $this->getMapper()->getClassFromEntity($this);
		$pk = $this->getMapper()->getPrimaryKey($class);
		$pkcolumn = $this->getMapper()->getColumn($class, $pk);
		
		foreach ($data as $key=>$value) {
			$setter = 'set' . ucfirst($key);
			if (!method_exists($this, $setter)) {
				continue;
			}
			if ($key == $pk && $pkcolumn[2]['autoIncrement'] == true) {
				continue;
			}
			$this->$setter($value);
		}
	}
	
}