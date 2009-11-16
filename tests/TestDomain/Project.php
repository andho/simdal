<?php

//require_once ('library/SimDAL/Entity.php');
//require_once ('library/SimDAL/Entity/NoEntityManagerException.php');

class TestDomain_Project {
	
	public $id;
	public $name;
	public $description;
	public $typeId;
	
	private $type = null;
	
	public function getTestDomain_Type() {
		return $this->type;
	}
	
	public function setTestDomain_Type(TestDomain_Type $type) {
		$this->type = $type;
		
		if (!is_null($type->id)) {
			$this->typeId = $type->id;
		}
	}
	
}