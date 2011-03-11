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

class SimDAL_Entity extends SimDAL_Validator {
	
	public function setData($data) {
		if (is_object($data)) {
			foreach ($this as $key=>$value) {
				if ($key == 'id') {
					continue;
				}
				$method = 'get' . ucfirst($key);
				if (method_exists($data, $method)) {
					$this->$key = $data->$method();
				}
			}
		} else if (is_array($data)) {
			foreach ($data as $key=>$value) {
				if ($key == 'id') {
					continue;
				}
				$method = 'set' . ucfirst($key);
				if (method_exists($this, $method)) {
					$this->$method($value);
				}
			}
		}
		
		return false;
	}
	
}