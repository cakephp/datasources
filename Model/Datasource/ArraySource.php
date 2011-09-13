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
 * @since         CakePHP Datasources v 0.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('Set', 'Utility');
App::uses('ConnectionManager', 'Model');
/**
 * ArraySource
 *
 * Datasource by Array
 */
class ArraySource extends DataSource {

/**
 * Description string for this Data Source.
 *
 * @var string
 */
	public $description = 'Array Datasource';

/**
 * List of requests ("queries")
 *
 * @var array
 */
	protected $_requestsLog = array();

/**
 * Base Config
 *
 * @var array
 */
	public $_baseConfig = array(
		'driver' => '' // Just to avoid DebugKit warning
	);

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
 * Returns a Model description (metadata) or null if none found.
 *
 * @param Model $model
 * @return array Show only id
 */
	public function describe(&$model) {
		return array('id' => array());
	}

/**
 * List sources
 *
 * @param mixed $data
 * @return boolean Always false. It's not supported
 */
	public function listSources($data = null) {
		return false;
	}

/**
 * Used to read records from the Datasource. The "R" in CRUD
 *
 * @param Model $model The model being read.
 * @param array $queryData An array of query data used to find the data you want
 * @return mixed
 */
	public function read(&$model, $queryData = array()) {
		if (!isset($model->records) || !is_array($model->records) || empty($model->records)) {
			$this->_requestsLog[] = array(
				'query' => 'Model ' . $model->alias,
				'error' => __('No records found in model.', true),
				'affected' => 0,
				'numRows' => 0,
				'took' => 0
			);
			return array($model->alias => array());
		}
		$startTime = microtime(true);
		$data = array();
		$i = 0;
		$limit = false;
		if (!isset($queryData['recursive'])) {
			$queryData['recursive'] = $model->recursive;
		}
		if (is_integer($queryData['limit']) && $queryData['limit'] > 0) {
			$limit = $queryData['page'] * $queryData['limit'];
		}
		foreach ($model->records as $pos => $record) {
			// Tests whether the record will be chosen
			if (!empty($queryData['conditions'])) {
				$queryData['conditions'] = (array)$queryData['conditions'];
				if (!$this->conditionsFilter($model, $record, $queryData['conditions'])) {
					continue;
				}
			}
			$data[$i][$model->alias] = $record;
			$i++;
			// Test limit
			if ($limit !== false && $i == $limit && empty($queryData['order'])) {
				break;
			}
		}
		if ($queryData['fields'] === 'COUNT') {
			$this->_registerLog($model, $queryData, microtime(true) - $startTime, 1);
			if ($limit !== false) {
				$data = array_slice($data, ($queryData['page'] - 1) * $queryData['limit'], $queryData['limit'], false);
			}
			return array(array(array('count' => count($data))));
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
					$data = Set::sort($data, '{n}.' . $model->alias . '.' . $field, $sort);
				}
			}
		}
		// Limit
		if ($limit !== false) {
			$data = array_slice($data, ($queryData['page'] - 1) * $queryData['limit'], $queryData['limit'], false);
		}
		// Filter fields
		if (!empty($queryData['fields'])) {
			$listOfFields = array();
			foreach ((array)$queryData['fields'] as $field) {
				if (strpos($field, '.') !== false) {
					list($alias, $field) = explode('.', $field, 2);
					if ($alias !== $model->alias) {
						continue;
					}
				}
				$listOfFields[] = $field;
			}
			foreach ($data as $id => $record) {
				foreach ($record[$model->alias] as $field => $value) {
					if (!in_array($field, $listOfFields)) {
						unset($data[$id][$model->alias][$field]);
					}
				}
			}
		}
		$this->_registerLog($model, $queryData, microtime(true) - $startTime, count($data));
		$_associations = $model->__associations;
		if ($queryData['recursive'] > -1) {
			foreach ($_associations as $type) {
				foreach ($model->{$type} as $assoc => $assocData) {
					$linkModel =& $model->{$assoc};

					if ($model->useDbConfig == $linkModel->useDbConfig) {
						$db =& $this;
					} else {
						$db =& ConnectionManager::getDataSource($linkModel->useDbConfig);
					}

					if (isset($db)) {
						if (method_exists($db, 'queryAssociation')) {
							$stack = array($assoc);
							$db->queryAssociation($model, $linkModel, $type, $assoc, $assocData, $queryData, true, $data, $queryData['recursive'] - 1, $stack);
						}
						unset($db);
					}
				}
			}
		}
		if ($model->findQueryType === 'first') {
			if (!isset($data[0])) {
				$data = array();
			} else {
				$data = array($data[0]);
			}
		}
		return $data;
	}

