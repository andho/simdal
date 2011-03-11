<?php

return array(
	'table' => 'projects',
	'columns' => array(
		'id' => array('id', 'int', array('pk'=>true, 'autoIncrement'=>true)),
		'name' => array('name', 'varchar')
	),
	'associations' => array(
		array('many-to-one', 'Type', array('fk'=>'typeId'))
	)
);