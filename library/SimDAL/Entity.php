<?php
/**
 * Class Domain Entity
 * Represents an entity in the domain.
 * Usage: declare new entity class with entity name and extend with Domain_Entity Class
 * 
 * @author Amjad Mohamed
 *
 */

class SimDAL_Entity {
	
	protected static $_defaultEntityManager = null;

	/**
	 * Array of validators that will be used to validate the data in the 
	 * entity in cases where needed (eg. when saving)
	 * 
	 * Passed object must return appropriate data when the data is passed 
	 * to the validator's 'validate' method 
	 *
	 * @var array
	 */
	protected $_validators = array();
	
	/**
	 * Array of filters that will be used to filter the data in the entity
	 * in cases where needed (eg. when saving...)
	 * 
	 * Passed object must return appropriate data when the data is passed
	 * to the filter's 'filter' method
	 *
	 * @var array
	 */
	protected $_filters = array();
	
	/**
	 * Manager object which should be injected when instantiating the object
	 *
	 * @var SimDAL_Entity_Manager
	 */
	protected $_entityManager;
	
	/**
	 * Array containing the data of the entity
	 *
	 * @var array
	 */
	protected $_data = array();
	
	/**
	 * Array containing child entities that was lazy loaded
	 *
	 * @var array
	 */
	protected $_injected = array();
	
	/**
	 * The properties of the entity that uniquely identifies it
	 *
	 * @var array
	 */
	protected $_identity = array('id');
	
	/**
	 * True if the primary key automatically generated by the 
	 * underlying data persistence layer
	 *
	 * @var boolean
	 */
	protected $_sequence = true;
	
	/**
	 * Contains modified data
	 *
	 * @var array
	 */
	protected $_modified = array();
	
	/**
	 * Restrict injecting more properties than actually in the entity 
	 *
	 * @var boolean
	 */
	protected $_restrict = true;
	
	/**
	 * An Array of relationships of the entity
	 * Each array key corresponds to a property and the value is the
	 * name of the Repository class (which corresponds to the entity class)
	 * for that property
	 *
	 * @var array
	 */
	protected $_relations = array();
	
	static public function setDefaultEntityManager(SimDAL_Entity_ManagerInterface $manager) {
		self::$_defaultEntityManager = $manager;
	}
	
	static public function reset() {
		self::$_defaultEntityManager = null;
	}
	
	/**
	 * Enables the following magic methods
	 * * get<Property> which will return the entity which corresponds to the
	 *   id in the property
	 *
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 */
	public function __call($name, $arguments) {
		if (preg_match('/^get(.*)$/', $name, $matches)) {
			
			if ( !array_key_exists( strtolower( $matches[1] ), $this->_data ) ) {
				require_once 'SimDAL/Entity/NonExistentMutatorException.php';
				throw new SimDAL_Entity_NonExistentMutatorException();
			}
			
			$key = strtolower($matches[1]); // @todo should convert from CamelCase to underscore
			
			// @todo cater for other kinds of entity relationships
			//if ($this->_entityManager->hasRelation($key) && $this->_entityManager->getRelation($key)->getType() != 'one-to-many') {
				
			//}
			
			return $this->$key;
		}
		
		if (preg_match('/^set(.*)$/', $name, $matches)) {
			
			if ( !array_key_exists( strtolower( $matches[1] ), $this->_data ) ) {
				require_once 'SimDAL/Entity/NonExistentMutatorException.php';
				throw new SimDAL_Entity_NonExistentMutatorException();
			}
			
			$key = strtolower($matches[1]); // @todo should convert from CamelCase to underscore
			$this->$key = $arguments[0];
		}
	}
	
