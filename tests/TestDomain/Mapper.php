<?php

class TestDomain_Mapper extends SimDAL_Mapper {
	
	protected $map = array(
		'TestDomain_Project' => array(
			'table' => 'projects',
			'columns' => array(
				'id' => array('id', 'int', array('pk'=>true, 'autoIncrement'=>true)),
				'name' => array('name', 'varchar'),
				'description' => array('description', 'varchar'),
				'typeId' => array('type_id', 'int')
			)
		)
	);
	
}