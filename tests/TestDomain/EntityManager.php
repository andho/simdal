<?php

class TestDomain_EntityManager extends SimDAL_Entity_Manager {

	static protected $_instance = null;
	
	static public function getInstance() {
		if (self::$_instance === null) {
			self::$_instance = new TestDomain_EntityManager();
		}
		
		return self::$_instance;
	}
	
}