/**
 * Conditions Filter
 *
 * @param Model $model
 * @param string $record
 * @param array $conditions
 * @param boolean $or
 * @return void
 */
	public function conditionsFilter(&$model, $record, $conditions, $or = false) {
		foreach ($conditions as $field => $value) {
			$return = null;
			if ($value === '') {
				continue;
			}
			if (is_array($value) && in_array(strtoupper($field), array('AND', 'NOT', 'OR'))) {
				switch (strtoupper($field)) {
					case 'AND':
						$return = $this->conditionsFilter($model, $record, $value);
						break;
					case 'NOT':
						$return = !$this->conditionsFilter($model, $record, $value);
						break;
					case 'OR':
						$return = $this->conditionsFilter($model, $record, $value, true);
						break;
				}
			} else {
				if (is_array($value)) {
					$type = 'IN';
				} elseif (preg_match('/^(\w+\.?\w+)\s+(=|!=|LIKE|IN)$/i', $field, $matches)) {
					$field = $matches[1];
					$type = strtoupper($matches[2]);
				} elseif (preg_match('/^(\w+\.?\w+)\s+(=|!=|LIKE|IN)\s+(.*)$/i', $value, $matches)) {
					$field = $matches[1];
					$type = strtoupper($matches[2]);
					$value = $matches[3];
				} else {
					$type = '=';
				}
				if (strpos($field, '.') !== false) {
					list($alias, $field) = explode('.', $field, 2);
					if ($alias != $model->alias) {
						continue;
					}
				}
				switch ($type) {
					case '=':
						$return = (isset($record[$field]) && $record[$field] == $value);
						break;
					case '!=':
						$return = (!isset($record[$field]) || $record[$field] != $value);
						break;
					case 'LIKE':
						$value = preg_replace(array('#(^|[^\\\\])_#', '#(^|[^\\\\])%#'), array('$1.', '$1.*'), $value);
						$return = (isset($record[$field]) && preg_match('#^' . $value . '$#i', $record[$field]));
						break;
					case 'IN':
						$items = array();
						if (is_array($value)) {
							$items = $value;
						} elseif (preg_match('/^\(\w+(,\s*\w+)*\)$/', $value)) {
							$items = explode(',', trim($value, '()'));
							$items = array_map('trim', $items);
						}
						$return = (isset($record[$field]) && in_array($record[$field], (array)$items));
						break;
				}
			}
			if ($return === $or) {
				return $or;
			}
		}
		return !$or;
	}

/**
 * Returns an calculation
 *
 * @param model $model
 * @param string $type Lowercase name type, i.e. 'count' or 'max'
 * @param array $params Function parameters (any values must be quoted manually)
 * @return string Calculation method
 */
	public function calculate(&$model, $type, $params = array()) {
		return 'COUNT';
	}

/**
 * Queries associations. Used to fetch results on recursive models.
 *
 * @param Model $model Primary Model object
 * @param Model $linkModel Linked model that
 * @param string $type Association type, one of the model association types ie. hasMany
 * @param unknown_type $association
 * @param unknown_type $assocData
 * @param array $queryData
 * @param boolean $external Whether or not the association query is on an external datasource.
 * @param array $resultSet Existing results
 * @param integer $recursive Number of levels of association
 * @param array $stack
 */
	public function queryAssociation(&$model, &$linkModel, $type, $association, $assocData, &$queryData, $external = false, &$resultSet, $recursive, $stack) {
		$assocData = array_merge(array('conditions' => null, 'fields' => null, 'order' => null), $assocData);
		if (isset($queryData['conditions'])) {
			$assocData['conditions'] = array_merge((array)$queryData['conditions'], (array)$assocData['conditions']);
		}
		if (isset($queryData['fields'])) {
			$assocData['fields'] = array_merge((array)$queryData['fields'], (array)$assocData['fields']);
		}
		foreach ($resultSet as $id => $result) {
			if (!array_key_exists($model->alias, $result)) {
				continue;
			}
			if ($type === 'belongsTo' && array_key_exists($assocData['foreignKey'], $result[$model->alias])) {
				$find = $model->{$association}->find('first', array(
					'conditions' => array_merge((array)$assocData['conditions'], array($model->{$association}->primaryKey => $result[$model->alias][$assocData['foreignKey']])),
					'fields' => $assocData['fields'],
					'order' => $assocData['order'],
					'recursive' => $recursive
				));
			} elseif (in_array($type, array('hasOne', 'hasMany')) && array_key_exists($model->primaryKey, $result[$model->alias])) {
				if ($type === 'hasOne') {
					$find = $model->{$association}->find('first', array(
						'conditions' => array_merge((array)$assocData['conditions'], array($association . '.' . $assocData['foreignKey'] => $result[$model->alias][$model->primaryKey])),
						'fields' => $assocData['fields'],
						'order' => $assocData['order'],
						'recursive' => $recursive
					));
				} else {
					$find = $model->{$association}->find('all', array(
						'conditions' => array_merge((array)$assocData['conditions'], array($association . '.' . $assocData['foreignKey'] => $result[$model->alias][$model->primaryKey])),
						'fields' => $assocData['fields'],
						'order' => $assocData['order'],
						'recursive' => $recursive
					));
					$find = array(
						$association => Set::extract('{n}.' . $association, $find)
					);
				}
			} elseif ($type === 'hasAndBelongsToMany' && array_key_exists($model->primaryKey, $result[$model->alias])) {
				$hABTMModel = ClassRegistry::init($assocData['with']);
				$ids = $hABTMModel->find('all', array(
					'fields' => array(
						$assocData['with'] . '.' . $assocData['associationForeignKey']
					),
					'conditions' => array(
						$assocData['with'] . '.' . $assocData['foreignKey'] => $result[$model->alias][$model->primaryKey]
					)
				));
				$ids = Set::extract('{n}.' . $assocData['with'] . '.' . $assocData['associationForeignKey'], $ids);
				$find = $model->{$association}->find('all', array(
					'conditions' => array_merge((array)$assocData['conditions'], array($association . '.' . $linkModel->primaryKey => $ids)),
					'fields' => $assocData['fields'],
					'order' => $assocData['order'],
					'recursive' => $recursive
				));
				$find = array(
					$association => Set::extract('{n}.' . $association, $find)
				);
			}
			if (empty($find)) {
				$find = array($association => array());
			}
			$resultSet[$id] = array_merge($find, $resultSet[$id]);
		}
	}

