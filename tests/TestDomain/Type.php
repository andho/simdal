<?php

class Type {
	
	protected $id;
	protected $name;
	
	protected $projects;
	
	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function getProjects() {
		return $this->projects;
	}

	public function setProjects($projects) {
		$this->projects = $projects;
	}

	public function getId() {
		return $this->id;
	}
	
}