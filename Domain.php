<?php
/**
 * SimDAL - Simple Domain Abstraction Library.
 * This library will help you to separate your domain logic from
 * your persistence logic and makes the persistence of your domain
 * objects transparent.
 * 
 * Copyright (C) 2011  Andho
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class SimDAL_Domain extends SimDAL_ErrorTriggerer {
	
	static protected $_instance = null;
	
	protected $_user = null;
	
	protected $_commits = array();
	
	public function __construct($user=null) {
		if (!is_null($user)) {
			$this->_user = $user;
		}
	}
	
	static public function getInstance($user=null, $class='Domain') {
		if (is_null(self::$_instance)) {
			if (is_null($user)) {
				throw new Exception("User is null");
			}
			self::$_instance = new $class($user);
		}
		
		return self::$_instance;
	}

	protected function _setCommit($repo) {
		$this->_commits[] = $repo;
	}
	
	public function commit() {
		$repo = array_shift($this->_commits);
		if (!is_null($repo) && !$repo->commit()) {
			$this->_errorMessages = $repo->getErrorMessages();
			return false;
		}
		
		return true;
	}
	
	public function getError() {
		$msg = array_shift($this->_errorMessages);
		
		while (is_array($msg)) {
			$msg = array_shift($msg);
		}
		
		return $msg;
	}
	
}