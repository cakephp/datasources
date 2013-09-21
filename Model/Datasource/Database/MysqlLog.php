<?php
/**
 * MySQL Logging layer for DBO.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2013, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2013, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP Datasources v 0.2
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Mysql', 'Model/Datasource/Database');

/**
 * DBO implementation for the MySQL DBMS with logging enabled.
 *
 * A DboSource adapter for MySQL that enables developers to log queries
 *
 */
class MysqlLog extends Mysql {

/**
 * Datasource Description
 *
 * @var string
 */
	public $description = 'MySQL Logging DBO Driver';

/**
 * Log given SQL query. If config value `logQueries` is set to true the query
 * will also be logged using CakeLog to 'sql' scope.
 *
 * @param string $sql SQL statement
 * @param array $params Values binded to the query (prepared statements)
 * @return void
 */
	public function logQuery($sql, $params = array()) {
		parent::logQuery($sql, $params);
		if (Configure::read('logQueries')) {
			$this->log($this->_queriesLog[count($this->_queriesLog) - 1], 'sql');
		}
	}
}
