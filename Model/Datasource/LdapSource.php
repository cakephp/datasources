<?php
/**
 * LDAP Datasource
 *
 * Connect to LDAPv3 style datasource with full CRUD support.
 * Still needs HABTM support
 * Discussion at http://www.analogrithems.com/rant/2009/06/12/cakephp-with-full-crud-a-living-example/
 * Tested with OpenLDAP, Netscape Style LDAP {iPlanet, Fedora, RedhatDS} Active Directory.
 * Supports TLS, multiple ldap servers (Failover not, mirroring), Scheme Detection
 *
 * PHP Version 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright	 Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link		  http://cakephp.org CakePHP(tm) Project
 * @since		 CakePHP Datasources v 0.3
 * @license	   MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('Inflector', 'Utility');

/**
 * Ldap Datasource
 *
 */
class LdapSource extends DataSource {

/**
 * Datasource description
 *
 * @var string
 */
	public $description = 'Ldap Data Source';

/**
 * Cache Sources
 *
 * @var boolean
 */
	public $cacheSources = true;

/**
 * Schema Results
 *
 * @var boolean
 */
	public $SchemaResults = false;

/**
 * Database
 *
 * @var mixed
 */
	public $database = false;

/**
 * Count
 *
 * @var integer
 */
	public $count = 0;

/**
 * Model
 *
 * @var mixed
 */
	public $model;

/**
 * Operational Attributes
 *
 * @var mixed
 */
	public $OperationalAttributes;

/**
 * Schema DN
 *
 * @var string
 */
	public $SchemaDN;

/**
 * Schema Attributes
 *
 * @var string
 */
	public $SchemaAtributes;

/**
 * Schema Filter
 *
 * @var string
 */
	public $SchemaFilter;

/**
 * Result for formal queries
 *
 * @var mixed
 */
	protected $_result = false;

/**
 * Base configuration
 *
 * @var array
 */
	protected $_baseConfig = array(
		'host' => 'localhost',
		'port' => 389,
		'version' => 3
	);

/**
 * MultiMaster Use
 *
 * @var integer
 */
	protected $_multiMasterUse = 0;

/**
 * Descriptions
 *
 * @var array
 */
	protected $_descriptions = array();

/**
 * Constructor
 *
 * @param array $config Configuration
 */
	public function __construct($config = array()) {
		$this->debug = Configure::read('debug') > 0;
		$this->fullDebug = Configure::read('debug') > 1;
		parent::__construct($config);
		$link = $this->connect();

		// People Have been asking for this forever.
		if (isset($config['type']) && !empty($config['type'])) {
			switch ($config['type']) {
				case 'Netscape':
					$this->setNetscapeEnv();
					break;
				case 'OpenLDAP':
					$this->setOpenLDAPEnv();
					break;
				case 'ActiveDirectory':
					$this->setActiveDirectoryEnv();
					break;
				default:
					$this->setNetscapeEnv();
					break;
			}
		}

		$this->setSchemaPath();
		return $link;
	}

/**
 * Destructor
 *
 * Closes connection to the server
 *
 * @return void
 */
	public function __destruct() {
		$this->close();
		parent::__destruct();
	}

/**
 * Field name
 *
 * This looks weird, but for LDAP we just return the name of the field thats passed as an argument.
 *
 * @param string $field Field name
 * @return string Field name
 * @author Graham Weldon
 */
	public function name($field) {
		return $field;
	}

/**
 * connect([$bindDN], [$passwd])  create the actual connection to the ldap server
 * This function supports failover, so if your config['host'] is an array it will try the first one, if it fails,
 * jumps to the next and attempts to connect and so on.  If will also check try to setup any special connection options
 * needed like referal chasing and tls support
 *
 * @param string the users dn to bind with
 * @param string the password for the previously state bindDN
 * @return boolean the status of the connection
 */
	public function connect($bindDN = null, $passwd = null) {
		$config = array_merge($this->_baseConfig, $this->config);
		$this->connected = false;
		$hasFailover = false;
		if (isset($config['host']) && is_array($config['host'])) {
			$config['host'] = $config['host'][$this->_multiMasterUse];
			if (count($this->config['host']) > (1 + $this->_multiMasterUse)) {
				$hasFailOver = true;
			}
		}
		$bindDN = empty($bindDN) ? $config['login'] : $bindDN;
		$bindPasswd = empty($passwd) ? $config['password'] : $passwd;
		$this->database = @ldap_connect($config['host']);
		if (!$this->database) {
			//Try Next Server Listed
			if ($hasFailover) {
				$this->log('Trying Next LDAP Server in list:' . $this->config['host'][$this->_multiMasterUse], 'ldap.error');
				$this->_multiMasterUse++;
				$this->connect($bindDN, $passwd);
				if ($this->connected) {
					return $this->connected;
				}
			}
		}

		//Set our protocol version usually version 3
		ldap_set_option($this->database, LDAP_OPT_PROTOCOL_VERSION, $config['version']);

		if ($config['tls']) {
			if (!ldap_start_tls($this->database)) {
				$this->log("Ldap_start_tls failed", 'ldap.error');
				fatal_error("Ldap_start_tls failed");
			}
		}
		//So little known fact, if your php-ldap lib is built against openldap like pretty much every linux
		//distro out their like redhat, suse etc. The connect doesn't acutally happen when you call ldap_connect
		//it happens when you call ldap_bind.  So if you are using failover then you have to test here also.
		$bindResult = @ldap_bind($this->database, $bindDN, $bindPasswd);
		if (!$bindResult) {
			if (ldap_errno($this->database) == 49) {
				$this->log("Auth failed for '$bindDN'!", 'ldap.error');
			} else {
				$this->log('Trying Next LDAP Server in list:' . $this->config['host'][$this->_multiMasterUse], 'ldap.error');
				$this->_multiMasterUse++;
				$this->connect($bindDN, $passwd);
				if ($this->connected) {
					return $this->connected;
				}
			}

		} else {
			$this->connected = true;
		}
		return $this->connected;
	}

/**
 * auth($dn, $passwd)
 * Test if the dn/passwd combo is valid
 * This may actually belong in the component code, will look into that
 *
 * @param string bindDN to connect as
 * @param string password for the bindDN
 * @param boolean or string on error
 */
	public function auth($dn, $passwd) {
		$this->connect($dn, $passwd);
		if ($this->connected) {
			return true;
		}
		$this->log("Auth Error: for '$dn': " . $this->lastError(), 'ldap.error');
		return $this->lastError();
	}

/**
 * Disconnects database, kills the connection and says the connection is closed,
 * and if DEBUG is turned on, the log for this object is shown.
 *
 */
	public function close() {
		if ($this->fullDebug && Configure::read('debug') > 1) {
			$this->showLog();
		}
		$this->disconnect();
	}

/**
 * disconnect  close connection and release any remaining results in the buffer
 *
 */
	public function disconnect() {
		@ldap_free_result($this->results);
		@ldap_unbind($this->database);
		$this->connected = false;
		return $this->connected;
	}

/**
 * Checks if it's connected to the database
 *
 * @return boolean True if the database is connected, else false
 */
	public function isConnected() {
		return $this->connected;
	}

/**
 * Reconnects to database server with optional new settings
 *
 * @param array $config An array defining the new configuration settings
 * @return boolean True on success, false on failure
 */
	public function reconnect($config = null) {
		$this->disconnect();
		if ($config != null) {
			$this->config = array_merge($this->_baseConfig, $this->config, $config);
		}
		return $this->connect();
	}

/**
 * The "C" in CRUD
 *
 * @param Model $model
 * @param array $fields containing the field names
 * @param array $values containing the fields' values
 * @return true on success, false on error
 */
	public function create(Model $model, $fields = null, $values = null) {
		$basedn = $this->config['basedn'];
		$key = $model->primaryKey;
		$table = $model->useTable;
		$fieldsData = array();
		$id = null;
		$objectclasses = null;

		if ($fields == null) {
			unset($fields, $values);
			$fields = array_keys($model->data);
			$values = array_values($model->data);
		}

		$count = count($fields);

		for ($i = 0; $i < $count; $i++) {
			if ($fields[$i] == $key) {
				$id = $values[$i];
			} elseif ($fields[$i] === 'cn') {
				$cn = $values[$i];
			}
			$fieldsData[$fields[$i]] = $values[$i];
		}

		//Lets make our DN, this is made from the useTable & basedn + primary key. Logically this corelate to LDAP

		if (isset($table) && preg_match('/=/', $table)) {
			$table = $table . ', ';
		} else {
			$table = '';
		}
		if (isset($key) && !empty($key)) {
			$key = "$key=$id, ";
		} else {
			//Almost everything has a cn, this is a good fall back.
			$key = "cn=$cn, ";
		}
		$dn = $key . $table . $basedn;

		$res = @ldap_add($this->database, $dn, $fieldsData);
		// Add the entry
		if ($res) {
			$model->setInsertID($id);
			$model->id = $id;
			return true;
		}
		$this->log("Failed to add ldap entry: dn:$dn\nData:" . print_r($fieldsData, true) . "\n" . ldap_error($this->database), 'ldap.error');
		$model->onError();
		return false;
	}

/**
 * Returns the query
 *
 * @return mixed
 */
	public function query($find, $query, $model) {
		if (isset($query[0]) && is_array($query[0])) {
			$query = $query[0];
		}

		if (isset($find)) {
			switch ($find) {
				case 'auth':
					return $this->auth($query['dn'], $query['password']);
				case 'findSchema':
					$query = $this->_getLDAPschema();
					//$this->findSchema($query);
					break;
				case 'findConfig':
					return $this->config;
				default:
					$query = $this->read($model, $query);
					break;
			}
		}
		return $query;
	}

/**
 * The "R" in CRUD
 *
 * @param Model $model
 * @param array $queryData
 * @param integer $recursive Number of levels of association
 * @return unknown
 */
	public function read(Model $model, $queryData = array(), $recursive = null) {
		$this->model = $model;
		$this->_scrubQueryData($queryData);
		if ($recursive !== null) {
			$_recursive = $model->recursive;
			$model->recursive = $recursive;
		}

		// Check if we are doing a 'count' .. this is kinda ugly but i couldn't find a better way to do this, yet
		if (is_string($queryData['fields']) && $queryData['fields'] === 'COUNT(*) AS ' . $this->name('count')) {
			$queryData['fields'] = array();
		}

		// Prepare query data ------------------------
		$queryData['conditions'] = $this->_conditions($queryData['conditions'], $model);
		if (empty($queryData['targetDn'])) {
			$queryData['targetDn'] = $model->useTable;
		}
		$queryData['type'] = 'search';

		if (empty($queryData['order'])) {
			$queryData['order'] = array($model->primaryKey);
		}
		// Associations links --------------------------
		foreach ($model->_associations as $type) {
			foreach ($model->{$type} as $assoc => $assocData) {
				if ($model->recursive > -1) {
					$linkModel = $model->{$assoc};
					$linkedModels[] = $type . '/' . $assoc;
				}
			}
		}

		// Execute search query ------------------------
		$res = $this->_executeQuery($queryData);

		if ($this->lastNumRows() == 0) {
			return false;
		}
		// Format results  -----------------------------
		ldap_sort($this->database, $res, $queryData['order'][0]);
		$resultSet = ldap_get_entries($this->database, $res);
		$resultSet = $this->_ldapFormat($model, $resultSet);

		// Query on linked models  ----------------------
		if ($model->recursive > 0) {
			foreach ($model->_associations as $type) {
				foreach ($model->{$type} as $assoc => $assocData) {
					$db = null;
					$linkModel = $model->{$assoc};

					if ($model->useDbConfig === $linkModel->useDbConfig) {
						$db = $this;
					} else {
						$db = ConnectionManager::getDataSource($linkModel->useDbConfig);
					}

					if ($db !== null) {
						$stack = array($assoc);
						$array = array();
						$db->queryAssociation($model, $linkModel, $type, $assoc, $assocData, $array, true, $resultSet, $model->recursive - 1, $stack);
						unset ($db);
					}
				}
			}
		}

		if ($recursive !== null) {
			$model->recursive = $_recursive;
		}

		// Add the count field to the resultSet (needed by find() to work out how many entries we got back .. used when $model->exists() is called)
		$resultSet[0][0]['count'] = $this->lastNumRows();
		return $resultSet;
	}

/**
 * The "U" in CRUD
 */
	public function update(Model $model, $fields = null, $values = null) {
		$fieldsData = array();

		if ($fields === null) {
			unset($fields, $values);
			$fields = array_keys($model->data);
			$values = array_values($model->data);
		}

		$fieldCount = count($fields);
		for ($i = 0; $i < $fieldCount; $i++) {
			$fieldsData[$fields[$i]] = $values[$i];
		}

		//set our scope
		$queryData['scope'] = 'base';
		if ($model->primaryKey === 'dn') {
			$queryData['targetDn'] = $model->id;
		} elseif (isset($model->useTable) && !empty($model->useTable)) {
			$queryData['targetDn'] = $model->primaryKey . '=' . $model->id . ', ' . $model->useTable;
		}

		// fetch the record
		// Find the user we will update as we need their dn
		$resultSet = $this->read($model, $queryData, $model->recursive);

		//now we need to find out what's different about the old entry and the new one and only changes those parts
		$current = $resultSet[0][$model->alias];
		$update = $model->data[$model->alias];

		foreach ($update as $attr => $value) {
			if (isset($update[$attr]) && !empty($update[$attr])) {
				$entry[$attr] = $update[$attr];
			} elseif (!empty($current[$attr]) && (isset($update[$attr]) && empty($update[$attr]))) {
				$entry[$attr] = array();
			}
		}

		//if this isn't a password reset, then remove the password field to avoid constraint violations...
		if (!$this->inArrayInsensitive('userpassword', $update)) {
			unset($entry['userpassword']);
		}
		unset($entry['count']);
		unset($entry['dn']);

		if ($resultSet) {
			$dn = $resultSet[0][$model->alias]['dn'];

			if (@ldap_modify($this->database, $dn, $entry)) {
				return true;
			}
			$this->log("Error updating $dn: " . ldap_error($this->database) . "\nHere is what I sent: " . print_r($entry, true), 'ldap.error');
			return false;
		}

		// If we get this far, something went horribly wrong ..
		$model->onError();
		return false;
	}

/**
 * The "D" in CRUD
 */
	public function delete(Model $model) {
		// Boolean to determine if we want to recursively delete or not
		//$recursive = true;
		$recursive = false;

		if (preg_match('/dn/i', $model->primaryKey)) {
			$dn = $model->id;
		} else {
			// Find the user we will update as we need their dn
			if ($model->defaultObjectClass) {
				$options['conditions'] = sprintf( '(&(objectclass=%s)(%s=%s))', $model->defaultObjectClass, $model->primaryKey, $model->id);
			} else {
				$options['conditions'] = sprintf( '%s=%s', $model->primaryKey, $model->id);
			}
			$options['targetDn'] = $model->useTable;
			$options['scope'] = 'sub';

			$entry = $this->read($model, $options, $model->recursive);
			$dn = $entry[0][$model->name]['dn'];
		}

		if ($dn) {
			if ($recursive === true) {
				// Recursively delete LDAP entries
				if ($this->_deleteRecursively($dn)) {
					return true;
				}
			} else {
				// Single entry delete
				if (@ldap_delete($this->database, $dn)) {
					return true;
				}
			}
		}

		$model->onError();
		$errMsg = ldap_error($this->database);
		$this->log("Failed Trying to delete: $dn \nLdap Erro:$errMsg", 'ldap.error');
		return false;
	}

/**
 * Courtesy of gabriel at hrz dot uni-marburg dot de @ http://ar.php.net/ldap_delete
 */
	protected function _deleteRecursively($dn) {
		// Search for sub entries
		$subentries = ldap_list($this->database, $dn, "objectClass=*", array());
		$info = ldap_get_entries($this->database, $subentries);
		for ($i = 0; $i < $info['count']; $i++) {
			// deleting recursively sub entries
			$result = $this->_deleteRecursively($info[$i]['dn']);
			if (!$result) {
				return false;
			}
		}

		return @ldap_delete($this->database, $dn);
	}

