<?php

class SimDAL_Entity_RelationCollection implements ArrayAccess {
	
	protected $_relations = array();
	
	public function hasRelation($relation) {
		if (!array_key_exists($relation, $this->_relations)) {
			return false;
		}
		
		return true;
	}
	
	public function add($relation, $entity=null, $key=null) {
		if ($relation instanceof SimDAL_Entity_Relation) {
			$this->_relations[$relation->getRelationName()] = $relation;
		}
		
		// @todo figure out the relation if the relation is not an instance of SimDAL_Entity_Relation
	}
	
	public function offsetExists($offset) {
		if (!array_key_exists($offset, $this->_relations)) {
			return false;
		}
		
		return true;
	}
	
	public function offsetGet($offset) {
		if (!array_key_exists($offset, $this->_relations)) {
			return false;
		}
		
		return $this->_relations[$offset];
	}
	
	public function offsetSet($offset, $value) {
		if (!array_key_exists($offset, $this->_relations)) {
			return false;
		}
		
		$this->_relations[$offset] = $value;
	}
	
	public function offsetUnset($offset) {
		if (!array_key_exists($offset, $this->_relations)) {
			return false;
		}
		
		unset($this->_relations[$offset]);
	}

}