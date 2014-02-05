<?php
/**
 * ODBC layer for DBO
 * Helpful for Linux connection to MS SQL Server via FreeTDS
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Model.Datasource.Database
 * @since         CakePHP(tm) v 0.10.5.1790
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 * Example database.php:
 *
class DATABASE_CONFIG {
	
	public $default = array(
		'datasource' => 'Database/Odbc',
		'host' => '127.0.0.1',
		'port' => 1433,
		'driver' => 'FreeTDS',
		'login' => 'mssqluser',
		'password' => 'mssqlpass',
		'database' => 'mssqldb',
		'version' => 'Microsoft SQL Server 2005 - 9.00.5000.00 (X64)'
}
 */
 
App::uses('Sqlserver', 'Model/Datasource/Database');
 
/**
 * Dbo driver for ODBC
 *
 * A Dbo driver for ODBC primarily for MSSQL via FreeTDS.
 * For setup: https://secure.kitserve.org.uk/content/accessing-microsoft-sql-server-php-ubuntu-using-pdo-odbc-and-freetds
 *
 * @package       Cake.Model.Datasource.Database
 */
class OdbcShrimpFreeTds extends Sqlserver {
 
/**
 * Driver description
 *
 * @var string
 */
	public $description = "ODBC DBO Driver";
	
/**
 * Base configuration settings for MS SQL driver
 *
 * @var array
 */
	protected $_baseConfig = array(
		'persistent' => true,
		'host' => 'localhost\SQLEXPRESS',
		'port' => '1433',
		'driver' => 'FreeTDS',
		'login' => '',
		'password' => '',
		'database' => 'cake',
		'schema' => '',
		'version' => ''
	);
	
/**
 * Connects to the database using options in the given configuration array.
 *
 * @return boolean True if the database could be connected, else false
 * @throws MissingConnectionException
 */
	public function connect() {
		$config = $this->config;
		$this->connected = false;
		try {
			$flags = array(
				PDO::ATTR_PERSISTENT => $config['persistent'],
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
			);
			if (!empty($config['encoding'])) {
				$flags[PDO::SQLSRV_ATTR_ENCODING] = $config['encoding'];
			}

			$this->_connection = new PDO(
				"odbc:spotinstance",
				$config['login'],
				$config['password'],
				$flags
			);
			$this->connected = true;
		} catch (PDOException $e) {
			throw new MissingConnectionException(array(
				'class' => get_class($this),
				'message' => $e->getMessage()
			));
		}
 
		return $this->connected;
	}
	
/**
 * Check that PDO SQL Server is installed/loaded
 *
 * @return boolean
 */
	public function enabled() {
		return in_array('odbc', PDO::getAvailableDrivers());
	}
	
/**
 * Executes given SQL statement.
 *
 * @param string $sql SQL statement
 * @param array $params list of params to be bound to query
 * @param array $prepareOptions Options to be used in the prepare statement
 * @return mixed PDOStatement if query executes with no problem, true as the result of a successful, false on error
 * query returning no rows, such as a CREATE statement, false otherwise
 * @throws PDOException
 */
	protected function _execute($sql, $params = array(), $prepareOptions = array()) {
		$sql = trim($sql);
		if (preg_match('/^(?:CREATE|ALTER|DROP)\s+(?:TABLE|INDEX)/i', $sql)) {
			$statements = array_filter(explode(';', $sql));
			if (count($statements) > 1) {
				$result = array_map(array($this, '_execute'), $statements);
				return array_search(false, $result) === false;
			}
		}
 
		try {
			$query = $this->_connection->prepare($sql);
			$query->setFetchMode(PDO::FETCH_LAZY);
			if (!$query->execute()) {
				$this->_results = $query;
				$query->closeCursor();
				return false;
			}
			if (!$query->columnCount()) {
				$query->closeCursor();
				if (!$query->rowCount()) {
					return true;
				}
			}
			return $query;
		} catch (PDOException $e) {
			if (isset($query->queryString)) {
				$e->queryString = $query->queryString;
			} else {
				$e->queryString = $sql;
			}
			throw $e;
		}
	}
	
/**
 * Gets the version string of the database server
 *
 * @return string The database version
 */
	public function getVersion() {
		return $this->config['version'];
	}
	
/**
 * Builds a map of the columns contained in a result
 *
 * @param PDOStatement $results
 * @return void
 */
	public function resultSet(&$results) {
		$this->map = array();
		$clean = substr($results->queryString, strpos($results->queryString, " ") + 1);
		$clean = substr($clean, 0, strpos($clean, ' FROM') - strlen($clean));
        //Remove the "top 1" from the beginning of the line.
        $clean = preg_replace("/^\\s?top\\s+[0-9]+\\s+/i", "", $clean);
        $parts = preg_split("/,\\s+/", $clean);
        $customQuery = false;
        if(strpos($clean, $this->virtualFieldSeparator) === false && trim($clean) != ''){
            $customQuery = true;
        }

		foreach ($parts as $key => $value) {
            //If this query ISN'T a typical cake query
            if($customQuery){
                $asAlias = preg_split("/\\s+[Aa][Ss]\\s+/", $value);
                if(count($asAlias) < 2) break;
                $newValue = trim(preg_replace("/'|\\[|\\]/", "", $asAlias[1]));
                $this->map[$key] = array(0, $newValue, "VAR_STRING");
            } else {
                $matches = array();
                preg_match("/\\[(.*?)\\]\\.\\[.*?\\](.*)/", $value, $matches);
                if(count($matches) != 3) break;
                $table = $matches[1];
                $name = $matches[2];
                $table = trim(preg_replace("/'|\\[|\\]/", "", $table));
                if (!$table && strpos($name, $this->virtualFieldSeparator) !== false) {
                    $name = substr(strrchr($name, " "), 1);
                }
                $nameParts = preg_split("/\\s+[Aa][Ss]\\s+/", $name);
                $name = trim(preg_replace("/'|\\[|\\]/", "", array_pop($nameParts)));
                preg_match('/.*?__(.*)/', $name, $matches);
                $name = $matches[1];
                $this->map[$key] = array($table, $name, "VAR_STRING");
            }
		}
        return;
	}
	
/**
 * Returns a quoted and escaped string of $data for use in an SQL statement.
 *
 * @param string $data String to be prepared for use in an SQL statement
 * @param string $column The column into which this data will be inserted
 * @return string Quoted and escaped data
 */
	public function value($data, $column = null) {
		if (is_array($data) && !empty($data)) {
			return array_map(
				array(&$this, 'value'),
				$data, array_fill(0, count($data), $column)
			);
		} elseif (is_object($data) && isset($data->type, $data->value)) {
			if ($data->type == 'identifier') {
				return $this->name($data->value);
			} elseif ($data->type == 'expression') {
				return $data->value;
			}
		} elseif (in_array($data, array('{$__cakeID__$}', '{$__cakeForeignKey__$}'), true)) {
			return $data;
		}
 
		if ($data === null || (is_array($data) && empty($data))) {
			return 'NULL';
		}
 
		if (empty($column)) {
			$column = $this->introspectType($data);
		}
 
		switch ($column) {
			case 'binary':
				return $this->_connection->quote($data, PDO::PARAM_LOB);
			case 'boolean':
				return $this->_connection->quote($this->boolean($data, true), PDO::PARAM_BOOL);
			case 'string':
			case 'text':
            case 'date':
            case 'datetime':
				//return $this->_connection->quote($data, PDO::PARAM_STR);
				return "'$data'";
			default:
				if ($data === '') {
					return 'NULL';
				}
				if (is_float($data)) {
					return str_replace(',', '.', strval($data));
				}
				if ((is_int($data) || $data === '0') || (
					is_numeric($data) && strpos($data, ',') === false &&
					$data[0] != '0' && strpos($data, 'e') === false)
				) {
					return $data;
				}
				return $this->_connection->quote($data);
		}
	}
	
/**
 * Fetches the next row from the current result set.
 * Eats the magic ROW_COUNTER variable.
 *
 * @return mixed
 */
	public function fetchResult() {
		if ($row = $this->_result->fetch(PDO::FETCH_NUM)) { // ### HERE IS WHERE IT RESETS ###
			$resultRow = array();
			foreach ($this->map as $col => $meta) {
				list($table, $column, $type) = $meta;
				if ($table === 0 && $column === self::ROW_COUNTER) {
					continue;
				}
				$resultRow[$table][$column] = $row[$col];
				if ($type === 'boolean' && !is_null($row[$col])) {
					$resultRow[$table][$column] = $this->boolean($resultRow[$table][$column]);
				}
			}
			return $resultRow;
		}
		$this->_result->closeCursor();
		return false;
	}

