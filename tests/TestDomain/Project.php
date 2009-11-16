<?php

//require_once ('library/SimDAL/Entity.php');
//require_once ('library/SimDAL/Entity/NoEntityManagerException.php');

class TestDomain_Project {
	
	public $id;
	public $name;
	public $description;
	public $typeId;
	
	private $type = null;
	
	public function getType() {
		return $this->type;
	}
	
	public function setType(TestDomain_Type $type) {
		$this->type = $type;
	}
	
}