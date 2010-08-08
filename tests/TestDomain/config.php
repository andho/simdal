<?php
return array(
	'db' => array(
		'host' => '192.168.123.13',
		'username' => 'root',
		'password' => 'allied',
		'database' => 'alliedhealth',
		'class' => 'SimDAL_Persistence_MySqliAdapter'
	),
	'map' => array(
		'Project' => array(
			'columns' => array(
				'id' => array('id', 'int', array('pk'=>true, 'autoIncrement'=>true)),
				'name' => array('name', 'varchar'),
				'typeId' => array('type_id', 'int')
			),
			'associations' => array(
				array('many-to-one', 'Type', array('fk'=>'typeId'))
			)
		),
		'Type' => array(
			'columns' => array(
				'id' => array('id', 'int', array('pk'=>true, 'autoIncrement'=>true)),
				'name' => array('name', 'varchar')
			),
			'associations' => array(
				array('one-to-many', 'Project', array('fk'=>'typeId'))
			)
		)
	)
);