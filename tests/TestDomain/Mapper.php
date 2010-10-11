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
			),
			'associations' => array(
				array('many-to-one', 'Type', array('fk'=>'typeId'))
			)
		),
		'TestDomain_Type' => array(
			'table' => 'types',
			'columns' => array(
				'id' => array('id', 'int', array('pk'=>true, 'autoIncremenet'=>true)),
				'name' => array('name', 'varchar')
			),
			'associations' => array(
				array('one-to-many', 'Project', array('fk'=>'typeId'))
			)
		)
	);
	
}