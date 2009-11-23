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
		if (is_null($this->type)) {
			$trepo = new TestDomain_TypeRepository();
			$this->type = $trepo->findById($this->typeId);
		}
		return $this->type;
	}
	
	public function setTestDomain_Type(TestDomain_Type $type) {
		$this->typeId = $type->id;
		$this->type = $type;
	}
	
}