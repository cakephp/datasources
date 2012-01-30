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
App::uses('HttpSocket', 'Network/Http');
App::uses('DataSource', 'Model/Datasource');

/**
 * CouchDB Datasource
 *
 * @package datasources
 * @subpackage datasources.models.datasources
 */
class CouchDBSource extends DataSource {

/**
 * Start quote
 *
 * @var string
 */
	public $startQuote = null;

/**
 * End quote
 *
 * @var string
 */
	public $endQuote = null;

/**
 * Constructor.
 *
 * @param array $config Connection setup for CouchDB.
 * @param integer $autoConnect Autoconnect.
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
		} else {
			return true;
		}
	}

/**
 * Reconnects to the database with optional new settings.
 *
 * @param array $config New settings.
 * @return boolean Success.
 */
	public function reconnect($config = null) {
		$this->disconnect();
		$this->setConfig($config);
		$this->_sources = null;
		return $this->connect();
	}

/**
 * Connects to the database. Options are specified in the $config instance variable.
 *
 * @return boolean Connected.
 */
	public function connect() {
		if ($this->connected !== true) {
			if (isset($this->config['login']))
				$this->config['request']['uri']['user'] = $this->config['login'];

			if (isset($this->config['password']))
				$this->config['request']['uri']['pass'] = $this->config['password'];

			try {
				$this->Socket = new HttpSocket($this->config);
				$this->Socket->get();
				$this->connected = true;
			} catch (SocketException $e) {
				throw new MissingConnectionException(array('class' => $e->getMessage()));
			}
		}
		return $this->connected;
	}

/**
 * Disconnects from the database, kills the connection and advises that the
 * connection is closed, and if DEBUG is turned on (equal to 2) displays the
 * log of stored data.
 *
 * @return boolean Disconnected.
 */
	public function close() {
		if (Configure::read('debug') > 1) {
			//$this->showLog();
		}
		$this->disconnect();
	}

/**
 * Disconnect from the database.
 *
 * @return boolean Disconnected.
 */
	public function disconnect() {
		if (isset($this->results) && is_resource($this->results)) {
			$this->results = null;
		}
		$this->connected = false;
		return !$this->connected;
	}

/**
 * List of databases.
 *
 * @return array Databases.
 */
	public function listSources() {
		$databases = $this->__decode($this->Socket->get($this->__uri('_all_dbs')), true);
		return $databases;
	}

/**
 * Convenience method for DboSource::listSources().
 * Returns the names of databases in lowercase.
 *
 * @return array Lowercase databases.
 */
	public function sources($reset = false) {
		if ($reset === true) {
			$this->_sources = null;
		}
		return array_map('strtolower', $this->listSources());
	}

/**
 * Returns a description of the model (metadata).
 *
 * @param Model $model
 * @return array Schema.
 */
	public function describe($model) {
		return $model->schema;
	}

/**
 * Creates a new document in the database.
 * If the primaryKey is declared, creates the document with the specified ID.
 * To create a new database: $this->Model->curlPut('databaseName');
 *
 * @param Model $model
 * @param array $fields An array of field names to insert. If null, $model->data will be used to generate the field names.
 * @param array $values An array with key values of the fields. If null, $model->data will be used to generate the field names.
 * @return boolean Success.
 */
	public function create($model, $fields = null, $values = null) {
		$data = $model->data;
		if ($fields !== null && $values !== null) {
			$data = array_combine($fields, $values);
		}

		if (isset($data[$model->primaryKey]) && !empty($data[$model->primaryKey])) {
			$params = $data[$model->primaryKey];
		} else {
			$uuids = $this->__decode($this->Socket->get('/_uuids'));
			$params = $uuids->uuids[0];
		}

		$result = $this->__decode($this->Socket->put($this->__uri($model, $params), $this->__encode($data)));

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
 * @param array $queryData An array of information containing $queryData keys, similar to Model::find().
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
		return $this->__readResult($model, $queryData, $result);
	}

/**
 * Applies the rules to the document read.
 *
 * @param Model $model
 * @param array $queryData An array of information containing $queryData keys, similar to Model::find().
 * @param array $result Data read from the document.
 * @return mixed False if an error occurred, otherwise an array of results.
 */
	private function __readResult($model, $queryData, $result) {
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
 * @return boolean Success.
 */
	public function update($model, $fields = null, $values = null, $conditions = null) {
		$data = $model->data[$model->alias];
		if ($fields !== null && $values !== null) {
			$data = array_combine($fields, $values);
		}

		$this->__idRevData($model, $data);

		if (!empty($model->id)) {
			$result = $this->__decode($this->Socket->put($this->__uri($model, $model->id), $this->__encode($data)));
			if ($this->__checkOk($result)) {
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
	private function __idRevData(&$model, &$data) {
		if (isset($data[$model->primaryKey]) && !empty($data[$model->primaryKey])) {
			$model->id = $data[$model->primaryKey];
			unset($data[$model->primaryKey]);
		}

		if (isset($data['rev']) && !empty($data['rev'])) {
			$data['_rev'] = $data['rev'];
			unset($data['rev']);
		} else if ($model->rev) {
			$data['_rev'] = $model->rev;
		} else {
			$data['_rev'] = $this->__lastRevision($model, $model->id);
		}
	}

/**
 * The method searches for the latest revision of a document
 *
 * @param object $model
 * @param int $id
 * @return string Last revision of the document
 */
	private function __lastRevision(&$model, $id) {
		$result = $this->__decode($this->Socket->get($this->__uri($model, $id)));
		return $result->_rev;
	}

/**
 * Generates and executes a DELETE statement.
 *
 * @param Model $model
 * @param mixed $conditions
 * @return boolean Success.
 */
	public function delete($model, $conditions = null) {
		$id = $model->id;
		$rev = $model->rev;

		if (!empty($id)) {
			if (empty($rev)) $rev = $this->__lastRevision($model, $id);
			$id_rev = $id . '/?rev=' . $rev;
			$result = $this->__decode($this->Socket->delete($this->__uri($model, $id_rev)));
			return $this->__checkOk($result);
		}
		return false;
	}

/**
 * Returns an instruction to count data. (SQL, i.e. COUNT() or MAX()).
 *
 * @param model $model
 * @param string $func Lowercase name of SQL function, i.e. 'count' or 'max'.
 * @param array $params Function parameters (any values must be quoted manually).
 * @return string An SQL calculation function.
 */
	public function calculate($model, $func, $params = array()) {
		return true;
	}

/**
 * Gets full table name including prefix.
 *
 * @param mixed $model
 * @param boolean $quote
 * @return string Full name of table.
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
 * Perform any function in CouchDB.
 * The first position of the $params array is used to mount the uri.
 * The second place in the $params array is used to assemble data from a POST or PUT.
 * The third parameter is used to decode the return json.
 * The fourth parameter is used to build an associative array.
 *
 * The method can be performed by a Model of the following ways:
 *
 * 		$this->Model->curlGet('_all_dbs');
 *		$this->Model->curlPut('document_name');
 *		$this->Model->curlPost('document_name', array('field' => 'value'));
 *		$this->Model->curlDelete('document_name');
 *		$this->Model->curlPost('document_name', array('field' => 'value'), false);
 *		$this->Model->curlPost('document_name', array('field' => 'value'), true , false);
 *
 * @param string $method
 * @param array $params Parâmetros aceitos na ordem: uri, data, decode, assoc
 * @return object
 */
	public function query($method, $params) {
		list($uri, $data, $decode, $assoc) = $this->__queryParams($params);

		$request = array(
			'method' => strtoupper(str_replace('curl', '', $method))
		);

		if (!empty($uri))
			$request['uri'] = '/' . $uri;

		if (!empty($data))
			$request['body'] = $this->__encode($data);

		$result = $this->Socket->request($request);

		if ($decode === true) {
			$result = $this->__decode($result, $assoc);
		}

		return $result;
	}

/**
 * Construct the parameter of the query method.
 *
 * @param array $params
 * @return array
 */
	private function __queryParams($params) {
		if (isset($params[0])) $uri = $params[0];
		else $uri = '';

		if (isset($params[1])) $data = $params[1];
		else $data = array();

		if (isset($params[2])) $decode = $params[2];
		else $decode = true;

		if (isset($params[3])) $assoc = $params[3];
		else $assoc = true;

		return array($uri, $data, $decode, $assoc);
	}

/**
 * Get a URI.
 *
 * @param mixed $model
 * @param string $params
 * @return string URI.
 */
	private function __uri($model = null, $params = null) {
		if (!is_null($params)) {
			$params = '/' . $params;
		}
		return '/' . $this->fullTableName($model) . $params;
	}

/**
 * JSON encode.
 *
 * @param string json $data
 * @return string JSON.
 */
	private function __encode($data) {
		return json_encode($data);
	}

/**
 * JSON decode.
 *
 * @param string json $data
 * @param boolean $assoc If true, returns array. If false, returns object.
 * @return mixed Object or Array.
 */
	private function __decode($data, $assoc = false) {
		return json_decode($data, $assoc);
	}

/**
 * Checks if the result returned ok = true.
 *
 * @param object $object
 * @return boolean
 */
	private function __checkOk($object = null) {
		return isset($object->ok) && $object->ok === true;
	}
}
?>