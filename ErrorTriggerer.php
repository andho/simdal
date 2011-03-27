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

class SimDAL_ErrorTriggerer {
	
	protected $_errorMessages = array();
	protected $_message = array();

	protected function _error($msg, $key=null) {
		if (!is_null($key)) {
			$this->_errorMessages[$key] = $msg;
		} else {
			$this->_errorMessages[] = $msg;
		}
	}
	
	protected function _message($msg, $key=null) {
		if (!is_null($key)) {
			$this->_messages[$key] = $msg;
		} else {
			$this->_messages[] = $msg;
		}
	}
	
	public function getErrorMessages() {
		return $this->_errorMessages;
	}
	
	public function getMessages() {
		return $this->_messages;
	}
	
	public function getErrorMessage($key) {
		if (!array_key_exists($key, $this->_errorMessages)) {
			return false;
		}
		return $this->_errorMessages[$key];
	}
	
	public function getOrphanErrorMessages() {
		$msgs = array();
		foreach ($this->_errorMessages as $key=>$msg) {
			if (is_numeric($key)) {
				$msgs[] = $msg;
			}
		}
		
		return $msgs;
	}
	
	public function isError() {
		return count($this->_errorMessages) > 0;
	}
	
	public function clearErrors() {
		$this->_errorMessages = array();
	}
	
	public function hasMessages() {
		return count($this->_messages) > 0;
	}
	
}