<?php
/**
 * Array Datasource
 *
 * PHP versions 4 and 5
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
 * @since         CakePHP Datasources v 0.1
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'Set');

/**
 * ArraySource
 *
 * Datasource for Array
 */
class ArraySource extends Datasource {

/**
 * Version for this Data Source.
 *
 * @var string
 * @access public
 */
	var $version = '0.1';

/**
 * Description string for this Data Source.
 *
 * @var string
 * @access public
 */
	var $description = 'Array Datasource';

/**
 * Default Constructor
 *
 * @param array $config options
 * @access public
 */
	function __construct($config = array()) {
		parent::__construct($config);
	}

/**
 * Returns a Model description (metadata) or null if none found.
 *
 * @param Model $model
 * @return null It's not supported
 * @access public
 */
	function describe(&$model) {
		return null;
	}

/**
 * List sources
 *
 * @param mixed $data
 * @return boolean Always false. It's not supported
 * @access public
 */
	function listSources($data = null) {
		return false;
	}

/**
 * Used to read records from the Datasource. The "R" in CRUD
 *
 * @param Model $model The model being read.
 * @param array $queryData An array of query data used to find the data you want
 * @return mixed
 * @access public
 */
	function read(&$model, $queryData = array()) {
		if (!isset($model->records) || !is_array($model->records) || empty($model->records)) {
			return array($model->alias => array());
		}
		$data = array();
		$i = 0;
		$limit = false;
		if (is_integer($queryData['limit']) && $queryData['limit'] > 0) {
			$limit = $queryData['page'] * $queryData['limit'];
		}
		foreach ($model->records as $pos => $record) {
			// Tests whether the record will be chosen
			if (!empty($queryData['conditions'])) {
				// continue;
			}
			// Select the fields
			if (empty($queryData['fields'])) {
				$data[$i] = $record;
			} else {
				foreach ($queryData['fields'] as $field) {
					if (strpos($field, '.') !== false) {
						list($alias, $field) = explode('.', $field, 2);
						if ($alias !== $model->alias) {
							continue;
						}
					}
					if (isset($record[$field])) {
						$data[$i][$field] = $record[$field];
					}
				}
			}
			$i++;
			// Test limit
			if ($limit !== false && $i == $limit) {
				break;
			}
		}
		if ($limit !== false) {
			$data = array_slice($data, ($queryData['page'] - 1) * $queryData['limit'], $queryData['limit'], false);
		}
		return array($model->alias => $data);
	}
}
?>
