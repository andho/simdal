<?php

return array(
	'table' => 'types',
	'columns' => array(
		'id' => array('id', 'int', array('pk'=>true, 'autoIncrement'=>true)),
		'name' => array('name', 'varchar')
	),
	'associations' => array(
		array('one-to-many', 'Project', array('fk'=>'typeId'))
	)
);