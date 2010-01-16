<?php
/**
 * Array Source Test file
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
 * @subpackage    datasources.tests.cases.models.datasources.dbo
 * @since         CakePHP Datasources v 0.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::import('Datasource', 'Datasources.ArraySource');

// Add new db config
ConnectionManager::getDataSource('test');
ConnectionManager::create('test_array', array('datasource' => 'Datasources.ArraySource'));

/**
 * Array Testing Model
 *
 * @package datasources
 * @subpackage datasources.tests.models.datasources.dbo
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

class UserModel extends CakeTestModel {
	var $name = 'UserModel';
	var $useDbConfig = 'test';
	var $useTable = 'users';
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
 * @package datasources
 * @subpackage datasources.tests.models.datasources.dbo
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
			)
		);
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('fields' => array('id')));
		$expected = array(
			'ArrayModel' => array(
				array(
					'id' => 1
				),
				array(
					'id' => 2
				),
				array(
					'id' => 3
				)
			)
		);
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('fields' => array('ArrayModel.id')));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('fields' => array('ArrayModel.id', 'Unknow.id')));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('limit' => 2));
		$expected = array(
			'ArrayModel' => array(
				array(
					'id' => 1,
					'name' => 'USA'
				),
				array(
					'id' => 2,
					'name' => 'Brazil'
				)
			)
		);
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('limit' => 2, 'page' => 2));
		$expected = array(
			'ArrayModel' => array(
				array(
					'id' => 3,
					'name' => 'Germany'
				)
			)
		);
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('order' => 'ArrayModel.name'));
		$expected = array(
			'ArrayModel' => array(
				array(
					'id' => 2,
					'name' => 'Brazil'
				),
				array(
					'id' => 3,
					'name' => 'Germany'
				),
				array(
					'id' => 1,
					'name' => 'USA'
				)
			)
		);
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('fields' => array('ArrayModel.id'), 'order' => 'ArrayModel.name'));
		$expected = array(
			'ArrayModel' => array(
				array(
					'id' => 2
				),
				array(
					'id' => 3
				),
				array(
					'id' => 1
				)
			)
		);
		$this->assertEqual($result, $expected);

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

	function testFindFirst() {
		$result = $this->Model->find('first');
		$expected = array('id' => 1, 'name' => 'USA');
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('first', array('fields' => array('name')));
		$expected = array('name' => 'USA');
		$this->assertEqual($result, $expected);
	}

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

	function testFindList() {
		$result = $this->Model->find('list');
		$expected = array(1 => 1, 2 => 2, 3 => 3);
		$this->assertEqual($result, $expected);

		$this->Model->displayField = 'name';
		$result = $this->Model->find('list');
		$expected = array(1 => 'USA', 2 => 'Brazil', 3 => 'Germany');
		$this->assertEqual($result, $expected);
	}
}

class IntractModelTest extends CakeTestCase {

	var $fixtures = array('plugin.datasources.user');

	function skip() {
		$db =& ConnectionManager::getDataSource('test');
		$this->skipUnless(is_subclass_of($db, 'DboSource'), '%s because database test not extends one DBO driver.');
	}

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
}
?>