    /**
     * Generates the fields list of an SQL query.
     *
     * @param Model $model
     * @param string $alias Alias table name
     * @param array $fields
     * @param boolean $quote
     * @return array
     */
    public function fields(Model $model, $alias = null, $fields = array(), $quote = true) {
        if (empty($alias)) {
            $alias = $model->alias;
        }
        $fields = DboSource::fields($model, $alias, $fields, false);
        $count = count($fields);

        if ($count >= 1 && strpos($fields[0], 'COUNT(*)') === false) {
            $result = array();
            for ($i = 0; $i < $count; $i++) {
                $prepend = '';

                if (strpos($fields[$i], 'DISTINCT') !== false) {
                    $prepend = 'DISTINCT ';
                    $fields[$i] = trim(str_replace('DISTINCT', '', $fields[$i]));
                }

                if (!preg_match('/\s+AS\s+/i', $fields[$i])) {
                    if (substr($fields[$i], -1) === '*') {
                        if (strpos($fields[$i], '.') !== false && $fields[$i] != $alias . '.*') {
                            $build = explode('.', $fields[$i]);
                            $AssociatedModel = $model->{$build[0]};
                        } else {
                            $AssociatedModel = $model;
                        }

                        $_fields = $this->fields($AssociatedModel, $AssociatedModel->alias, array_keys($AssociatedModel->schema()));
                        $result = array_merge($result, $_fields);
                        continue;
                    }

                    if (strpos($fields[$i], '.') === false) {
                        $this->_fieldMappings[$alias . '__' . $fields[$i]] = $alias . '.' . $fields[$i];
                        $fieldName = $this->name($alias . '.' . $fields[$i]);
                        $fieldAlias = $this->name($alias . '__' . $fields[$i]);
                    } else {
                        $build = explode('.', $fields[$i]);
                        $build[0] = trim($build[0], '[]');
                        $build[1] = trim($build[1], '[]');
                        $name = $build[0] . '.' . $build[1];
                        $alias = $build[0] . '__' . $build[1];

                        $this->_fieldMappings[$alias] = $name;
                        $fieldName = $this->name($name);
                        $fieldAlias = $this->name($alias);
                    }
                    if ($model->getColumnType($fields[$i]) === 'datetime') {
                        $fieldName = "CONVERT(VARCHAR(20),{$fieldName},20)";
                    }
                    $fields[$i] = "{$fieldName} AS {$fieldAlias}";
                }
                $result[] = $prepend . $fields[$i];
            }
            return $result;
        }
        return $fields;
    }


    /**
     * Returns the ID generated from the previous INSERT operation.
     *
     * @param mixed $source
     * @return mixed
     */
    public function lastInsertId($source = null) {
        /**
         * @var $resultSet PDOStatement
         */
        $resultSet = $this->_connection->query("SELECT SCOPE_IDENTITY()");
        $lastId = $resultSet->fetchColumn();
        return $lastId;
    }
}