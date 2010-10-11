<?php
return array(
	'db' => array(
		'filename' => '/home/likewise-open/ALLIEDINSURE/amjad/git/simdal/tests/test.db',
		'class' => 'SimDAL_Persistence_SqLite3Adapter'
	),
	'map' => array(
		'Project' => array(
			'table' => 'projects',
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
			'table' => 'types',
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