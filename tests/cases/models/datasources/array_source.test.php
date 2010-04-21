<?php
/**
 * Array Datasource Test file
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
 * @subpackage    datasources.tests.cases.models.datasources
 * @since         CakePHP Datasources v 0.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::import('Datasource', 'ArrayDatasource.ArraySource');

// Add new db config
ConnectionManager::create('test_array', array('datasource' => 'ArrayDatasource.ArraySource'));

/**
 * Array Testing Model
 *
 */
class ArrayModel extends CakeTestModel {

/**
 * Name of Model
 *
 * @var string
 * @access public
 */
	var $name = 'ArrayModel';

/**
 * Database Configuration
 *
 * @var string
 * @access public
 */
	var $useDbConfig = 'test_array';

/**
 * Records
 *
 * @var array
 * @access public
 */
	var $records = array(
		array(
			'id' => 1,
			'name' => 'USA'
		),
		array(
			'id' => 2,
			'name' => 'Brazil'
		),
		array(
			'id' => 3,
			'name' => 'Germany'
		)
	);
}

/**
 * User Testing Model
 *
 */
class UserModel extends CakeTestModel {

/**
 * Name of model
 *
 * @var string
 * @access public
 */
	var $name = 'UserModel';

/**
 * Use DB Config
 *
 * @var string
 * @access public
 */
	var $useDbConfig = 'test';

/**
 * Use Table
 *
 * @var string
 * @access public
 */
	var $useTable = 'users';

/**
 * Belongs To
 *
 * @var array
 * @access public
 */
	var $belongsTo = array(
		'Born' => array(
			'className' => 'ArrayModel',
			'foreignKey' => 'born_id',
		)
	);
}

/**
 * Array Datasource Test
 *
 */
class ArraySourceTest extends CakeTestCase {

/**
 * Array Source Instance
 *
 * @var ArraySource
 * @access public
 */
	var $Model = null;

/**
 * Set up for Tests
 *
 * @return void
 * @access public
 */
	function setUp() {
		parent::setUp();
		$this->Model =& ClassRegistry::init('ArrayModel');
	}

/**
 * testFindAll
 *
 * @return void
 * @access public
 */
	function testFindAll() {
		$result = $this->Model->find('all');
		$expected = array(
			'ArrayModel' => array(
				array('id' => 1, 'name' => 'USA'),
				array('id' => 2, 'name' => 'Brazil'),
				array('id' => 3, 'name' => 'Germany')
			)
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testFindFields
 *
 * @return void
 * @access public
 */
	function testFindFields() {
		$expected = array(
			'ArrayModel' => array(
				array('id' => 1),
				array('id' => 2),
				array('id' => 3)
			)
		);
		$result = $this->Model->find('all', array('fields' => array('id')));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('fields' => array('ArrayModel.id')));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('fields' => array('ArrayModel.id', 'Unknow.id')));
		$this->assertEqual($result, $expected);
	}

/**
 * testFindLimit
 *
 * @return void
 * @access public
 */
	function testFindLimit() {
		$result = $this->Model->find('all', array('limit' => 2));
		$expected = array(
			'ArrayModel' => array(
				array('id' => 1, 'name' => 'USA'),
				array('id' => 2, 'name' => 'Brazil')
			)
		);
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('limit' => 2, 'page' => 2));
		$expected = array(
			'ArrayModel' => array(
				array('id' => 3, 'name' => 'Germany')
			)
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testFindOrder
 *
 * @return void
 * @access public
 */
	function testFindOrder() {
		$result = $this->Model->find('all', array('order' => 'ArrayModel.name'));
		$expected = array(
			'ArrayModel' => array(
				array('id' => 2, 'name' => 'Brazil'),
				array('id' => 3, 'name' => 'Germany'),
				array('id' => 1, 'name' => 'USA')
			)
		);
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('fields' => array('ArrayModel.id'), 'order' => 'ArrayModel.name'));
		$expected = array(
			'ArrayModel' => array(
				array('id' => 2),
				array('id' => 3),
				array('id' => 1)
			)
		);
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('fields' => array('ArrayModel.id'), 'order' => 'ArrayModel.name', 'limit' => 1, 'page' => 2));
		$expected = array(
			'ArrayModel' => array(
				array('id' => 3)
			)
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testFindConditions
 *
 * @return void
 * @access public
 */
	function testFindConditions() {
		$result = $this->Model->find('all', array('conditions' => array('ArrayModel.name' => 'USA')));
		$expected = array('ArrayModel' => array(array('id' => 1, 'name' => 'USA')));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('conditions' => array('ArrayModel.name = USA')));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('conditions' => array('ArrayModel.name != USA')));
		$expected = array('ArrayModel' => array(array('id' => 2, 'name' => 'Brazil'), array('id' => 3, 'name' => 'Germany')));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('conditions' => array('ArrayModel.name LIKE ra')));
		$expected = array('ArrayModel' => array(array('id' => 2, 'name' => 'Brazil')));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('conditions' => array('ArrayModel.name IN (USA, Germany)')));
		$expected = array('ArrayModel' => array(array('id' => 1, 'name' => 'USA'), array('id' => 3, 'name' => 'Germany')));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('conditions' => array('ArrayModel.name' => 'USA', 'ArrayModel.id' => 2)));
		$expected = array('ArrayModel' => array());
		$this->assertEqual($result, $expected);
	}

