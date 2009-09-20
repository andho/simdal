<?php

//require_once ('library/SimDAL/Entity.php');
//require_once ('library/SimDAL/Entity/NoEntityManagerException.php');

class TestDomain_Project extends SimDAL_Entity {
	
	protected $_data = array(
		'Name'=>null,
		'Description'=>null,
		'TypeId'=>null
	);
	
	public function init() {
		$this->_relations->add(new SimDAL_Entity_Relation_OneToOne('type', array('entity'=>'Type', 'key'=>'TypeId')));
	}
	
}