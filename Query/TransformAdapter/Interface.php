<?php

interface SimDAL_Query_TransformAdapter_Interface {
	
	function queryToString(SimDAL_Query $query);
	
	function processQueryPrimaryTableColumns($query);
	
	function processQueryJoins($query, &$columns=null);
	
	function processWhereSets(SimDAL_Query $query);
	
	function processWhere(SimDAL_Query_Where_Interface $where);
	
	function processWhereValue($value, SimDAL_Query_Where_Interface $where);
	
	function processWhereRawValue($value);
	
	function processWhereColumn($table, $column, $where=null);
	
	function processWhereRange(SimDAL_Query_Where_Value_Range $range);
	
	function processWhereArray(array $values);
	
	function processWhereOperator($operator);
	
	function processQueryLimit($limit, $offset, SimDAL_Query $query);
	
	function processOrderBy(SimDAL_Query_OrderBy $order_by, SimDAL_Query $query);
	
}