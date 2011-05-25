<?php

interface SimDAL_Query_TransformAdapter_AdapterAbstract {
	
	public function queryToString(SimDAL_Query $query);
	
	protected function _processQueryPrimaryTableColumns($query);
	
	protected function _processQueryJoins($query, &$columns=null);
	
	protected function _processWhereSets(SimDAL_Query $query);
	
	protected function _processWhere(SimDAL_Query_Where_Interface $where);
	
	protected function _processWhereValue($value, SimDAL_Query_Where_Interface $where);
	
	protected function _processWhereColumn($table, $column, $where=null);
	
	protected function _processWhereRange(SimDAL_Query_Where_Value_Range $range);
	
	protected function _processWhereArray(array $value);
	
	protected function _processWhereOperator($operator);
	
	protected function _processQueryLimit($limit, $offset, SimDAL_Query $query);
	
	protected function _processOrderBy(SimDAL_Query_OrderBy $order_by, SimDAL_Query $query);
	
}