<?php

interface SimDAL_Persistence_AdapterInterface {
	
	public function insert($table, $data);
	
	public function update($table, $data, $id);
	
	public function findById($table, $id);
	
}