	public function generateAssociationQuery(Model $model, Model $linkModel, $type, $association, $assocData, &$queryData, $external, &$resultSet) {
		$this->_scrubQueryData($queryData);

		switch ($type) {
			case 'hasOne':
				$id = $resultSet[$model->name][$model->primaryKey];
				$queryData['conditions'] = trim($assocData['foreignKey']) . '=' . trim($id);
				$queryData['targetDn'] = $linkModel->useTable;
				$queryData['type'] = 'search';
				$queryData['limit'] = 1;
				return $queryData;

			case 'belongsTo':
				$id = $resultSet[$model->name][$assocData['foreignKey']];
				$queryData['conditions'] = trim($linkModel->primaryKey) . '=' . trim($id);
				$queryData['targetDn'] = $linkModel->useTable;
				$queryData['type'] = 'search';
				$queryData['limit'] = 1;
				return $queryData;

			case 'hasMany':
				$id = $resultSet[$model->name][$model->primaryKey];
				$queryData['conditions'] = trim($assocData['foreignKey']) . '=' . trim($id);
				$queryData['targetDn'] = $linkModel->useTable;
				$queryData['type'] = 'search';
				$queryData['limit'] = $assocData['limit'];
				return $queryData;

			case 'hasAndBelongsToMany':
				return null;
		}
		return null;
	}

