<?php
/**
 * Sybase layer for DBO
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP Datasources v 0.1
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('DboSource', 'Model/Datasource');

/**
 * Short description for class.
 *
 * Long description for class
 *
 */
class DboSybase extends DboSource {

/**
 * Driver description
 *
 * @var string
 */
	public $description = "Sybase DBO Driver";

/**
 * Start quote for quoted identifiers
 *
 * @var string
 */
	public $startQuote = '';

/**
 * End quote for quoted identifiers
 *
 * @var string
 */
	public $endQuote = '';

/**
 * Base configuration settings for Sybase driver
 *
 * @var array
 */
	protected $_baseConfig = array(
		'persistent' => true,
		'host' => 'localhost',
		'login' => 'sa',
		'password' => '',
		'database' => 'cake',
		'port' => '4100'
	);

/**
 * Sybase column definition
 *
 * @var array
 */
	public $columns = array(
		'primary_key' => array('name' => 'numeric(9,0) IDENTITY PRIMARY KEY'),
		'string' => array('name' => 'varchar', 'limit' => '255'),
		'text' => array('name' => 'text'),
		'integer' => array('name' => 'int', 'limit' => '11', 'formatter' => 'intval'),
		'float' => array('name' => 'float', 'formatter' => 'floatval'),
		'datetime' => array('name' => 'datetime', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
		'timestamp' => array('name' => 'timestamp', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
		'time' => array('name' => 'datetime', 'format' => 'H:i:s', 'formatter' => 'date'),
		'date' => array('name' => 'datetime', 'format' => 'Y-m-d', 'formatter' => 'date'),
		'binary' => array('name' => 'image'),
		'boolean' => array('name' => 'bit')
	);

/**
 * Connects to the database using options in the given configuration array.
 *
 * @return boolean True if the database could be connected, else false
 */
	public function connect() {
		$config = $this->config;

		$port = '';
		if ($config['port'] !== null) {
			$port = ':' . $config['port'];
		}
		if ($config['persistent']) {
			$this->connection = sybase_pconnect($config['host'] . $port, $config['login'], $config['password']);
		} else {
			$this->connection = sybase_connect($config['host'] . $port, $config['login'], $config['password'], true);
		}
		$this->connected = sybase_select_db($config['database'], $this->connection);
		return $this->connected;
	}

/**
 * Check that one of the sybase extensions is installed
 *
 * @return boolean
 */
	public function enabled() {
		return extension_loaded('sybase') || extension_loaded('sybase_ct');
	}
/**
 * Disconnects from database.
 *
 * @return boolean True if the database could be disconnected, else false
 */
	public function disconnect() {
		$this->connected = !@sybase_close($this->connection);
		return !$this->connected;
	}

/**
 * Executes given SQL statement.
 *
 * @param string $sql SQL statement
 * @return resource Result resource identifier
 */
	protected function _execute($sql) {
		return sybase_query($sql, $this->connection);
	}

/**
 * Returns an array of sources (tables) in the database.
 *
 * @return array Array of tablenames in the database
 */
	public function listSources() {
		$cache = parent::listSources();
		if ($cache != null) {
			return $cache;
		}

		$result = $this->_execute("SELECT name FROM sysobjects WHERE type IN ('U', 'V')");
		if (!$result) {
			return array();
		}
		$tables = array();
		while ($line = sybase_fetch_array($result)) {
			$tables[] = $line[0];
		}

		parent::listSources($tables);
		return $tables;
	}

/**
 * Returns an array of the fields in given table name.
 *
 * @param string $tableName Name of database table to inspect
 * @return array Fields in table. Keys are name and type
 */
	public function describe($model) {
		$cache = parent::describe($model);
		if ($cache != null) {
			return $cache;
		}

		$fields = false;
		$cols = $this->query('DESC ' . $this->fullTableName($model));

		foreach ($cols as $column) {
			$colKey = array_keys($column);
			if (isset($column[$colKey[0]]) && !isset($column[0])) {
				$column[0] = $column[$colKey[0]];
			}
			if (isset($column[0])) {
				$fields[$column[0]['Field']] = array(
					'type' => $this->column($column[0]['Type']),
					'null' => $column[0]['Null'],
					'length' => $this->length($column[0]['Type']),
				);
			}
		}

		$this->_cacheDescription($model->tablePrefix . $model->table, $fields);
		return $fields;
	}

/**
 * Returns a quoted and escaped string of $data for use in an SQL statement.
 *
 * @param string $data String to be prepared for use in an SQL statement
 * @param string $column The column into which this data will be inserted
 * @param boolean $safe Whether or not numeric data should be handled automagically if no column data is provided
 * @return string Quoted and escaped data
 */
	public function value($data, $column = null, $safe = false) {
		$parent = parent::value($data, $column, $safe);

		if ($parent != null) {
			return $parent;
		}

		if ($data === null || (is_array($data) && empty($data))) {
			return 'NULL';
		}

		if ($data === '') {
			return "''";
		}

		switch ($column) {
			case 'boolean':
				$data = $this->boolean((bool)$data);
			break;
			default:
				$data = str_replace("'", "''", $data);
			break;
		}

		return "'" . $data . "'";
	}

/**
 * Begin a transaction
 *
 * @param unknown_type $model
 * @return boolean True on success, false on fail
 * (i.e. if the database/model does not support transactions).
 */
	public function begin(Model $model) {
		if (parent::begin($model)) {
			if ($this->execute('BEGIN TRAN')) {
				$this->_transactionStarted = true;
				return true;
			}
		}
		return false;
	}

/**
 * Commit a transaction
 *
 * @param unknown_type $model
 * @return boolean True on success, false on fail
 * (i.e. if the database/model does not support transactions,
 * or a transaction has not started).
 */
	public function commit(Model $model) {
		if (parent::commit($model)) {
			$this->_transactionStarted = false;
			return $this->execute('COMMIT TRAN');
		}
		return false;
	}

/**
 * Rollback a transaction
 *
 * @param unknown_type $model
 * @return boolean True on success, false on fail
 * (i.e. if the database/model does not support transactions,
 * or a transaction has not started).
 */
	public function rollback(Model $model) {
		if (parent::rollback($model)) {
			return $this->execute('ROLLBACK TRAN');
		}
		return false;
	}

/**
 * Returns a formatted error message from previous database operation.
 *
 * @todo not implemented
 * @return string Error message with error number
 */
	public function lastError() {
		return null;
	}

/**
 * Returns number of affected rows in previous database operation. If no previous operation exists,
 * this returns false.
 *
 * @return integer Number of affected rows
 */
	public function lastAffected() {
		if ($this->_result) {
			return sybase_affected_rows($this->connection);
		}
		return null;
	}

/**
 * Returns number of rows in previous resultset. If no previous resultset exists,
 * this returns false.
 *
 * @return integer Number of rows in resultset
 */
	public function lastNumRows() {
		if ($this->hasResult()) {
			return @sybase_num_rows($this->_result);
		}
		return null;
	}

/**
 * Returns the ID generated from the previous INSERT operation.
 *
 * @param unknown_type $source
 * @return in
 */
	public function lastInsertId($source = null) {
		$result = $this->fetchRow('SELECT @@IDENTITY');
		return $result[0];
	}

/**
 * Converts database-layer column types to basic types
 *
 * @param string $real Real database-layer column type (i.e. "varchar(255)")
 * @return string Abstract column type (i.e. "string")
 */
	public function column($real) {
		if (is_array($real)) {
			$col = $real['name'];
			if (isset($real['limit'])) {
				$col .= '(' . $real['limit'] . ')';
			}
			return $col;
		}

		$col = str_replace(')', '', $real);
		$limit = null;
		if (strpos($col, '(') !== false) {
			list($col, $limit) = explode('(', $col);
		}

		if (in_array($col, array('datetime', 'smalldatetime'))) {
			return 'datetime';
		}
		if (in_array($col, array('int', 'bigint', 'smallint', 'tinyint'))) {
			return 'integer';
		}
		if (in_array($col, array('float', 'double', 'real', 'decimal', 'money', 'numeric', 'smallmoney'))) {
			return 'float';
		}
		if (strpos($col, 'text') !== false) {
			return 'text';
		}
		if (in_array($col, array('char', 'nchar', 'nvarchar', 'string', 'varchar'))) {
			return 'binary';
		}
		if (in_array($col, array('binary', 'image', 'varbinary'))) {
			return 'binary';
		}

		return 'text';
	}

/**
 * Enter description here...
 *
 * @param unknown_type $results
 */
	public function resultSet(&$results) {
		$this->results =& $results;
		$this->map = array();
		$numFields = sybase_num_fields($results);
		$index = 0;
		$j = 0;

		while ($j < $numFields) {

			$column = sybase_fetch_field($results, $j);
			if (!empty($column->table)) {
				$this->map[$index++] = array($column->table, $column->name);
			} else {
				$this->map[$index++] = array(0, $column->name);
			}
			$j++;
		}
	}

/**
 * Fetches the next row from the current result set
 *
 * @return mixed
 */
	public function fetchResult() {
		if ($row = sybase_fetch_row($this->results)) {
			$resultRow = array();
			$i = 0;
			foreach ($row as $index => $field) {
				list($table, $column) = $this->map[$index];
				$resultRow[$table][$column] = $row[$index];
				$i++;
			}
			return $resultRow;
		}
		return false;
	}
}
