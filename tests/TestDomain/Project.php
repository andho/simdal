<?php

class Project {
	
	protected $id;
	protected $name;
	protected $typeId;
	
	protected $type;
	
	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function getTypeId() {
		return $this->typeId;
	}

	public function setTypeId($typeId) {
		$this->typeId = $typeId;
	}

	public function getType() {
		return $this->type;
	}

	public function setType(Type $type=null) {
		$this->type = $type;
	}

	public function getId() {
		return $this->id;
	}

	
}