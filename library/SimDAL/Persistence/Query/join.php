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

class SimDAL_Persistence_Query_Join {

	protected $_table;
	protected $_conditions;

	function __construct($table, $column) {
		$this->_table = $table;

		if (!$this->_conditions instanceof SimDAL_Persistence_Query_ConditionSet) {
			$this->_conditions = new SimDAL_Persistence_Query_ConditionSet();
		}

		if (is_array($column) && count($column) > 0) {
			foreach ($column as $key=>$value) {
				if (is_int($key)) {
					if (!is_array($value)) {
						$this->_conditions->addKeyValue($value);
					}
					else if (count($value) > 1) {
						$expr = new Db_Expr($value[0], $this->_table);
						$colval = $expr->__toString();

						$expr = new Db_Expr($value[1], $this->_table);
						$valval = $expr->__toString();
						if (isset($value[2])) $opval = $value[2];
						else $opval = '=';
						$this->_conditions->addKeyValue($colval, $valval, $opval);
					}
				}
				else {
					$expr = new Db_Expr($value, $this->_table);
					$this->_conditions->addKeyValue($key, $expr->__toString());
				}
			}
		} else {
			$this->_conditions->addKeyValue($column);
		}
	}

	public function __toString() {
		if ($this->_table instanceof Db_Table_Abstract ) $table = $this->_table->getTableName();
		else $table = $this->_table;
		$sql = "INNER JOIN `" . $table . "` ON ";
		$sql .= $this->_conditions->__toString();

		return $sql;
	}

}