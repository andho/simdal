<?php

interface SimDAL_Entity_ManagerInterface {
	
	static public function getInstance();
	
	public function hasRelation($dependent, $parent);
	
	public function getBy($property, $entity);
	
}