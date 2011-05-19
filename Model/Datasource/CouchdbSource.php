<?php
/**
 * CouchDB Datasource
 *
 * PHP version 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       datasources
 * @subpackage    datasources.models.datasources
 * @since         CakePHP Datasources v 0.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'HttpSocket');

/**
 * CouchDB Datasource
 *
 * @package datasources
 * @subpackage datasources.models.datasources
 */
class CouchdbSource extends DataSource{

/**
 * Constructor
 *
 * @param array $config Connection setup for CouchDB.
 * @param integer $autoConnect Autoconnect
 * @return boolean
 */
	public function __construct($config = null, $autoConnect = true){
		if (!isset($config['request'])) {
			$config['request']['uri'] = $config;
			$config['request']['header']['Content-Type'] = 'application/json';
		}
		parent::__construct($config);
		$this->fullDebug = Configure::read() > 1;

		if ($autoConnect) {
			return $this->connect();
		} else {
			return true;
		}
	}

/**
 * Reconnects to the database with optional new settings
 *
 * @param array $config New settings
 * @return boolean Success
 */
	public function reconnect($config = null) {
		$this->disconnect();
		$this->setConfig($config);
		$this->_sources = null;
		return $this->connect();
	}

/**
 * Connects to the database. Options are specified in the $config instance variable
 *
 * @return boolean Connected
 */
	public function connect() {
		if($this->connected !== true){
			$this->Socket = new HttpSocket($this->config);
			if(strpos($this->Socket->get(), 'couchdb') !== false){
				$this->connected = true;
			}
		}
		return $this->connected;
	}

/**
 * Disconnects from the database, kills the connection and advises that the
 * connection is closed, and if DEBUG is turned on (equal to 2) displays the
 * log of stored data.
 *
 * @return boolean Disconnected
 */
	public function close() {
		if (Configure::read() > 1) {
			// $this->showLog();
		}
		$this->disconnect();
	}

/**
 * Disconnect from the database
 *
 * @return boolean Disconnected
 */
	public function disconnect() {
		if (isset($this->results) && is_resource($this->results)) {
			$this->results = null;
		}
		$this->connected = false;
		return !$this->connected;
	}

/**
 * List of databases
 *
 * @return array Databases
 */
	public function listSources() {
		return $this->__decode($this->Socket->get($this->__uri('_all_dbs')), true);
	}

/**
 * Convenience method for DboSource::listSources().
 * Returns the names of databases in lowercase.
 *
 * @return array Lowercase databases
 */
	public function sources($reset = false){
		if ($reset === true) {
			$this->_sources = null;
		}
		return array_map('strtolower', $this->listSources());
	}

/**
 * Returns a description of the model (metadata)
 *
 * @param Model $model
 * @return array
 */
	public function describe($model) {
		return $model->schema;
	}

/**
 * Creates a new document in the database.
 * If the primaryKey is declared, creates the document with the specified ID.
 * To create a new database: $this->__decode($this->Socket->put($this->__uri('databaseName')));
 *
 * @param Model $model
 * @param array $fields An array of field names to insert. If null, $model->data will be used to generate the field names.
 * @param array $values An array with key values of the fields. If null, $model->data will be used to generate the field names.
 * @return boolean Success
 */
	public function create($model, $fields = null, $values = null) {
		$data = $model->data;
		if ($fields !== null && $values !== null) {
			$data = array_combine($fields, $values);
		}

		$params = null;
		if (isset($data[$model->primaryKey]) && !empty($data[$model->primaryKey])) {
			$params = $data[$model->primaryKey];
		}

		$result = $this->__decode($this->Socket->post($this->__uri($model, $params), $this->__encode($data)));

		if ($this->__checkOk($result)) {
			$model->id = $result->id;
			$model->rev = $result->rev;
			return true;
		}
		return false;
	}

/**
 * Reads data from a document.
 *
 * @param Model $model
 * @param array $queryData An array of information containing $queryData keys, similar to Model::find()
 * @param integer $recursive Level number of associations.
 * @return mixed False if an error occurred, otherwise an array of results.
 */
	public function read($model, $queryData = array(), $recursive = null) {
		if ($recursive === null && isset($queryData['recursive'])) {
			$recursive = $queryData['recursive'];
		}
		if (!is_null($recursive)) {
			$model->recursive = $recursive;
		}

		$params = null;

		if (empty($queryData['conditions'])) {
			$params = $params . '_all_docs?include_docs=true';
			if (!empty($queryData['limit'])) {
				$params = $params . '&limit=' . $queryData['limit'];
			}
		} else {
			if (isset($queryData['conditions'][$model->alias . '.' . $model->primaryKey])) {
				$params = $queryData['conditions'][$model->alias . '.' . $model->primaryKey];
			} else {
				$params = $queryData['conditions'][$model->primaryKey];
			}

			if ($model->recursive > -1) {
				$params = $params . '?revs_info=true';
			}
		}

		$result = array();
		$result[0][$model->alias] = $this->__decode($this->Socket->get($this->__uri($model, $params)), true);
		return $this->readResult($model, $queryData, $result);
	}

/**
 * Applies the rules to the document read.
 *
 * @param Model $model
 * @param array $queryData An array of information containing $queryData keys, similar to Model::find()
 * @param array $result Data read from the document.
 * @return mixed False if an error occurred, otherwise an array of results.
 */
	private function readResult($model, $queryData, $result) {
		if (isset($result[0][$model->alias]['_id'])) {
			if (isset($queryData['fields']) && $queryData['fields'] === true) {
				$result[0][0]['count'] = 1;
			}

			$result[0][$model->alias]['id'] = $result[0][$model->alias]['_id'];
			$result[0][$model->alias]['rev'] = $result[0][$model->alias]['_rev'];

			unset($result[0][$model->alias]['_id']);
			unset($result[0][$model->alias]['_rev']);

			return $result;
		} else if (isset($result[0][$model->alias]['rows'])) {
			$docs = array();
			foreach ($result[0][$model->alias]['rows'] as $k => $doc) {

				$docs[$k][$model->alias]['id'] = $doc['doc']['_id'];
				$docs[$k][$model->alias]['rev'] = $doc['doc']['_rev'];

				unset($doc['doc']['_id']);
				unset($doc['doc']['_rev']);
				unset($doc['doc']['id']);
				unset($doc['doc']['rev']);

				foreach ($doc['doc'] as $field => $value) {
					$docs[$k][$model->alias][$field] = $value;
				}
			}
			return $docs;
		}
		return false;
	}

/**
 * Generates and executes an UPDATE statement for a given model, fields and values.
 *
 * @param Model $model
 * @param array $fields
 * @param array $values
 * @param mixed $conditions
 * @return boolean Success
 */
	public function update($model, $fields = null, $values = null, $conditions = null) {
		$id = $model->id;
		$data = $model->data[$model->alias];
		if ($fields !== null && $values !== null) {
			$data = array_combine($fields, $values);
		}
		$data['_rev'] = $model->rev;
		if (!empty($id)) {
			$result = $this->__decode($this->Socket->put($this->__uri($model, $id), $this->__encode($data)));
			if ($this->__checkOk($result)) {
				$model->rev = $result->rev;
				return true;
			}
		}
		return false;
	}

/**
 * Generates and executes a DELETE statement
 *
 * @param Model $model
 * @param mixed $conditions
 * @return boolean Success
 */
	public function delete($model, $conditions = null) {
		$id = $model->id;
		$rev = $model->rev;

		if (!empty($id) && !empty($rev)) {
			$id_rev = $id . '/?rev=' . $rev;
			$result = $this->__decode($this->Socket->delete($this->__uri($model, $id_rev)));
			return $this->__checkOk($result);
		}
		return false;
	}

/**
 * Returns an instruction to count data. (SQL, i.e. COUNT() or MAX())
 *
 * @param model $model
 * @param string $func Lowercase name of SQL function, i.e. 'count' or 'max'
 * @param array $params Function parameters (any values must be quoted manually)
 * @return string An SQL calculation function
 */
	public function calculate($model, $func, $params = array()) {
		return true;
	}

/**
 * Gets full table name including prefix
 *
 * @param mixed $model
 * @param boolean $quote
 * @return string Full name of table
 */
	public function fullTableName($model = null, $quote = true) {
		$table = null;
		if (is_object($model)) {
			$table = $model->tablePrefix . $model->table;
		} else if (isset($this->config['prefix'])) {
			$table = $this->config['prefix'] . strval($model);
		} else {
			$table = strval($model);
		}
		return $table;
	}

/**
 * Get a URI
 *
 * @param mixed $model
 * @param string $params
 * @return string URI
 * @access private
 */
	private function __uri($model = null, $params = null) {
		if (!is_null($params)) {
			$params = '/' . $params;
		}
		return '/' . $this->fullTableName($model) . $params;
	}

/**
 * JSON encode
 *
 * @param string json $data
 * @return string JSON
 * @access private
 */
	private function __encode($data) {
		return json_encode($data);
	}

/**
 * JSON decode
 *
 * @param string json $data
 * @param boolean $assoc If true, returns array. If false, returns object.
 * @return mixed Object or Array.
 * @access private
 */
	private function __decode($data, $assoc = false) {
		return json_decode($data, $assoc);
	}

/**
 * Checks if the result returned ok = true
 *
 * @param object $object
 * @return boolean
 */
	private function __checkOk($object = null) {
		return isset($object->ok) && $object->ok === true;
	}
}
