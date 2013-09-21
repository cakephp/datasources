<?php
/**
 * ODBC for DBO
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
class DboOdbc extends DboSource {

/**
 * Driver description
 *
 * @var string
 */
	public $description = "ODBC DBO Driver";

/**
 * Table/column starting quote
 *
 * @var string
 */
	public $startQuote = "`";

/**
 * Table/column end quote
 *
 * @var string
 */
	public $endQuote = "`";

/**
 * Driver base configuration
 *
 * @var array
 */
	protected $_baseConfig = array(
		'persistent' => true,
		'login' => 'root',
		'password' => '',
		'database' => 'cake',
		'connect'  => 'odbc_pconnect'
	);

/**
 * Columns
 *
 * @var array
 */
	public $columns = array();

	//	public $columns = array('primary_key' => array('name' => 'int(11) DEFAULT NULL auto_increment'),
	//						'string' => array('name' => 'varchar', 'limit' => '255'),
	//						'text' => array('name' => 'text'),
	//						'integer' => array('name' => 'int', 'limit' => '11'),
	//						'float' => array('name' => 'float'),
	//						'datetime' => array('name' => 'datetime', 'format' => 'Y-m-d h:i:s', 'formatter' => 'date'),
	//						'timestamp' => array('name' => 'datetime', 'format' => 'Y-m-d h:i:s', 'formatter' => 'date'),
	//						'time' => array('name' => 'time', 'format' => 'h:i:s', 'formatter' => 'date'),
	//						'date' => array('name' => 'date', 'format' => 'Y-m-d', 'formatter' => 'date'),
	//						'binary' => array('name' => 'blob'),
	//						'boolean' => array('name' => 'tinyint', 'limit' => '1'));

/**
 * Connects to the database using options in the given configuration array.
 *
 * @return boolean True if the database could be connected, else false
 */
	public function connect() {
		$config = $this->config;
		$connect = $config['connect'];
		if (!$config['persistent']) {
			$connect = 'odbc_connect';
		}
		if (!function_exists($connect)) {
			exit('no odbc?');
		}
		$this->connected = false;
		$this->connection = $connect($config['database'], $config['login'], $config['password'],  SQL_CUR_USE_ODBC);
		if ($this->connection) {
			$this->connected = true;
		}

		return $this->connected;
	}

/**
 * Check if the ODBC extension is installed/loaded
 *
 * @return boolean
 */
	public function enabled() {
		return extension_loaded('odbc');
	}

/**
 * Disconnects from database.
 *
 * @return boolean True if the database could be disconnected, else false
 */
	public function disconnect() {
		return @odbc_close($this->connection);
	}

/**
 * Executes given SQL statement.
 *
 * @param string $sql SQL statement
 * @return resource Result resource identifier
 */
	protected function _execute($sql) {
		switch ($sql) {
			case 'BEGIN':
				return odbc_autocommit($this->connection, false);
			case 'COMMIT':
				return odbc_commit($this->connection);
			case 'ROLLBACK':
				return odbc_rollback($this->connection);
		}
		// TODO: should flags be set? possible requirement:  SQL_CURSOR_STATIC
		return odbc_exec($this->connection, $sql);
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

		$result = odbc_tables($this->connection);
		$tables = array();
		while (odbc_fetch_row($result)) {
			array_push($tables, odbc_result($result, 'TABLE_NAME'));
		}

		parent::listSources($tables);
		return $tables;
	}

/**
 * Returns an array of the fields in given table name.
 *
 * @param Model $model Model object to describe
 * @return array Fields in table. Keys are name and type
 */
	public function &describe($model) {
		$cache=parent::describe($model);

		if ($cache != null) {
			return $cache;
		}

		$fields = array();
		$sql = 'SELECT * FROM ' . $this->fullTableName($model);
		$result = odbc_exec($this->connection, $sql);
		$count = odbc_num_fields($result);

		for ($i = 1; $i <= $count; $i++) {
			$cols[$i - 1] = odbc_field_name($result, $i);
		}

		foreach ($cols as $column) {
			$type = odbc_field_type(odbc_exec($this->connection, 'SELECT ' . $column . ' FROM ' . $this->fullTableName($model)), 1);
			$fields[$column] = array('type' => $type);
		}

		$this->_cacheDescription($model->tablePrefix . $model->table, $fields);
		return $fields;
	}