/**
 * testFindFirst
 *
 * @return void
 * @access public
 */
	function testFindFirst() {
		$result = $this->Model->find('first');
		$expected = array('ArrayModel' => array('id' => 1, 'name' => 'USA'));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('first', array('fields' => array('name')));
		$expected = array('ArrayModel' => array('name' => 'USA'));
		$this->assertEqual($result, $expected);
	}

/**
 * testFindCount
 *
 * @return void
 * @access public
 */
	function testFindCount() {
		$result = $this->Model->find('count');
		$this->assertEqual($result, 3);

		$result = $this->Model->find('count', array('limit' => 2));
		$this->assertEqual($result, 2);

		$result = $this->Model->find('count', array('limit' => 5));
		$this->assertEqual($result, 3);

		$result = $this->Model->find('count', array('limit' => 2, 'page' => 2));
		$this->assertEqual($result, 1);
	}

/**
 * testFindList
 *
 * @return void
 * @access public
 */
	function testFindList() {
		$result = $this->Model->find('list');
		$expected = array(1 => 1, 2 => 2, 3 => 3);
		$this->assertEqual($result, $expected);

		$this->Model->displayField = 'name';
		$result = $this->Model->find('list');
		$expected = array(1 => 'USA', 2 => 'Brazil', 3 => 'Germany');
		$this->assertEqual($result, $expected);
	}

/**
 * testRead
 *
 * @return void
 * @access public
 */
	function testRead() {
		$result = $this->Model->read(null, 1);
		$expected = array('ArrayModel' => array('id' => 1, 'name' => 'USA'));
		$this->assertEqual($result, $expected);

		$result = $this->Model->read(array('name'), 2);
		$expected = array('ArrayModel' => array('name' => 'Brazil'));
		$this->assertEqual($result, $expected);
	}
}

/**
 * Interact with Dbo Test
 *
 */
class IntractModelTest extends CakeTestCase {

/**
 * List of fixtures
 *
 * @var array
 * @access public
 */
	var $fixtures = array('plugin.array_datasource.user');

/**
 * skip
 *
 * @return void
 * @access public
 */
	function skip() {
		$db =& ConnectionManager::getDataSource('test');
		$this->skipUnless(is_subclass_of($db, 'DboSource'), '%s because database test not extends one DBO driver.');
	}

/**
 * testBeongsTo
 *
 * @return void
 * @access public
 */
	function testBelongsTo() {
		ClassRegistry::config(array());
		$model = ClassRegistry::init('UserModel');

		$result = $model->find('all', array('recursive' => 0));
		$expected = array(
			array('UserModel' => array('id' => 1, 'born_id' => 1, 'name' => 'User 1'), 'Born' => array('id' => 1, 'name' => 'USA')),
			array('UserModel' => array('id' => 2, 'born_id' => 2, 'name' => 'User 2'), 'Born' => array('id' => 2, 'name' => 'Brazil')),
			array('UserModel' => array('id' => 3, 'born_id' => 1, 'name' => 'User 3'), 'Born' => array('id' => 1, 'name' => 'USA')),
			array('UserModel' => array('id' => 4, 'born_id' => 3, 'name' => 'User 4'), 'Born' => array('id' => 3, 'name' => 'Germany'))
		);
		$this->assertEqual($result, $expected);

		$model->belongsTo['Born']['fields'] = array('name');
		$result = $model->find('all', array('recursive' => 0));
		$expected = array(
			array('UserModel' => array('id' => 1, 'born_id' => 1, 'name' => 'User 1'), 'Born' => array('name' => 'USA')),
			array('UserModel' => array('id' => 2, 'born_id' => 2, 'name' => 'User 2'), 'Born' => array('name' => 'Brazil')),
			array('UserModel' => array('id' => 3, 'born_id' => 1, 'name' => 'User 3'), 'Born' => array('name' => 'USA')),
			array('UserModel' => array('id' => 4, 'born_id' => 3, 'name' => 'User 4'), 'Born' => array('name' => 'Germany'))
		);
		$this->assertEqual($result, $expected);

		$result = $model->read(null, 1);
		$expected = array('UserModel' => array('id' => 1, 'born_id' => 1, 'name' => 'User 1'), 'Born' => array('name' => 'USA'));
		$this->assertEqual($result, $expected);
	}

/**
 * testBelongsToWithoutForeignKey
 *
 * @return void
 * @access public
 */
	function testBelongsToWithoutForeignKey() {
		ClassRegistry::config(array());
		$model = ClassRegistry::init('UserModel');
		$result = $model->find('all', array(
			'fields' => array('UserModel.id', 'UserModel.name'),
			'recursive' => 0
		));
		$expected = array(
			array('UserModel' => array('id' => 1, 'name' => 'User 1')),
			array('UserModel' => array('id' => 2, 'name' => 'User 2')),
			array('UserModel' => array('id' => 3, 'name' => 'User 3')),
			array('UserModel' => array('id' => 4, 'name' => 'User 4'))
		);
		$this->assertEqual($result, $expected);
	}
}
?>