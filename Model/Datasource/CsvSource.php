<?php
/**
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
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
App::uses('DataSource', 'Model/Datasource');
App::uses('Folder', 'Utility');

/**
 * Comma Separated Values Datasource
 *
 */
class CsvSource extends DataSource {

/**
 * Description
 *
 * @var string
 */
	public $description = 'CSV Data Source';

/**
 * Column delimiter
 *
 * @var string
 */
	public $delimiter = ';';

/**
 * Maximum Columns
 *
 * @var integer
 */
	public $maxCol = 0;

/**
 * Field Names
 *
 * @var mixed
 */
	public $fields = null;

/**
 * File Handle
 *
 * @var mixed
 */
	public $handle = false;

/**
 * Page to start on
 *
 * @var integer
 */
	public $page = 1;

/**
 * Limit of records
 *
 * @var integer
 */
	public $limit = 99999;

/**
 * Default configuration.
 *
 * @var array
 */
	protected $_baseConfig = array(
		'datasource' => 'csv',
		'path' => '.',
		'extension' => 'csv',
		'readonly' => true,
		'recursive' => false
	);

/**
 * Constructor
 *
 * @param string $config Configuration array
 * @param boolean $autoConnect Automatically connect to / open the file
 */
	public function __construct($config = null, $autoConnect = true) {
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
 */
	public function connect() {
		$this->connected = false;

		if ($this->config['readonly']) {
			$create = false;
			$mode = 0;
		} else {
			$create = true;
			$mode = 0777;
		}

		$this->connection = new Folder($this->config['path'], $create, $mode);
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
 */
	public function listSources($data = null) {
		$this->config['database'] = 'csv';
		$cache = parent::listSources();
		if ($cache !== null) {
			return $cache;
		}

		$extPattern = '\.' . preg_quote($this->config['extension']);
		if ($this->config['recursive']) {
			$list = $this->connection->findRecursive('.*' . $extPattern, true);
			foreach ($list as &$item) {
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
 */
	public function describe($model) {
		$this->_getDescriptionFromFirstLine($model);
		return $this->fields;
	}

/**
 * Get Description from First Line, and store into class vars
 *
 * @param Model $model
 * @return boolean True, Success
 */
	protected function _getDescriptionFromFirstLine(Model $model) {
		$filename = $model->table . '.' . $this->config['extension'];
		$handle = fopen($this->config['path'] . DS . $filename, 'r');
		$line = rtrim(fgets($handle));
		$dataComma = explode(',', $line);
		$dataSemicolon = explode(';', $line);

		if (count($dataComma) > count($dataSemicolon)) {
			$this->delimiter = ',';
			$this->fields = $dataComma;
			$this->maxCol = count($dataComma);
		} else {
			$this->delimiter = ';';
			$this->fields = $dataSemicolon;
			$this->maxCol = count($dataSemicolon);
		}
		fclose($handle);
		return true;
	}

/**
 * Close file handle
 *
 * @return null
 */
	public function close() {
		if ($this->connected) {
			if (!empty($this->handle)) {
				foreach ($this->handle as $h) {
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
	public function read(Model $model, $queryData = array(), $recursive = null) {
		$this->describe($model);
		$config = $this->config;
		$filename = $config['path'] . DS . $model->table . '.' . $config['extension'];
		if (!Set::extract($this->handle, $model->table)) {
			$this->handle[$model->table] = fopen($filename, 'r');
		} else {
			fseek($this->handle[$model->table], 0, SEEK_SET);
		}

		$queryData = $this->_scrubQueryData($queryData);

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
			foreach ($this->fields as $field) {
				if (in_array($field, $fields)) {
					$_fieldIndex[] = $index;
				}
				$index++;
			}
		}

		$lineCount = 0;
		$recordCount = 0;
		$findCount = 0;
		$resultSet = array();

		while (($data = fgetcsv($this->handle[$model->table], 8192, $this->delimiter)) !== false) {
			if ($lineCount === 0) {
				$lineCount++;
				continue;
			}

			// Skip over records, that are not complete
			if (count($data) < $this->maxCol) {
				continue;
			}

			$record = array();
			$i = 0;

			foreach ((array)$this->fields as $field) {
				$record[$model->alias][$field] = $data[$i++];
			}

			if ($this->_checkConditions($record, $queryData['conditions'], $model)) {
				// Compute the virtual pagenumber
				$page = floor($findCount / $this->limit) + 1;
				if ($this->page <= $page) {
					if (!$allFields) {
						$record = array();
						if (count($_fieldIndex) > 0) {
							foreach ($_fieldIndex as $i) {
								$record[$model->alias][$this->fields[$i]] = $data[$i];
							}
						}
					}
					$resultSet[] = $record;
					$recordCount++;
				}
			}
			unset($record);
			$findCount++;

			if ($recordCount >= $this->limit) {
				break;
			}
		}

		if ($model->findQueryType === 'count') {
			return array(array(array('count' => count($resultSet))));
		}
		return $resultSet;
	}

/**
 * Helper method to remove query metadata in given data array.
 *
 * @param array $data Data
 * @return array Cleaned Data
 */
	protected function _scrubQueryData($data) {
		foreach (array('conditions', 'fields', 'joins', 'order', /*'limit', 'offset',*/ 'group') as $key) {
			if (!isset($data[$key]) || empty($data[$key])) {
				$data[$key] = array();
			}
		}
		if (!isset($data['limit']) || empty($data['limit'])) {
			$data['limit'] = PHP_INT_MAX;
		}
		if (!isset($data['offset']) || empty($data['offset'])) {
			$data['offset'] = 0;
		}
		return $data;
	}

/**
 * Helper method to check conditions.
 *
 * @param array $record
 * @param array $conditions
 * @return boolean
 */
	protected function _checkConditions($record, $conditions, $model) {
		$result = true;
		foreach ($conditions as $name => $value) {
			$alias = $model->alias;
			if (strpos($name, '.') !== false) {
				list($alias, $name) = explode('.', $name);
			}

			if (strtolower($name) === 'or') {
				$cond = $value;
				$result = false;
				foreach ($cond as $name => $value) {
					list($condAlias, $name) = pluginSplit($name);
					if (is_array($value)) {
						foreach ($value as $val) {
							if (Set::matches($this->_createRule($name, $val), $record[$alias])) {
								$result = true;
							}
						}
					} else {
						if (Set::matches($this->_createRule($name, $value), $record[$alias])) {
							$result = true;
						}
					}
				}
			} else {
				if (!Set::matches($this->_createRule($name, $value), $record[$alias])) {
					return false;
				}
			}
		}
		return $result;
	}

/**
 * Helper method to crete rule.
 *
 * @param string $name
 * @param string $value
 * @return string
 */
	protected function _createRule($name, $value) {
		if (strpos($name, ' ') !== false) {
			return array(str_replace(' ', '', $name) . $value);
		}
		return array("{$name}={$value}");
	}

/**
 * Calculate
 *
 * @param Model $model
 * @param mixed $func
 * @param array $params
 * @return array
 */
	public function calculate(Model $model, $func, $params = array()) {
		return array('count' => true);
	}
}