/**
 * Returns a quoted and escaped string of $data for use in an SQL statement.
 *
 * @param string $data String to be prepared for use in an SQL statement
 * @param string $column The column into which this data will be inserted
 * @return string Quoted and escaped
 * @todo Add logic that formats/escapes data based on column type
 */
	public function value($data, $column = null) {
		$parent = parent::value($data, $column);

		if ($parent != null) {
			return $parent;
		}

		if ($data === null || (is_array($data) && empty($data))) {
			return 'NULL';
		}

		if (!is_numeric($data)) {
			return "'" . $data . "'";
		}

		return $data;
	}

/**
 * Returns a formatted error message from previous database operation.
 *
 * @return string Error message with error number
 */
	public function lastError() {
		if ($error = odbc_errormsg($this->connection)) {
			return odbc_error($this->connection) . ': ' . $error;
		}
		return null;
	}

/**
 * Returns number of affected rows in previous database operation. If no previous operation exists,
 * this returns false.
 *
 * @return integer Number of affected rows
 */
	public function lastAffected() {
		if ($this->hasResult()) {
			return odbc_num_rows($this->_result);
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
			return odbc_num_rows($this->_result);
		}
		return null;
	}

/**
 * Returns the ID generated from the previous INSERT operation.
 *
 * @param unknown_type $source
 * @return integer
 */
	public function lastInsertId($source = null) {
		$result = $this->fetchRow('SELECT @@IDENTITY');
		return $result[0];
	}

/**
 * Enter description here...
 *
 * @param string $real Real database-layer column type (i.e. "varchar(255)")
 */
	public function column($real) {
		if (is_array($real)) {
			$col=$real['name'];
			if (isset($real['limit'])) {
				$col .= '(' . $real['limit'] . ')';
			}
			return $col;
		}
		return $real;
	}

/**
* Enter description here...
*
* @param unknown_type $results
*/
	public function resultSet(&$results) {
		$this->results = $results;
		$numFields = odbc_num_fields($results);
		$this->map = array();
		$index = 0;
		$j = 0;
		while ($j < $numFields) {
			$column = odbc_field_name($results, $j+1);

			if (strpos($column, '_dot_') !== false) {
				list($table, $column) = explode('_dot_', $column);
				$this->map[$index++] = array($table, $column);
			} else {
				$this->map[$index++] = array(0, $column);
			}
			$j++;
		}
	}

/**
* Generates the fields list of an SQL query.
*
* @param Model $model
* @param string $alias Alias tablename
* @param mixed $fields
* @return array
*/
	public function fields(Model $model, $alias = null, $fields = null, $quote = true) {
		if (empty($alias)) {
			$alias = $model->name;
		}
		if (!is_array($fields)) {
			if ($fields != null) {
				$fields = array_map('trim', explode(',', $fields));
			} else {
				foreach ($model->tableToModel as $tableName => $modelName) {
					foreach ($this->_descriptions[$model->tablePrefix . $tableName] as $field => $type) {
						$fields[] = $modelName . '.' . $field;
					}
				}
			}
		}

		$count = count($fields);

		if ($count >= 1 && $fields[0] !== '*' && strpos($fields[0], 'COUNT(*)') === false) {
			for ($i = 0; $i < $count; $i++) {
				if (!preg_match('/^.+\\(.*\\)/', $fields[$i])) {
					$prepend = '';
					if (strpos($fields[$i], 'DISTINCT') !== false) {
						$prepend = 'DISTINCT ';
						$fields[$i] = trim(str_replace('DISTINCT', '', $fields[$i]));
					}

					if (strrpos($fields[$i], '.') === false) {
						$fields[$i] = $prepend . $this->name($alias) . '.' . $this->name($fields[$i]) . ' AS ' . $this->name($alias . '_dot_' . $fields[$i]);
					} else {
						$build = explode('.', $fields[$i]);
						$fields[$i] = $prepend . $this->name($build[0]) . '.' . $this->name($build[1]) . ' AS ' . $this->name($build[0] . '_dot_' . $build[1]);
					}
				}
			}
		}
		return $fields;
	}

/**
 * Fetches the next row from the current result set
 *
 * @return unknown
 */
	public function fetchResult() {
		if ($row = odbc_fetch_row($this->results)) {
			$resultRow = array();
			$numFields = odbc_num_fields($this->results);
			$i = 0;
			for ($i = 0; $i < $numFields; $i++) {
				list($table, $column) = $this->map[$i];
				$resultRow[$table][$column] = odbc_result($this->results, $i + 1);
			}
			return $resultRow;
		}
		return false;
	}
}
