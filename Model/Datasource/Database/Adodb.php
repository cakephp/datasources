<?php
/**
 * AdoDB layer for DBO.
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
 * Include AdoDB files.
 */
App::import('Vendor', 'NewADOConnection', array('file' => 'adodb' . DS . 'adodb.inc.php'));

/**
 * AdoDB DBO implementation.
 *
 * Database abstraction implementation for the AdoDB library.
 *
 */
class Adodb extends DboSource {

/**
 * Enter description here...
 *
 * @var string
 */
	public $description = "ADOdb DBO Driver";

/**
 * ADOConnection object with which we connect.
 *
 * @var ADOConnection The connection object.
 */
	protected $_adodb = null;

/**
 * Array translating ADOdb column MetaTypes to cake-supported metatypes
 *
 * @var array
 */
	protected $_adodbColumnTypes = array(
		'string' => 'C',
		'text' => 'X',
		'date' => 'D',
		'timestamp' => 'T',
		'time' => 'T',
		'datetime' => 'T',
		'boolean' => 'L',
		'float' => 'N',
		'integer' => 'I',
		'binary' => 'R',
	);

/**
 * ADOdb column definition
 *
 * @var array
 */
	public $columns = array(
		'primary_key' => array('name' => 'R', 'limit' => 11),
		'string' => array('name' => 'C', 'limit' => '255'),
		'text' => array('name' => 'X'),
		'integer' => array('name' => 'I', 'limit' => '11', 'formatter' => 'intval'),
		'float' => array('name' => 'N', 'formatter' => 'floatval'),
		'timestamp' => array('name' => 'T', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
		'time' => array('name' => 'T', 'format' => 'H:i:s', 'formatter' => 'date'),
		'datetime' => array('name' => 'T', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
		'date' => array('name' => 'D', 'format' => 'Y-m-d', 'formatter' => 'date'),
		'binary' => array('name' => 'B'),
		'boolean' => array('name' => 'L', 'limit' => '1')
	);

/**
 * Connects to the database using options in the given configuration array.
 *
 * @param array $config Configuration array for connecting
 */
	public function connect() {
		$config = $this->config;
		$persistent = strrpos($config['connect'], '|p');

		if ($persistent === false) {
			$adodbDriver = $config['connect'];
			$connect = 'Connect';
		} else {
			$adodbDriver = substr($config['connect'], 0, $persistent);
			$connect = 'PConnect';
		}
		if (!$this->enabled()) {
			return false;
		}
		$this->_adodb = NewADOConnection($adodbDriver);

		$this->_adodbDataDict = NewDataDictionary($this->_adodb, $adodbDriver);

		$this->startQuote = $this->_adodb->nameQuote;
		$this->endQuote = $this->_adodb->nameQuote;

		$this->connected = $this->_adodb->$connect($config['host'], $config['login'], $config['password'], $config['database']);
		$this->_adodbMetatyper = &$this->_adodb->execute('Select 1');
		return $this->connected;
	}

/**
 * Check that AdoDB is available.
 *
 * @return boolean
 */
	public function enabled() {
		return function_exists('NewADOConnection');
	}
/**
 * Disconnects from database.
 *
 * @return boolean True if the database could be disconnected, else false
 */
	public function disconnect() {
		return $this->_adodb->Close();
	}

/**
 * Executes given SQL statement.
 *
 * @param string $sql SQL statement
 * @param array $params list of params to be bound to query
 * @param array $prepareOptions Options to be used in the prepare statement
 * @return resource Result resource identifier
 */
	protected function _execute($sql, $params = array(), $prepareOptions = array()) {
		// @codingStandardsIgnoreStart
		global $ADODB_FETCH_MODE;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		// @codingStandardsIgnoreEnd
		return $this->_adodb->execute($sql);
	}

/**
 * Returns a row from current resultset as an array .
 *
 * @param string $sql Some SQL to be executed.
 * @return array The fetched row as an array
 */
	public function fetchRow($sql = null) {
		if (!empty($sql) && is_string($sql) && strlen($sql) > 5) {
			if (!$this->execute($sql)) {
				return null;
			}
		}

		if (!$this->hasResult()) {
			return null;
		} else {
			$resultRow = $this->_result->FetchRow();
			$this->resultSet($resultRow);
			return $this->fetchResult();
		}
	}

/**
 * Begin a transaction
 *
 * @return boolean True on success, false on fail
 * (i.e. if the database/model does not support transactions).
 */
	public function begin() {
		if (parent::begin()) {
			if ($this->_adodb->BeginTrans()) {
				$this->_transactionStarted = true;
				return true;
			}
		}
		return false;
	}

/**
 * Commit a transaction
 *
 * @return boolean True on success, false on fail
 * (i.e. if the database/model does not support transactions,
 * or a transaction has not started).
 */
	public function commit() {
		if (parent::commit()) {
			$this->_transactionStarted = false;
			return $this->_adodb->CommitTrans();
		}
		return false;
	}

/**
 * Rollback a transaction
 *
 * @return boolean True on success, false on fail
 * (i.e. if the database/model does not support transactions,
 * or a transaction has not started).
 */
	public function rollback() {
		if (parent::rollback()) {
			return $this->_adodb->RollbackTrans();
		}
		return false;
	}

/**
 * Returns an array of tables in the database. If there are no tables, an error is raised and the application exits.
 *
 * @param mixed $data
 * @return array Array of tablenames in the database
 */
	public function listSources($data = null) {
		$tables = $this->_adodb->MetaTables('TABLES');

		if (!count($tables) > 0) {
			trigger_error(ERROR_NO_TABLE_LIST, E_USER_NOTICE);
			exit;
		}
		return $tables;
	}

/**
 * Returns an array of the fields in the table used by the given model.
 *
 * @param AppModel $model Model object
 * @return array Fields in table. Keys are name and type
 */
	public function describe($model) {
		$cache = parent::describe($model);
		if ($cache != null) {
			return $cache;
		}

		$fields = false;
		$cols = $this->_adodb->MetaColumns($this->fullTableName($model, false));

		foreach ($cols as $column) {
			$fields[$column->name] = array(
										'type' => $this->column($column->type),
										'null' => !$column->not_null,
										'length' => $column->max_length,
									);
			if ($column->has_default) {
				$fields[$column->name]['default'] = $column->default_value;
			}
			if ($column->primary_key == 1) {
				$fields[$column->name]['key'] = 'primary';
			}
		}

		$this->_cacheDescription($this->fullTableName($model, false), $fields);
		return $fields;
	}

/**
 * Returns a formatted error message from previous database operation.
 *
 * @param PDOStatement $query the query to extract the error from if any
 * @return string Error message
 */
	public function lastError(PDOStatement $query = null) {
		return $this->_adodb->ErrorMsg();
	}

/**
 * Returns number of affected rows in previous database operation, or false if no previous operation exists.
 *
 * @param mixed $source
 * @return integer Number of affected rows
 */
	public function lastAffected($source = null) {
		return $this->_adodb->Affected_Rows();
	}

/**
 * Returns number of rows in previous resultset, or false if no previous resultset exists.
 *
 * @param mixed $source
 * @return integer Number of rows in resultset
 */
	public function lastNumRows($source = null) {
		return $this->_result ? $this->_result->RecordCount() : false;
	}

/**
 * Returns the ID generated from the previous INSERT operation.
 *
 * @param mixed $source
 * @return integer Returns the last autonumbering ID inserted. Returns false if function not supported.
 */
	public function lastInsertId($source = null) {
		return $this->_adodb->Insert_ID();
	}

/**
 * Returns a LIMIT statement in the correct format for the particular database.
 *
 * @param integer $limit Limit of results returned
 * @param integer $offset Offset from which to start results
 * @return string SQL limit/offset statement
 * @todo Please change output string to whatever select your database accepts. adodb doesn't allow us to get the correct limit string out of it.
 */
	public function limit($limit, $offset = null) {
		if ($limit) {
			$rt = '';
			if (!strpos(strtolower($limit), 'limit') || strpos(strtolower($limit), 'limit') === 0) {
				$rt = ' LIMIT';
			}

			if ($offset) {
				$rt .= ' ' . $offset . ',';
			}

			$rt .= ' ' . $limit;
			return $rt;
		}
		return null;
		// please change to whatever select your database accepts
		// adodb doesn't allow us to get the correct limit string out of it
	}

/**
 * Converts database-layer column types to basic types
 *
 * @param string $real Real database-layer column type (i.e. "varchar(255)")
 * @return string Abstract column type (i.e. "string")
 */
	public function column($real) {
		$metaTypes = array_flip($this->_adodbColumnTypes);

		$interpretedType = $this->_adodbMetatyper->MetaType($real);

		if (!isset($metaTypes[$interpretedType])) {
			return 'text';
		}
		return $metaTypes[$interpretedType];
	}

/**
 * Returns a quoted and escaped string of $data for use in an SQL statement.
 *
 * @param string $data String to be prepared for use in an SQL statement
 * @param string $column The type of the column into which this data will be inserted
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
		return $this->_adodb->qstr($data);
	}

/**
 * Generates the fields list of an SQL query.
 *
 * @param Model $model
 * @param string $alias Alias tablename
 * @param mixed $fields
 * @param boolean $quote
 * @return array
 */
	public function fields(Model $model, $alias = null, $fields = array(), $quote = true) {
		if (empty($alias)) {
			$alias = $model->alias;
		}
		$fields = parent::fields($model, $alias, $fields, false);

		if (!$quote) {
			return $fields;
		}
		$count = count($fields);

		if ($count >= 1 && $fields[0] !== '*' && strpos($fields[0], 'COUNT(*)') === false) {
			for ($i = 0; $i < $count; $i++) {
				if (!preg_match('/^.+\\(.*\\)/', $fields[$i]) && !preg_match('/\s+AS\s+/', $fields[$i])) {
					$prepend = '';
					if (strpos($fields[$i], 'DISTINCT') !== false) {
						$prepend = 'DISTINCT ';
						$fields[$i] = trim(str_replace('DISTINCT', '', $fields[$i]));
					}

					if (strrpos($fields[$i], '.') === false) {
						$fields[$i] = $prepend . $this->name($alias) . '.' . $this->name($fields[$i]) . ' AS ' . $this->name($alias . '__' . $fields[$i]);
					} else {
						$build = explode('.', $fields[$i]);
						$fields[$i] = $prepend . $this->name($build[0]) . '.' . $this->name($build[1]) . ' AS ' . $this->name($build[0] . '__' . $build[1]);
					}
				}
			}
		}
		return $fields;
	}

/**
 * Build ResultSets and map data
 *
 * @param array $results
 */
	public function resultSet(&$results) {
		$numFields = count($results);
		$fields = array_keys($results);
		$this->results =& $results;
		$this->map = array();
		$index = 0;
		$j = 0;

		while ($j < $numFields) {
			$columnName = $fields[$j];

			if (strpos($columnName, '__')) {
				$parts = explode('__', $columnName);
				$this->map[$index++] = array($parts[0], $parts[1]);
			} else {
				$this->map[$index++] = array(0, $columnName);
			}
			$j++;
		}
	}

/**
 * Fetches the next row from the current result set
 *
 * @return unknown
 */
	public function fetchResult() {
		if (!empty($this->results)) {
			$row = $this->results;
			$this->results = null;
		} else {
			$row = $this->_result->FetchRow();
		}

		if (empty($row)) {
			return false;
		}

		$resultRow = array();
		$fields = array_keys($row);
		$count = count($fields);
		$i = 0;
		for ($i = 0; $i < $count; $i++) { //$row as $index => $field) {
			list($table, $column) = $this->map[$i];
			$resultRow[$table][$column] = $row[$fields[$i]];
		}
		return $resultRow;
	}

/**
 * Generate a database-native column schema string
 *
 * @param array $column An array structured like the following: array('name'=>'value', 'type'=>'value'[, options]),
 *                      where options can be 'default', 'length', or 'key'.
 * @return string
 */
	public function buildColumn($column) {
		$name = $type = null;
		extract(array_merge(array('null' => true), $column));

		if (empty($name) || empty($type)) {
			trigger_error('Column name or type not defined in schema', E_USER_WARNING);
			return null;
		}

		//$metaTypes = array_flip($this->_adodbColumnTypes);
		if (!isset($this->_adodbColumnTypes[$type])) {
			trigger_error("Column type {$type} does not exist", E_USER_WARNING);
			return null;
		}
		$metaType = $this->_adodbColumnTypes[$type];
		$concreteType = $this->_adodbDataDict->ActualType($metaType);
		$real = $this->columns[$type];

		//UUIDs are broken so fix them.
		if ($type === 'string' && isset($real['length']) && $real['length'] == 36) {
			$concreteType = 'CHAR';
		}

		$out = $this->name($name) . ' ' . $concreteType;

		if (isset($real['limit']) || isset($real['length']) || isset($column['limit']) || isset($column['length'])) {
			if (isset($column['length'])) {
				$length = $column['length'];
			} elseif (isset($column['limit'])) {
				$length = $column['limit'];
			} elseif (isset($real['length'])) {
				$length = $real['length'];
			} else {
				$length = $real['limit'];
			}
			$out .= '(' . $length . ')';
		}
		$_notNull = $_default = $_autoInc = $_constraint = $_unsigned = false;

		if (isset($column['key']) && $column['key'] === 'primary' && $type === 'integer') {
			$_constraint = '';
			$_autoInc = true;
		} elseif (isset($column['key']) && $column['key'] === 'primary') {
			$_notNull = '';
		} elseif (isset($column['default']) && isset($column['null']) && $column['null'] == false) {
			$_notNull = true;
			$_default = $column['default'];
		} elseif ( isset($column['null']) && $column['null'] == true) {
			$_notNull = false;
			$_default = 'NULL';
		}
		if (isset($column['default']) && $_default == false) {
			$_default = $this->value($column['default']);
		}
		if (isset($column['null']) && $column['null'] == false) {
			$_notNull = true;
		}
		//use concrete instance of DataDict to make the suffixes for us.
		$out .=	$this->_adodbDataDict->_CreateSuffix($out, $metaType, $_notNull, $_default, $_autoInc, $_constraint, $_unsigned);
		return $out;
	}

/**
 * Checks if the result is valid
 *
 * @return boolean True if the result is valid, else false
 */
	public function hasResult() {
		return is_object($this->_result) && !$this->_result->EOF;
	}
}
