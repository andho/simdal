<?php

interface SimDAL_Session_Consumer {
	
	/**
	 * Should return instance of SimDAL_Session
	 * 
	 * @return SimDAL_Session
	 */
	function _getSession();
	
}