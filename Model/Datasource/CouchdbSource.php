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
 * @since         CakePHP Datasources v 0.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('HttpSocket', 'Network/Http');

/**
 * CouchDB Datasource
 *
 */
class CouchdbSource extends DataSource {

/**
 * Constructor
 *
 * @param array $config Connection setup for CouchDB.
 * @param integer $autoConnect Autoconnect
 * @return boolean
 */
	public function __construct($config = null, $autoConnect = true) {
		if (!isset($config['request'])) {
			$config['request']['uri'] = $config;
			$config['request']['header']['Content-Type'] = 'application/json';
		}

		parent::__construct($config);
		$this->fullDebug = Configure::read('debug') > 1;

		if ($autoConnect) {
			return $this->connect();
		}
		return true;
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
		if ($this->connected !== true) {
			if (isset($this->config['login'])) {
				$this->config['request']['uri']['user'] = $this->config['login'];
			}

			if (isset($this->config['password'])) {
				$this->config['request']['uri']['pass'] = $this->config['password'];
			}

			$this->Socket = new HttpSocket($this->config);
			if (strpos($this->Socket->get(), 'couchdb') !== false) {
				$this->connected = true;
			} else {
				trigger_error(__('CouchDB Error: connection failed'), E_USER_WARNING);
				return $this->cakeError('missingConnection', array(array('code' => 500, 'className' => 'CouchdbSource')));
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
		if (Configure::read('debug') > 1) {
			//$this->showLog();
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
		return $this->_decode($this->Socket->get($this->_uri('_all_dbs')));
	}

/**
 * Convenience method for DboSource::listSources().
 * Returns the names of databases in lowercase.
 *
 * @return array Lowercase databases
 */
	public function sources($reset = false) {
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
 * To create a new database: $this->_decode($this->Socket->put($this->_uri('databaseName')));
 *
 * @param Model $model
 * @param array $fields An array of field names to insert. If null, $model->data will be used to generate the field names.
 * @param array $values An array with key values of the fields. If null, $model->data will be used to generate the field names.
 * @return boolean Success
 */
	public function create(Model $model, $fields = null, $values = null) {
		$data = $model->data;
		if ($fields !== null && $values !== null) {
			$data = array_combine($fields, $values);
		}

		if (isset($data[$model->primaryKey]) && !empty($data[$model->primaryKey])) {
			$params = $data[$model->primaryKey];
		} else {
			$uuids = $this->_decode($this->Socket->get('/_uuids'));
			$params = $uuids->uuids[0];
		}

		$result = $this->_decode($this->Socket->put($this->_uri($model, $params), $this->_encode($data)));

		if ($this->_checkOk($result)) {
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
	public function read(Model $model, $queryData = array(), $recursive = null) {
		if ($recursive === null && isset($queryData['recursive'])) {
			$recursive = $queryData['recursive'];
		}

		if ($recursive !== null) {
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
		$result[0][$model->alias] = $this->_decode($this->Socket->get($this->_uri($model, $params)), true);
		return $this->_readResult($model, $queryData, $result);
	}

/**
 * Applies the rules to the document read.
 *
 * @param Model $model
 * @param array $queryData An array of information containing $queryData keys, similar to Model::find()
 * @param array $result Data read from the document.
 * @return mixed False if an error occurred, otherwise an array of results.
 */
	protected function _readResult(Model $model, $queryData, $result) {
		if (isset($result[0][$model->alias]['_id'])) {
			if (isset($queryData['fields']) && $queryData['fields'] === true) {
				$result[0][0]['count'] = 1;
			}

			$result[0][$model->alias]['id'] = $result[0][$model->alias]['_id'];
			$result[0][$model->alias]['rev'] = $result[0][$model->alias]['_rev'];

			unset($result[0][$model->alias]['_id']);
			unset($result[0][$model->alias]['_rev']);

			return $result;
		}
		if (isset($result[0][$model->alias]['rows'])) {
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
	public function update(Model $model, $fields = null, $values = null, $conditions = null) {
		$data = $model->data[$model->alias];
		if ($fields !== null && $values !== null) {
			$data = array_combine($fields, $values);
		}

		$this->_idRevData($model, $data);

		if (!empty($model->id)) {
			$result = $this->_decode($this->Socket->put($this->_uri($model, $model->id), $this->_encode($data)));
			if ($this->_checkOk($result)) {
				$model->rev = $result->rev;
				return true;
			}
		}
		return false;
	}

/**
 * The method sets the "id" and "rev"to avoid problems in update of a document written shortly after a create a other document.
 *
 * @param object $model
 * @param array $data
 * @return void
 */
	protected function _idRevData(Model $model, &$data) {
		if (isset($data[$model->primaryKey]) && !empty($data[$model->primaryKey])) {
			$model->id = $data[$model->primaryKey];
			unset($data[$model->primaryKey]);
		}

		if (isset($data['rev']) && !empty($data['rev'])) {
			$data['_rev'] = $data['rev'];
			unset($data['rev']);
		} elseif ($model->rev) {
			$data['_rev'] = $model->rev;
		}
	}

/**
 * Generates and executes a DELETE statement
 *
 * @param Model $model
 * @param mixed $conditions
 * @return boolean Success
 */
	public function delete(Model $model, $conditions = null) {
		$id = $model->id;
		$rev = $model->rev;

		if (!empty($id) && !empty($rev)) {
			$idRev = $id . '/?rev=' . $rev;
			$result = $this->_decode($this->Socket->delete($this->_uri($model, $idRev)));
			return $this->_checkOk($result);
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
	public function calculate(Model $model, $func, $params = array()) {
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
		} elseif (isset($this->config['prefix'])) {
			$table = $this->config['prefix'] . strval($model);
		} else {
			$table = strval($model);
		}
		return $table;
	}

/**
 * Perform any function in CouchDB
 *
 * @param string $uri
 * @param array $post
 * @return object
 */
	public function query($uri, $post) {
		return $this->_decode($this->Socket->post($uri, $this->_encode($post)));
	}

/**
 * Get a URI
 *
 * @param mixed $model
 * @param string $params
 * @return string URI
 */
	protected function _uri(Model $model = null, $params = null) {
		if ($params !== null) {
			$params = '/' . $params;
		}
		return '/' . $this->fullTableName($model) . $params;
	}

/**
 * JSON encode
 *
 * @param string json $data
 * @return string JSON
 */
	protected function _encode($data) {
		return json_encode($data);
	}

/**
 * JSON decode
 * @param string json $data
 * @param boolean $assoc If true, returns array. If false, returns object.
 * @return mixed Object or Array.
 */
	protected function _decode($data, $assoc = false) {
		return json_decode($data, $assoc);
	}

/**
 * Checks if the result returned ok = true
 *
 * @param object $object
 * @return boolean
 */
	protected function _checkOk($object = null) {
		return isset($object->ok) && $object->ok === true;
	}
}
