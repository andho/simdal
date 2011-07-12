<?php

abstract class SimDAL_Persistence_PDO_PDOAbstract extends SimDAL_Persistence_DBAdapterAbstract {
	
	/**
	 * 
	 * PDO object
	 * @var PDO
	 */
	protected $_conn;
	
	public function __destruct() {
		try {
			if (!is_null($this->_conn)) {
				$this->_conn->rollBack();
			}
		} catch (Exception $e) {
			
		}
	}
	
	protected function _returnResultRows($sql, $class=null, $lockRows = false) {
		$stmnt = $this->execute($sql);
		
		$rows = $stmnt->fetchAll(PDO::FETCH_ASSOC);
		
		$stmnt->closeCursor();
		
		return $rows;
	}
	
	protected function _returnResultRow($sql, $class=null, $lockRows = false) {
		$stmnt = $this->execute($sql);
		
		$row = $stmnt->fetch(PDO::FETCH_ASSOC);
		
		$stmnt->closeCursor();
		
		if ($row === false) {
			return null;
		}
		
		return $row;
	}
	
}