	/**
	 * Constructor for Entity class 
	 * You can pass the Repository into the Entity for convinience functions such as save
	 *
	 * @param array $data data for the entity or configuration options out of which one of them
	 * should be the data
	 * @param Domain_Repository|null $repository
	 */
	public function __construct($data=null, $manager=null) {
		$options = array();
		
		if ($data !== null && isset($data['data'])) {
			$options = $data;
			$data = $data['data'];
		}
		
		if (null !== $data && is_array($data)) {
			if (count($this->_data) > 0 && isset($options['unrestricted']) && $options['unrestricted'] === true) {
				$this->_setDataRestricted($data);
			} else {
				$this->_setData($data);
			}
		} else if (null !== $data && !is_array($data)) {
			throw new Domain_Entity_InvalidDataException("The data provided is not an array");
		}
		
		if ($manager !== null ) {
			$this->_entityManager = $manager;
		} else if (isset($options['manager'])) {
			$this->_entityManager = $options['manager'];
		} else if ( self::$_defaultEntityManager !== null ) {
			$this->_entityManager = self::$_defaultEntityManager;
		}
		
		if ( !$this->_entityManager instanceof SimDAL_Entity_ManagerInterface ) {
			throw new SimDAL_Entity_NoEntityManagerException();
		}
	}
	
	/**
	 * Sets entity data only for properties defined at or before instantiation
	 *
	 * @param array $data
	 */
	public function _setDataRestricted(array $data) {
		foreach (array_keys($this->_data) as $key) {
			if (isset($data[$key])) {
				$this->_data[$key] = $data[$key];
			}
		}
	}
	
	/**
	 * Sets entity data passed from the array matching keys with entity properties
	 *
	 * @param array $data
	 */
	public function _setData(array $data) {
		foreach ($data as $key=>$value) {
			$this->_data[$key] = $value;
		}
	}
	
	/**
	 * Sets entity data passed from array
	 *
	 * @param array $data
	 */
	public function setFromArray(array $data) {
		if (!is_array($data)) {
			throw new Domain_Entity_InvalidDataException("The data provided is not an array");
		}
		
		if ($this->_restrict === true) {
			$this->_setDataRestricted($data);
		} else {
			$this->_setData($data);
		}
	}
	
	/**
	 * Returns the entity data as an array
	 *
	 * @return array
	 */
	public function toArray() {
		return $this->_data;
	}
	
	/**
	 * Returns the name of the Entity extracted from the Class name
	 *
	 * @return string The name of the entity extracted from the Class name
	 */
	public function getEntityName() {
		$class = get_class($this);
		
		return $class;
	}
	
	public function __get($name) {
		
		if (!isset($this->_data[$name])) {
			return null;
		}
		
		$entityClass = $this->getEntityName();
		
		if ($this->_entityManager->hasRelation($name, $entityClass)) {
			$method = 'get'.ucfirst($name).'ById';
			return $this->_entityManager->{$method}($this->_data[$name]);
		}
		
		return $this->_data[$name];
	}
	
	public function __set($name, $value) {
		if (!isset($this->_data[$name]) && $this->_data[$name] !== null) {
			return false;
		}
		
		$this->_data[$name] = $value;
	}
	
	public function __isset($name) {
		if (!isset($this->_data[$name])) {
			return false;
		}
		
		return true;
	}
	
	public function __unset($name) {
		if (isset($this->_data[$name])) {
			unset($this->_data[$name]);
		}
	}
	
	public function offsetGet($name) {
		if (!isset($this->_data[$name])) {
			return null;
		}
		
		return $this->_data[$name];
	}
	
	public function offsetSet($name, $value) {
		if (!isset($this->_data[$name]) && $this->_data[$name] !== null) {
			return false;
		}
		
		$this->_data[$name] = $value;
	}
	
	public function offsetExists($name) {
		if (!isset($this->_data[$name])) {
			return false;
		}
		
		return true;
	}
	
	public function offsetUnset($name) {
		if (isset($this->_data[$name])) {
			unset($this->_data[$name]);
		}
	}
	
	/**
	 * Returns the current Entity Manager of the entity
	 * 
	 * @return SimDAL_Entity_ManagerInterface
	 */
	public function getEntityManager() {
		return $this->_entityManager;
	}
	
	/**
	 * Convenienced method that can be used to save the entity
	 *
	 */
	public function save() {
		$this->_repository->save($this);
	}
	
}