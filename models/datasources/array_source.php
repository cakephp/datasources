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
 * @return array Show only id
 * @access public
 */
	function describe(&$model) {
		return array('id' => array());
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
				$queryData['conditions'] = (array)$queryData['conditions'];
				foreach ($queryData['conditions'] as $field => $value) {
					if (is_string($field)) {
						if (strpos($field, ' ') === false) {
							$value = $field . ' = ' . $value;
						} else {
							// Can have LIKE, NOT, IN, ...
							$value = $field . ' ' . $value;
						}
					}
					if (preg_match('/^(\w+\.?\w+)\s+(=|!=|LIKE|IN)\s+(.*)$/', $value, $matches)) {
						$field = $matches[1];
						$value = $matches[3];
						if (strpos($field, '.') !== false) {
							list($alias, $field) = explode('.', $field, 2);
							if ($alias != $model->alias) {
								continue;
							}
						}
						switch ($matches[2]) {
							case '=':
								if (!isset($record[$field]) || $record[$field] != $value) {
									continue(3);
								}
								break;
							case '!=':
								if (isset($record[$field]) && $record[$field] == $value) {
									continue(3);
								}
								break;
							case 'LIKE':
								if (!isset($record[$field]) || strpos($record[$field], $value) === false) {
									continue(3);
								}
								break;
							case 'IN':
								$items = array();
								if (preg_match('/^\(\w+(,\s*\w+)*\)$/', $value)) {
									$items = explode(',', trim($value, '()'));
									$items = array_map('trim', $items);
								}
								if (!isset($record[$field]) || !in_array($record[$field], (array)$items)) {
									continue(3);
								}
								break;
						}
					}
				}
			}
			$data[$i] = $record;
			$i++;
			// Test limit
			if ($limit !== false && $i == $limit) {
				break;
			}
		}
		if ($limit !== false) {
			$data = array_slice($data, ($queryData['page'] - 1) * $queryData['limit'], $queryData['limit'], false);
		}
		// Order
		if (!empty($queryData['order'])) {
			if (is_string($queryData['order'][0])) {
				$field = $queryData['order'][0];
				$alias = $model->alias;
				if (strpos($field, '.') !== false) {
					list($alias, $field) = explode('.', $field, 2);
				}
				if ($alias === $model->alias) {
					$sort = 'ASC';
					if (strpos($field, ' ') !== false) {
						list($field, $sort) = explode(' ', $field, 2);
					}
					$data = Set::sort($data, '{n}.' . $field, $sort);
				}
			}
		}
		// Filter fields
		if (!empty($queryData['fields'])) {
			if ($queryData['fields'] === 'COUNT') {
				return array(array(array('count' => count($data))));
			}
			$listOfFields = array();
			foreach ($queryData['fields'] as $field) {
				if (strpos($field, '.') !== false) {
					list($alias, $field) = explode('.', $field, 2);
					if ($alias !== $model->alias) {
						continue;
					}
				}
				$listOfFields[] = $field;
			}
			foreach ($data as $id => $record) {
				foreach ($record as $field => $value) {
					if (!in_array($field, $listOfFields)) {
						unset($data[$id][$field]);
					}
				}
			}
		}
		if ($model->findQueryType === 'first') {
			return $data;
		} elseif ($model->findQueryType === 'list') {
			$newData = array();
			foreach ($data as $item) {
				$newData[] = array($model->alias => $item);
			}
			return $newData;
		}
		return array($model->alias => $data);
	}

/**
 * Returns an calculation
 *
 * @param model $model
 * @param string $type Lowercase name type, i.e. 'count' or 'max'
 * @param array $params Function parameters (any values must be quoted manually)
 * @return string Calculation method
 * @access public
 */
	function calculate(&$model, $type, $params = array()) {
		return 'COUNT';
	}

	function queryAssociation(&$model, &$linkModel, $type, $association, $assocData, &$queryData, $external = false, &$resultSet, $recursive, $stack) {
		foreach ($resultSet as $id => $result) {
			$resultSet[$id][$association] = $model->{$association}->find('first', array(
				'conditions' => array_merge((array)$assocData['conditions'], array($model->{$association}->primaryKey => $result[$model->alias][$assocData['foreignKey']])),
				'fields' => $assocData['fields'],
				'order' => $assocData['order']
			));
		}
	}
}
?>