/**
 * Get the query log as an array.
 *
 * @param boolean $sorted Get the queries sorted by time taken, defaults to false.
 * @param boolean $clear Clear after return logs
 * @return array Array of queries run as an array
 */
	public function getLog($sorted = false, $clear = true) {
		if ($sorted) {
			$log = sortByKey($this->_requestsLog, 'took', 'desc', SORT_NUMERIC);
		} else {
			$log = $this->_requestsLog;
		}
		if ($clear) {
			$this->_requestsLog = array();
		}
		return array('log' => $log, 'count' => count($log), 'time' => array_sum(Set::extract('{n}.took', $log)));
	}

/**
 * Generate a log registry
 *
 * @param object $model
 * @param array $queryData
 * @param float $took
 * @param integer $numRows
 * @return void
 */
	public function _registerLog(&$model, &$queryData, $took, $numRows) {
		if (!Configure::read()) {
			return;
		}
		$this->_requestsLog[] = array(
			'query' => $this->_pseudoSelect($model, $queryData),
			'error' => '',
			'affected' => 0,
			'numRows' => $numRows,
			'took' => round($took, 3)
		);
	}

/**
 * Generate a pseudo select to log
 *
 * @param object $model Model
 * @param array $queryData Query data sended by find
 * @return string Pseudo query
 */
	protected function _pseudoSelect(&$model, &$queryData) {
		$out = '(symbolic) SELECT ';
		if (empty($queryData['fields'])) {
			$out .= '*';
		} elseif ($queryData['fields']) {
			$out .= 'COUNT(*)';
		} else {
			$out .= implode(', ', $queryData['fields']);
		}
		$out .= ' FROM ' . $model->alias;
		if (!empty($queryData['conditions'])) {
			$out .= ' WHERE';
			foreach ($queryData['conditions'] as $id => $condition) {
				if (empty($condition)) {
					continue;
				}
				if (is_array($condition)) {
					$condition = '(' . implode(', ', $condition) . ')';
					if (strpos($id, ' ') === false) {
						$id .= ' IN';
					}
				}
				if (is_string($id)) {
					if (strpos($id, ' ') !== false) {
						$condition = $id . ' ' . $condition;
					} else {
						$condition = $id . ' = ' . $condition;
					}
				}
				if (preg_match('/^(\w+\.)?\w+ /', $condition, $matches)) {
					if (!empty($matches[1]) && substr($matches[1], 0, -1) !== $model->alias) {
						continue;
					}
				}
				$out .= ' (' . $condition . ') &&';
			}
			$out = substr($out, 0, -3);
		}
		if (!empty($queryData['order'][0])) {
			$out .= ' ORDER BY ' . implode(', ', $queryData['order']);
		}
		if (!empty($queryData['limit'])) {
			$out .= ' LIMIT ' . (($queryData['page'] - 1) * $queryData['limit']) . ', ' . $queryData['limit'];
		}
		return $out;
	}
}
