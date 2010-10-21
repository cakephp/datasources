<?php
/**
 * MySQL Logging layer for DBO.
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
 * @subpackage    datasources.models.datasources.dbo
 * @since         CakePHP Datasources v 0.2
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::import('Datasource', 'DboSource');
App::import('Datasource', 'DboMysql');

/**
 * DBO implementation for the MySQL DBMS with logging enabled.
 *
 * A DboSource adapter for MySQL that enables developers to log queries
 *
 * @package datasources
 * @subpackage datasources.models.datasources.dbo
 */
class DboMysqlLog extends DboMysql {

/**
 * Datasource Description
 *
 * @var string
 */
	public $description = 'MySQL Logging DBO Driver';

/**
 * Log given SQL query.
 *
 * @param string $sql SQL statement
 */
	public function logQuery($sql) {
		$return = parent::logQuery($sql);
		if (Configure::read('logQueries')) {
			$this->log("sql[{$this->_queriesCnt}]:{$sql}", 'sql');
		}
		return $return;
	}
}
