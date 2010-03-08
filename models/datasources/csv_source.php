<?php
/**
 * Comma Separated Values Datasource
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
 * @package       datasources
 * @subpackage    datasources.models.datasources
 * @since         CakePHP Datasources v 0.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 * A CakePHP datasource for interacting with files using comma separated value storage.
 *
 * Create a datasource in your config/database.php
 *   public $csvfile = array(
 *     'datasource' => 'Datasources.CsvSource',
 *     'path' => '/path/to/file', // Path
 *     'extension' => 'csv', // File extension
 *     'readonly' => true, // Mark for read only access
 *     'recursive' => false // Only false is supported at the moment
 *   );
 */

if (!class_exists('Folder')) {
	App::import('Core', 'Folder');
}

/**
 * CSVSource Datasource
 *
 * @package datasources
 * @subpackage datasources.models.datasources
 */
class CsvSource extends DataSource {

/**
 * Description
 *
 * @var string
 * @access public
 */
	var $description = 'CSV Data Source';

/**
 * Column delimiter
 *
 * @var string
 * @access public
 */
	var $delimiter = ';';

/**
 * Maximum Columns
 *
 * @var integer
 * @access public
 */
	var $maxCol = 0;

/**
 * Field Names
 *
 * @var mixed
 * @access public
 */
	var $fields = null;

/**
 * File Handle
 *
 * @var mixed
 * @access public
 */
	var $handle = false;

/**
 * Page to start on
 *
 * @var integer
 * @access public
 */
	var $page = 1;

/**
 * Limit of records
 *
 * @var integer
 * @access public
 */
	var $limit = 99999;

/**
 * Default configuration.
 *
 * @var array
 * @access private
 */
	var $__baseConfig = array(
		'datasource' => 'csv',
		'path' => '.',
		'extension' => 'csv',
		'readonly' => true,
		'recursive' => false);

/**
 * Constructor
 *
 * @param string $config Configuration array
 * @param boolean $autoConnect Automatically connect to / open the file
 * @access public
 */
	function __construct($config = null, $autoConnect = true) {
		$this->debug = Configure::read('debug') > 0;
		$this->fullDebug = Configure::read('debug') > 1;
		parent::__construct($config);
		if ($autoConnect) {
			$this->connect();
		}
	}

/**
 * Connects to the mailbox using options in the given configuration array.
 *
 * @return boolean True if the file could be opened.
 * @access public
 */
	function connect() {
		$this->connected = false;

		if ($this->config['readonly']) {
			$create = false;
			$mode = 0;
		} else {
			$create = true;
			$mode = 0777;
		}

		$this->connection =& new Folder($this->config['path'], $create, $mode);
		if ($this->connection) {
			$this->handle = array();
			$this->connected = true;
		}
		return $this->connected;
	}

/**
 * List available sources
 *
 * @return array of available CSV files
 * @access public
 */
	function listSources() {
		$this->config['database'] = 'csv';
		$cache = parent::listSources();
		if ($cache !== null) {
			return $cache;
		}

		$extPattern = '\.' . preg_quote($this->config['extension']);
		if ($this->config['recursive']) {
			$list = $this->connection->findRecursive('.*' . $extPattern, true);
			foreach($list as &$item) {
				$item = mb_substr($item, mb_strlen($this->config['path'] . DS));
			}
		} else {
			$list = $this->connection->find('.*' . $extPattern, true);
		}

		foreach ($list as &$item) {
			$item = preg_replace('/' . $extPattern . '$/i', '', $item);
		}

		parent::listSources($list);
		unset($this->config['database']);
		return $list;
	}

/**
 * Returns a Model description (metadata) or null if none found.
 *
 * @return mixed
 * @access public
 */
	function describe($model) {
		$this->__getDescriptionFromFirstLine($model);
		return $this->fields;
	}

/**
 * Get Description from First Line, and store into class vars
 *
 * @param Model $model
 * @return boolean True, Success
 * @access private
 */
	function __getDescriptionFromFirstLine($model) {
		$filename = $model->table . '.' . $this->config['extension'];
		$handle = fopen($this->config['path'] . DS .  $filename, 'r');
		$line = rtrim(fgets($handle));
		$data_comma = explode(',', $line);
		$data_semicolon = explode(';', $line);

		if (count($data_comma) > count($data_semicolon)) {
			$this->delimiter = ',';
			$this->fields = $data_comma;
			$this->maxCol = count($data_comma);
		} else {
			$this->delimiter = ';';
			$this->fields = $data_semicolon;
			$this->maxCol = count($data_semicolon);
		}
		fclose($handle);
		return true;
	}

/**
 * Close file handle
 *
 * @return null
 * @access public
 */
	function close() {
		if ($this->connected) {
			if ($this->handle) {
				foreach($this->handle as $h) {
				  @fclose($h);
				}
				$this->handle = false;
			}
			$this->connected = false;
		}
	}

/**
 * Read Data
 *
 * @param Model $model
 * @param array $queryData
 * @param integer $recursive Number of levels of association
 * @return mixed
 */
	function read(&$model, $queryData = array(), $recursive = null) {
		$config = $this->config;
		$filename = $config['path'] . DS . $model->table . '.' . $config['extension'];
		if (!Set::extract($this->handle, $model->table)) {
			$this->handle[$model->table] = fopen($filename, 'r');
		} else {
			fseek($this->handle[$model->table], 0, SEEK_SET) ;
		}
		$queryData = $this->__scrubQueryData($queryData);

		if (isset($queryData['limit']) && !empty($queryData['limit'])) {
			$this->limit = $queryData['limit'];
		}

		if (isset($queryData['page']) && !empty($queryData['page'])) {
			$this->page = $queryData['page'];
		}

		if (empty($queryData['fields'])) {
			$fields = $this->fields;
			$allFields = true;
		} else {
			$fields = $queryData['fields'];
			$allFields = false;
			$_fieldIndex = array();
			$index = 0;
			// generate an index array of all wanted fields
			foreach($this->fields as $field) {
				if (in_array($field,  $fields)) {
					$_fieldIndex[] = $index;
				}
				$index++;
			}
		}

		$lineCount = 0;
		$recordCount = 0;
		$findCount = 0;
		$resultSet = array();

		// Daten werden aus der Datei in ein Array $data gelesen
		while (($data = fgetcsv($this->handle[$model->table], 8192, $this->delimiter)) !== FALSE) {
			if ($lineCount == 0) {
				$lineCount++;
				continue;
			} else {
				// Skip over records, that are not complete
				if (count($data) < $this->maxCol) {
					$lineCount++;
					continue;
				}

				$record = array();
				$i = 0;
				$record['id'] = $lineCount;
				foreach($this->fields as $field) {
					$record[$field] = $data[$i++];
				}

				if ($this->__checkConditions($record, $queryData['conditions'])) {
					// Compute the virtual pagenumber
					$_page = floor($findCount / $this->limit) + 1;
					$lineCount++;
					if ($this->page <= $_page) {
						if (!$allFields) {
							$record = array();
							$record['id'] = $lineCount;
							if (count($_fieldIndex) > 0) {
								foreach($_fieldIndex as $i) {
									$record[$this->fields[$i]] = $data[$i];
								}
							}
						}
						$resultSet[] = $record ;
						$recordCount++;
					}
				}
				unset($record);
				$findCount++;

				if ($recordCount >= $this->limit) {
					break;
				}
			}
		}

		if ($model->findQueryType === 'count') {
			return array(array(array('count' => count($resultSet))));
		} else {
			return $resultSet;
		}
	}

/**
 * Private helper method to remove query metadata in given data array.
 *
 * @param array $data Data
 * @return array Cleaned Data
 * @access private
 */
	function __scrubQueryData($data) {
		foreach (array('conditions', 'fields', 'joins', 'order', 'limit', 'offset', 'group') as $key) {
			if (!isset($data[$key]) || empty($data[$key])) {
				$data[$key] = array();
			}
		}
		return $data;
	}

/**
 * Private helper method to check conditions.
 *
 * @param array $record
 * @param array $conditions
 * @return bool
 * @access private
 */
	function __checkConditions($record, $conditions) {
		$result = true;
		foreach ($conditions as $name => $value) {
			if (strtolower($name) === 'or') {
				$cond = $value;
				$result = false;
				foreach ($cond as $name => $value) {
					if (Set::matches($this->__createRule($name, $value), $record)) {
						return true;
					}
				}
			} else {
				if (!Set::matches($this->__createRule($name, $value), $record)) {
					return false;
				}
			}
		}
		return $result;
	}

/**
 * Private helper method to crete rule.
 *
 * @param string $name
 * @param string $value
 * @return string
 * @access private
 */
	function __createRule($name, $value) {
		if (strpos($name, ' ') !== false) {
			return array(str_replace(' ', '', $name) . $value);
		} else {
			return array("{$name}={$value}");
		}
	}

/**
 * Calculate
 *
 * @param Model $model 
 * @param mixed $func 
 * @param array $params 
 * @return array
 * @access public
 */
	function calculate(&$model, $func, $params = array()) {
		return array('count' => true);
	}
}
?>