	public function queryAssociation(Model $model, &$linkModel, $type, $association, $assocData, &$queryData, $external, &$resultSet, $recursive, $stack) {
		if (!isset($resultSet) || !is_array($resultSet)) {
			if (Configure::read('debug') > 0) {
				echo '<div style = "font: Verdana bold 12px; color: #FF0000">SQL Error in model ' . $model->name . ': ';
				if (isset($this->error) && $this->error != null) {
					echo $this->error;
				}
				echo '</div>';
			}
			return null;
		}

		$count = count($resultSet);
		for ($i = 0; $i < $count; $i++) {
			$row = $resultSet[$i];
			$queryData = $this->generateAssociationQuery($model, $linkModel, $type, $association, $assocData, $queryData, $external, $row);
			$fetch = $this->_executeQuery($queryData);
			$fetch = ldap_get_entries($this->database, $fetch);
			$fetch = $this->_ldapFormat($linkModel, $fetch);

			if (!empty($fetch) && is_array($fetch)) {
					if ($recursive > 0) {
						foreach ($linkModel->_associations as $type1) {
							foreach ($linkModel->{$type1 } as $assoc1 => $assocData1) {
								$deepModel = $linkModel->{$assocData1['className']};
								if ($deepModel->alias !== $model->name) {
									$tmpStack = $stack;
									$tmpStack[] = $assoc1;
									if ($linkModel->useDbConfig == $deepModel->useDbConfig) {
										$db = $this;
									} else {
										$db = ConnectionManager::getDataSource($deepModel->useDbConfig);
									}
									$queryData = array();
									$db->queryAssociation($linkModel, $deepModel, $type1, $assoc1, $assocData1, $queryData, true, $fetch, $recursive - 1, $tmpStack);
								}
							}
						}
					}
				$this->_mergeAssociation($resultSet[$i], $fetch, $association, $type);

			} else {
				$tempArray[0][$association] = false;
				$this->_mergeAssociation($resultSet[$i], $tempArray, $association, $type);
			}
		}
	}

/**
 * Returns a formatted error message from previous database operation.
 *
 * @return string Error message with error number
 */
	public function lastError() {
		if (ldap_errno($this->database)) {
			return ldap_errno($this->database) . ': ' . ldap_error($this->database);
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
		if ($this->_result && is_resource($this->_result)) {
			return @ldap_count_entries($this->database, $this->_result);
		}
		return null;
	}

/**
 * Convert Active Directory timestamps to unix ones
 *
 * @param integer $adTimestamp Active directory timestamp
 * @return integer Unix timestamp
 */
	public function convertTimestampADToUnix($adTimestamp) {
		$epochDiff = 11644473600; // difference 1601<>1970 in seconds. see reference URL
		$dateTimestamp = $adTimestamp * 0.0000001;
		$unixTimestamp = $dateTimestamp - $epochDiff;
		return $unixTimestamp;
	}

/**
 * The following was kindly "borrowed" from the excellent phpldapadmin project
 *
 * @return array
 */
	protected function _getLDAPschema() {
		$schemaTypes = array('objectclasses', 'attributetypes');
		$this->results = @ldap_read($this->database, $this->SchemaDN, $this->SchemaFilter, $schemaTypes, 0, 0, 0, LDAP_DEREF_ALWAYS);
		if ($this->results === null) {
			$this->log( "LDAP schema filter $schemaFilter is invalid!", 'ldap.error');
			return array();
		}

		$schemaEntries = @ldap_get_entries($this->database, $this->results);
		if (!$schemaEntries) {
			return array();
		}

		$return = array();
		foreach ($schemaTypes as $n) {
			$schemaTypeEntries = $schemaEntries[0][$n];
			for ($x = 0; $x < $schemaTypeEntries['count']; $x++) {
				$entry = array();
				$strings = preg_split('/[\s,]+/', $schemaTypeEntries[$x], -1, PREG_SPLIT_DELIM_CAPTURE);
				$strCount = count($strings);
				for ($i = 0; $i < $strCount; $i++) {
					switch ($strings[$i]) {
						case '(':
							break;
						case 'NAME':
							if ($strings[$i + 1] !== '(') {
								do {
									$i++;
									if (!isset($entry['name']) || strlen($entry['name']) === 0) {
										$entry['name'] = $strings[$i];
									} else {
										$entry['name'] .= ' ' . $strings[$i];
									}
								} while (!preg_match('/\'$/s', $strings[$i]));
							} else {
								$i++;
								do {
									$i++;
									if (!isset($entry['name'] ) || strlen($entry['name']) === 0) {
										$entry['name'] = $strings[$i];
									} else {
										$entry['name'] .= ' ' . $strings[$i];
									}
								} while (!preg_match( '/\'$/s', $strings[$i]));
								do {
									$i++;
								} while (!preg_match( '/\)+\)?/', $strings[$i]));
							}

							$entry['name'] = preg_replace('/^\'/', '', $entry['name']);
							$entry['name'] = preg_replace('/\'$/', '', $entry['name']);
							break;
						case 'DESC':
							do {
								$i++;
								if (!isset($entry['description']) || strlen($entry['description']) === 0) {
									$entry['description'] = $strings[$i];
								} else {
									$entry['description'] .= ' ' . $strings[$i];
								}
							} while (!preg_match( '/\'$/s', $strings[$i]));
							break;
						case 'OBSOLETE':
							$entry['is_obsolete'] = true;
							break;
						case 'SUP':
							$entry['sup_classes'] = array();
							if ($strings[$i + 1] !== '(') {
								$i++;
								array_push($entry['sup_classes'], preg_replace( "/'/", '', $strings[$i]));
							} else {
								$i++;
								do {
									$i++;
									if ($strings[$i] !== '$') {
										array_push($entry['sup_classes'], preg_replace( "/'/", '', $strings[$i]));
									}
								} while (! preg_match('/\)+\)?/', $strings[$i + 1]));
							}
							break;
						case 'ABSTRACT':
							$entry['type'] = 'abstract';
							break;
						case 'STRUCTURAL':
							$entry['type'] = 'structural';
							break;
						case 'SINGLE-VALUE':
							$entry['multiValue'] = 'false';
							break;
						case 'AUXILIARY':
							$entry['type'] = 'auxiliary';
							break;
						case 'MUST':
							$entry['must'] = array();
							$i = $this->_parseList(++$i, $strings, $entry['must']);

							break;

						case 'MAY':
							$entry['may'] = array();
							$i = $this->_parseList(++$i, $strings, $entry['may']);

							break;
						default:
							if (preg_match( '/[\d\.]+/i', $strings[$i]) && $i == 1) {
								$entry['oid'] = $strings[$i];
							}
							break;
					}
				}
				if (!isset($return[$n]) || !is_array($return[$n])) {
					$return[$n] = array();
				}
				// Make lowercase for consistency
				$return[strtolower($n)][strtolower($entry['name'])] = $entry;
				//array_push($return[$n][$entry['name']], $entry);
			}
		}

		return $return;
	}

/**
 * LdapSource::_parseList()
 *
 * @param integer $i
 * @param array $strings
 * @param array $attrs
 * @return integer
 */
	protected function _parseList($i, $strings, &$attrs) {
	/**
	 ** A list starts with a ( followed by a list of attributes separated by $ terminated by )
	 ** The first token can therefore be a ( or a (NAME or a (NAME)
	 ** The last token can therefore be a ) or NAME)
	 ** The last token may be terminate by more than one bracket
	 */
		$string = $strings[$i];
		if (!preg_match('/^\(/', $string)) {
			// A bareword only - can be terminated by a ) if the last item
			if (preg_match('/\)+$/', $string)) {
				$string = preg_replace('/\)+$/', '', $string);
			}
			array_push($attrs, $string);
		} elseif (preg_match('/^\(.*\)$/', $string)) {
			$string = preg_replace('/^\(/', '', $string);
			$string = preg_replace('/\)+$/', '', $string);
			array_push($attrs, $string);
		} else {
			// Handle the opening cases first
			if ($string === '(') {
				$i++;

			} elseif (preg_match('/^\(./', $string)) {
				$string = preg_replace('/^\(/', '', $string);
				array_push($attrs, $string);
				$i++;
			}

			// Token is either a name, a $ or a ')'
			// NAME can be terminated by one or more ')'
			while (!preg_match('/\)+$/', $strings[$i])) {
				$string = $strings[$i];
				if ($string === '$') {
					$i++;
					continue;
				}

				if (preg_match('/\)$/', $string)) {
					$string = preg_replace('/\)+$/', '', $string);
				} else {
					$i++;
				}
				array_push($attrs, $string);
			}
		}
		sort($attrs);

		return $i;
	}

/**
 * Function not supported
 */
	public function execute($query) {
		return null;
	}

/**
 * Function not supported
 */
	public function fetchAll($query, $cache = true) {
		return array();
	}

/**
 * Log given LDAP query.
 *
 * @param string $query LDAP statement
 * @todo: Add hook to log errors instead of returning false
 */
	public function logQuery($query) {
		$this->_queriesCnt++;
		$this->_queriesTime += $this->took;
		$this->_queriesLog[] = array(
			'query' => $query,
			'error' => $this->error,
			'affected' => $this->affected,
			'numRows' => $this->numRows,
			'took' => $this->took
		);
		if (count($this->_queriesLog) > $this->_queriesLogMax) {
			array_pop($this->_queriesLog);
		}
		if ($this->error) {
			return false;
		}
	}

/**
 * Outputs the contents of the queries log.
 *
 * @param boolean $sorted
 */
	public function showLog($sorted = false) {
		if ($sorted) {
			$log = sortByKey($this->_queriesLog, 'took', 'desc', SORT_NUMERIC);
		} else {
			$log = $this->_queriesLog;
		}

		if ($this->_queriesCnt > 1) {
			$text = 'queries';
		} else {
			$text = 'query';
		}

		if (php_sapi_name() !== 'cli') {
			print ("<table id=\"cakeSqlLog\" cellspacing=\"0\" border = \"0\">\n<caption>{$this->_queriesCnt} {$text} took {$this->_queriesTime} ms</caption>\n");
			print ("<thead>\n<tr><th>Nr</th><th>Query</th><th>Error</th><th>Affected</th><th>Num. rows</th><th>Took (ms)</th></tr>\n</thead>\n<tbody>\n");

			foreach ($log as $k => $i) {
				print ("<tr><td>" . ($k + 1) . "</td><td>{$i['query']}</td><td>{$i['error']}</td><td style = \"text-align: right\">{$i['affected']}</td><td style = \"text-align: right\">{$i['numRows']}</td><td style = \"text-align: right\">{$i['took']}</td></tr>\n");
			}
			print ("</table>\n");
		} else {
			foreach ($log as $k => $i) {
				print (($k + 1) . ". {$i['query']} {$i['error']}\n");
			}
		}
	}

/**
 * Output information about a LDAP query. The query, number of rows in resultset,
 * and execution time in microseconds. If the query fails, an error is output instead.
 *
 * @param string $query Query to show information on.
 */
	public function showQuery($query) {
		$error = $this->error;
		if (strlen($query) > 200 && !$this->fullDebug) {
			$query = substr($query, 0, 200) . '[...]';
		}

		if ($this->debug || $error) {
			print ("<p style = \"text-align:left\"><b>Query:</b> {$query} <small>[Aff:{$this->affected} Num:{$this->numRows} Took:{$this->took}ms]</small>");
			if ($error) {
				print ("<br /><span style = \"color:Red;text-align:left\"><b>ERROR:</b> {$this->error}</span>");
			}
			print ('</p>');
		}
	}

	protected function _conditions($conditions, $model) {
		$res = '';
		$key = $model->primaryKey;
		$name = $model->name;

		if (is_array($conditions) && count($conditions) === 1) {
			$sqlHack = "$name.$key";
			$conditions = str_ireplace($sqlHack, $key, $conditions);
			foreach ($conditions as $k => $v) {
				if ($k === $name . '.dn') {
					$res = substr($v, 0, strpos($v, ','));
				} elseif (($k === $sqlHack) && (empty($v) || $v === '*')) {
					$res = 'objectclass=*';
				} elseif ($k === $sqlHack) {
					$res = "$key=$v";
				} else {
					$res = "$k=$v";
				}
			}
			$conditions = $res;
		}

		if (is_array($conditions)) {
			// Conditions expressed as an array
			if (empty($conditions)) {
				$res = 'objectclass=*';
			}
		}

		if (empty($conditions)) {
			$res = 'objectclass=*';
		} else {
			$res = $conditions;
		}
		return $res;
	}

/**
 * Convert an array into a ldap condition string
 *
 * @param array $conditions condition
 * @return string
 */
	protected function _conditionsArrayToString($conditions) {
		$opsRec = array('and' => array('prefix' => '&'), 'or' => array('prefix' => '|'));
		$opsNeg = array('and not' => array(), 'or not' => array(), 'not equals' => array());
		$opsTer = array('equals' => array('null' => '*'));

		$ops = array_merge($opsRec, $opsNeg, $opsTer);

		if (is_array($conditions)) {
			$operand = array_keys($conditions);
			$operand = $operand[0];

			if (!in_array($operand, array_keys($ops))) {
				$this->log("No operators defined in LDAP search conditions.", 'ldap.error');
				return null;
			}

			$children = $conditions[$operand];

			if (in_array($operand, array_keys($opsRec))) {
				if (!is_array($children)) {
					return null;
				}
				$tmp = '(' . $opsRec[$operand]['prefix'];
				foreach ($children as $key => $value) {
					$child = array($key => $value);
					$tmp .= $this->_conditionsArrayToString($child);
				}
				return $tmp . ')';
			}

			if (in_array($operand, array_keys($opsNeg))) {
				if (!is_array($children)) {
					return null;
				}
				$nextOperand = trim(str_replace('not', '', $operand));

				return '(!' . $this->_conditionsArrayToString(array($nextOperand => $children)) . ')';
			}

			if (in_array($operand, array_keys($opsTer))) {
				$tmp = '';
				foreach ($children as $key => $value) {
					if (!is_array($value)) {
						$tmp .= '(' . $key . '=' . ($value === null ? $opsTer['equals']['null'] : $value) . ')';
					} else {
						foreach ($value as $subvalue) {
							$tmp .= $this->_conditionsArrayToString(array('equals' => array($key => $subvalue)));
						}
					}
				}
				return $tmp;
			}
		}
	}

	public function checkBaseDn($targetDN) {
		$parts = preg_split('/,\s*/', $this->config['basedn']);
		$pattern = '/' . implode(',\s*', $parts) . '/i';
		return preg_match($pattern, $targetDN);
	}

	protected function _executeQuery($queryData = array(), $cache = true) {
		$t = microtime(true);

		$pattern = '/,[ \t]+(\w+)=/';
		$queryData['targetDn'] = preg_replace($pattern, ', $1=', $queryData['targetDn']);
		if ($this->checkBaseDn($queryData['targetDn']) == 0) {
			$this->log("Missing BaseDN in " . $queryData['targetDn'], 'debug');

			if ($queryData['targetDn'] != null) {
				$seperator = (substr($queryData['targetDn'], -1) === ',') ? '' : ',';
				if (strpos($queryData['targetDn'], '=') === false && (isset($this->model) && !empty($this->model))) {
					//Fix TargetDN here
					$key = $this->model->primaryKey;
					$table = $this->model->useTable;
					$queryData['targetDn'] = $key . '=' . $queryData['targetDn'] . ', ' . $table . $seperator . $this->config['basedn'];
				} else {
					$queryData['targetDn'] = $queryData['targetDn'] . $seperator . $this->config['basedn'];
				}
			} else {
				$queryData['targetDn'] = $this->config['basedn'];
			}
		}

		$query = $this->_queryToString($queryData);
		if ($cache && isset($this->_queryCache[$query])) {
			if (strpos(trim(strtolower($query)), $queryData['type']) !== false) {
				$res = $this->_queryCache[$query];
			}
		} else {

			switch ($queryData['type']) {
				case 'search':
					// TODO pb ldap_search &$queryData['limit']
					if (empty($queryData['fields'])) {
						$queryData['fields'] = $this->defaultNSAttributes();
					}

					//Handle LDAP Scope
					if (isset($queryData['scope']) && $queryData['scope'] === 'base') {
						$res = @ldap_read($this->database, $queryData['targetDn'], $queryData['conditions'], $queryData['fields']);
					} elseif (isset($queryData['scope']) && $queryData['scope'] === 'one') {
						$res = @ldap_list($this->database, $queryData['targetDn'], $queryData['conditions'], $queryData['fields']);
					} else {
						if ($queryData['fields'] == 1) {
							$queryData['fields'] = array();
						}
						$res = @ldap_search($this->database, $queryData['targetDn'], $queryData['conditions'], $queryData['fields'], 0, $queryData['limit']);
					}

					if (!$res) {
						$res = false;
						$errMsg = ldap_error($this->database);
						$this->log("Query Params Failed:" . print_r($queryData, true) . ' Error: ' . $errMsg, 'ldap.error');
						$this->count = 0;
					} else {
						$this->count = ldap_count_entries($this->database, $res);
					}

					if ($cache) {
						if (strpos(trim(strtolower($query)), $queryData['type']) !== false) {
							$this->_queryCache[$query] = $res;
						}
					}
					break;
				case 'delete':
					$res = @ldap_delete($this->database, $queryData['targetDn'] . ',' . $this->config['basedn']);
					break;
				default:
					$res = false;
					break;
			}
		}

		$this->_result = $res;
		$this->took = round((microtime(true) - $t) * 1000, 0);
		$this->error = $this->lastError();
		$this->numRows = $this->lastNumRows();

		if ($this->fullDebug) {
			$this->logQuery($query);
		}

		return $this->_result;
	}

	protected function _queryToString($queryData) {
		$tmp = '';
		if (!empty($queryData['scope'])) {
			$tmp .= ' | scope: ' . $queryData['scope'] . ' ';
		}
		if (!empty($queryData['conditions'])) {
			$tmp .= ' | cond: ' . $queryData['conditions'] . ' ';
		}
		if (!empty($queryData['targetDn'])) {
			$tmp .= ' | targetDn: ' . $queryData['targetDn'] . ' ';
		}
		$fields = '';
		if (!empty($queryData['fields']) && is_array($queryData['fields'] )) {
			$fields = implode(', ', $queryData['fields']);
			$tmp .= ' |fields: ' . $fields . ' ';
		}

		if (!empty($queryData['order'])) {
			$tmp .= ' | order: ' . $queryData['order'][0] . ' ';
		}
		if (!empty($queryData['limit'])) {
			$tmp .= ' | limit: ' . $queryData['limit'];
		}
		return $queryData['type'] . $tmp;
	}

/**
 * LdapSource::_ldapFormat()
 *
 * @param Model $model
 * @param array $data
 * @return array
 */
	protected function _ldapFormat(Model $model, $data) {
		$res = array();

		foreach ($data as $key => $row) {
			if ($key === 'count') {
				continue;
			}
			foreach ($row as $key1 => $param) {
				if ($key1 === 'dn') {
					$res[$key][$model->name][$key1] = $param;
					continue;
				}
				if (!is_numeric($key1)) {
					continue;
				}
				if ($row[$param]['count'] === 1) {
					$res[$key][$model->name][$param] = $row[$param][0];
				} else {
					foreach ($row[$param] as $key2 => $item) {
						if ($key2 === 'count') {
							continue;
						}
						$res[$key][$model->name][$param][] = $item;
					}
				}
			}
		}
		return $res;
	}

/**
 * LdapSource::_ldapQuote()
 *
 * @param string $str
 * @return string
 */
	protected function _ldapQuote($str) {
		return str_replace(
			array('\\', ' ', '*', '(', ')'),
			array('\\5c', '\\20', '\\2a', '\\28', '\\29'),
			$str
		);
	}

	protected function _mergeAssociation(&$data, $merge, $association, $type) {
		if (isset($merge[0]) && !isset($merge[0][$association])) {
			$association = Inflector::pluralize($association);
		}

		if ($type === 'belongsTo' || $type === 'hasOne') {
			if (isset($merge[$association])) {
				$data[$association] = $merge[$association][0];
			} else {
				if (count($merge[0][$association]) > 1) {
					foreach ($merge[0] as $assoc => $data2) {
						if ($assoc != $association) {
							$merge[0][$association][$assoc] = $data2;
						}
					}
				}
				if (!isset($data[$association])) {
					$data[$association] = $merge[0][$association];
				} else {
					if (is_array($merge[0][$association])) {
						$data[$association] = array_merge($merge[0][$association], $data[$association]);
					}
				}
			}
		} else {
			if ($merge[0][$association] === false) {
				if (!isset($data[$association])) {
					$data[$association] = array();
				}
			} else {
				foreach ($merge as $i => $row) {
					if (count($row) === 1) {
						$data[$association][] = $row[$association];
					} else {
						$tmp = array_merge($row[$association], $row);
						unset ($tmp[$association]);
						$data[$association][] = $tmp;
					}
				}
			}
		}
	}

/**
 * Private helper method to remove query metadata in given data array.
 *
 * @param array $data
 * @return void
 */
	protected function _scrubQueryData(&$data) {
		if (!isset($data['type'])) {
			$data['type'] = 'default';
		}
		if (!isset($data['conditions'])) {
			$data['conditions'] = array();
		}
		if (!isset($data['targetDn'])) {
			$data['targetDn'] = null;
		}
		if (!isset($data['fields']) && empty($data['fields'])) {
			$data['fields'] = array();
		}
		if (!isset($data['order']) && empty($data['order'])) {
			$data['order'] = array();
		}
		if (!isset($data['limit'])) {
			$data['limit'] = null;
		}
	}

	protected function _getObjectClasses() {
		$cache = null;
		if ($this->cacheSources !== false) {
			if (isset($this->_descriptions['ldap_objectclasses'])) {
				$cache = $this->_descriptions['ldap_objectclasses'];
			} else {
				$cache = $this->_cacheDescription('objectclasses');
			}
		}

		if ($cache != null) {
			return $cache;
		}

		// If we get this far, then we haven't cached the attribute types, yet!
		$ldapschema = $this->_getLDAPschema();
		$objectclasses = $ldapschema['objectclasses'];

		// Cache away
		$this->_cacheDescription( 'objectclasses', $objectclasses);

		return $objectclasses;
	}

	public function boolean() {
		return null;
	}

/**
 * Returns the count of records
 *
 * @param model $model
 * @param string $func Lowercase name of SQL function, i.e. 'count' or 'max'
 * @param array $params Function parameters (any values must be quoted manually)
 * @return integer Entry count
 */
	public function calculate(Model $model, $func, $params = array()) {
		$params = (array)$params;

		switch (strtolower($func)) {
			case 'count':
				if (empty($params) && $model->id) {
					// quick search to make sure it exsits
					$queryData['targetDn'] = $model->id;
					$queryData['conditions'] = 'objectClass=*';
					$queryData['scope'] = 'base';
					$query = $this->read($model, $queryData);
				}
				return $this->count;

			case 'max':
			case 'min':
				break;
		}
		return 0;
	}

/**
 * LdapSource::describe()
 *
 * @param mixed $model
 * @param string $field
 * @return array
 */
	public function describe($model, $field = null) {
		$schemas = $this->_getLDAPschema();
		$attrs = $schemas['attributetypes'];
		ksort($attrs);
		if (!empty($field)) {
			return $attrs[strtolower($field)];
		}
		return $attrs;
	}

/**
 * LdapSource::inArrayInsensitive()
 *
 * @param string $needle
 * @param array $haystack
 * @return boolean
 */
	public function inArrayInsensitive($needle, $haystack) {
		$found = false;
		foreach ($haystack as $attr => $value) {
			if (strtolower($attr) === strtolower($needle)) {
				$found = true;
			} elseif (strtolower($value) === strtolower($needle)) {
				$found = true;
			}
		}
		return $found;
	}

/**
 * LdapSource::defaultNSAttributes()
 *
 * @return array
 */
	public function defaultNSAttributes() {
		$fields = '* ' . $this->OperationalAttributes;
		return explode(' ', $fields);
	}

/**
 * debugLDAPConnection debugs the current connection to check the settings
 *
 */
	public function debugLDAPConnection() {
		$opts = array('LDAP_OPT_DEREF', 'LDAP_OPT_SIZELIMIT', 'LDAP_OPT_TIMELIMIT', 'LDAP_OPT_NETWORK_TIMEOUT',
			'LDAP_OPT_PROTOCOL_VERSION', 'LDAP_OPT_ERROR_NUMBER', 'LDAP_OPT_REFERRALS', 'LDAP_OPT_RESTART',
			'LDAP_OPT_HOST_NAME', 'LDAP_OPT_ERROR_STRING', 'LDAP_OPT_MATCHED_DN', 'LDAP_OPT_SERVER_CONTROLS',
			'LDAP_OPT_CLIENT_CONTROLS');
		foreach ($opts as $opt) {
			$ve = '';
			ldap_get_option($this->database, constant($opt), $ve);
			$this->log("Option={$opt}, Value=" . print_r($ve, 1), 'debug');
		}
	}

/**
 * If you want to pull everything from a netscape stype ldap server
 * iPlanet, Redhat-DS, Project-389 etc you need to ask for specific
 * attributes like so.  Other wise the attributes listed below wont
 * show up
 *
 * @return void
 */
	public function setNetscapeEnv() {
		$this->OperationalAttributes = 'accountUnlockTime aci copiedFrom copyingFrom createTimestamp creatorsName dncomp entrydn entryid hasSubordinates ldapSchemas ldapSyntaxes modifiersName modifyTimestamp nsAccountLock nsAIMStatusGraphic nsAIMStatusText nsBackendSuffix nscpEntryDN nsds5ReplConflict nsICQStatusGraphic nsICQStatusText nsIdleTimeout nsLookThroughLimit nsRole nsRoleDN nsSchemaCSN nsSizeLimit nsTimeLimit nsUniqueId nsYIMStatusGraphic nsYIMStatusText numSubordinates parentid passwordAllowChangeTime passwordExpirationTime passwordExpWarned passwordGraceUserTime passwordHistory passwordRetryCount pwdExpirationWarned pwdGraceUserTime pwdHistory pwdpolicysubentry retryCountResetTime subschemaSubentry';
		$this->SchemaFilter = '(objectClass=subschema)';
		$this->SchemaAttributes = 'objectClasses attributeTypes ldapSyntaxes matchingRules matchingRuleUse createTimestamp modifyTimestamp';
	}

	public function setActiveDirectoryEnv() {
		//Need to disable referals for AD
		ldap_set_option($this->database, LDAP_OPT_REFERRALS, 0);
		$this->OperationalAttributes = ' + ';
		$this->SchemaFilter = '(objectClass=subschema)';
		$this->SchemaAttributes = 'objectClasses attributeTypes ldapSyntaxes matchingRules matchingRuleUse createTimestamp modifyTimestamp subschemaSubentry';
	}

	public function setOpenLDAPEnv() {
		$this->OperationalAttributes = ' + ';
	}

	public function setSchemaPath() {
		$checkDN = ldap_read($this->database, '', 'objectClass=*', array('subschemaSubentry'));
		$schemaEntry = ldap_get_entries($this->database, $checkDN);
		$this->SchemaDN = $schemaEntry[0]['subschemasubentry'][0];
	}

}
