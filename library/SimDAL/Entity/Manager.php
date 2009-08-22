<?php

class SimDAL_Entity_Manager implements SimDAL_Entity_ManagerInterface {
	
	static protected $_instance = null;
	
	static public function getInstance() {
		if (self::$_instance === null) {
			self::$_instance = new SimDAL_Entity_Manager();
		}
		
		return self::$_instance;
	}
	
	public function hasRelation($entity, $parent) {
		
	}
	
	public function getBy($property, $entity) {
		
	}
	
}