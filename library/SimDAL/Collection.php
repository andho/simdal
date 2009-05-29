<?php

class Custom_Domain_Collection implements SeekableIterator, Countable, ArrayAccess {
	
	protected $_data = array();
	protected $_count = 0;
	protected $_pointer = 0;
	protected $_entities = array();
	protected $_unrestricted = false;

	public function __construct($data) {
		if (isset($data['data'])) {
			$options = $data;
			$data = $data['data'];
		}
		
		$this->_data = $data;
		
		if ($options['unrestricted'] === true) {
			$this->_unrestricted = true;
		}
		
		$this->_count = count($this->_data);
	}
	
	public function rewind() {
		$this->_pointer = 0;
		return $this;
	}
	
	public function current() {
		if ($this->valid() === false) {
			return null;
		}
		
		if (empty($this->_entities[$this->_pointer])) {
			$options = array(
				'data'=>$this->_data[$this->_pointer],
				'unresitricted'=>$this->_unrestricted
			);
			$this->_entities[$this->_pointer] = new $this->_entityClass($options);
		}
		
		return $this->_entities[$this->_pointer];
	}
	
	public function key() {
		return $this->_pointer;
	}
	
	public function next() {
		++$this->_pointer;
	}
	
	public function valid() {
		return $this->_pointer < $this->_count;
	}
	
	public function count() {
		return $this->_count;
	}
	
	public function seek($position) {
		$position = (int)$position;
		if ($position < 0 || $position > $this->_count) {
			throw new Exception("Illegal index: $position");
		}
		
		$this->_pointer = $position;
		return $this;
	}
	
	public function offsetExists($offset) {
		return isset($this->_data[(int) $offset]);
	}
	
    public function offsetGet($offset) {
        $this->_pointer = (int) $offset;

        return $this->current();
    }
    
    public function offsetSet($offset, $value) {}
    
    public function offsetUnset($offset) {}
    
    public function getEntity($position, $seek = false)
    {
        $key = $this->key();
        try {
            $this->seek($position);
            $entity = $this->current();
        } catch (Exception $e) {
            // // require_once 'Zend/Db/Table/Rowset/Exception.php';
            throw new Exception('No entity could be found at position ' . (int) $position);
        }
        if ($seek == false) {
            $this->seek($key);
        }
        return $entity;
    }
    
    public function toArray() {
    	return $this->_data;
    }
	
}