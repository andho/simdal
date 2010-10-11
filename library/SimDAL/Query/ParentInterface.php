<?php

interface SimDAL_Query_ParentInterface {
	
	public function fetch(SimDAL_Query $query,  $limit=null,  $offset=null);
	
	public function execute(SimDAL_Query $query);
	
}