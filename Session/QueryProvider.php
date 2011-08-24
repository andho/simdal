<?php

class SimDAL_Session_QueryProvider {
	
	public function getQuery() {
		return new SimDAL_Query($this, SimDAL_Query::TYPE_SELECT);
	}
	
}