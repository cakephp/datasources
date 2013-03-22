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

App::uses('ArraySource', 'Datasources.Model/Datasource');
App::uses('ConnectionManager', 'Model');

// Add new db config
ConnectionManager::create('test_array', array('datasource' => 'Datasources.ArraySource'));

/**
 * Array Testing Model
 *
 */
class ArrayModel extends CakeTestModel {

/**
 * Name of Model
 *
 * @var string
 */
	public $name = 'ArrayModel';

/**
 * Database Configuration
 *
 * @var string
 */
	public $useDbConfig = 'test_array';

/**
 * Set recursive
 *
 * @var integer
 */
	public $recursive = -1;

/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array(
			'id' => 1,
			'name' => 'USA',
			'relate_id' => 1
		),
		array(
			'id' => 2,
			'name' => 'Brazil',
			'relate_id' => 1
		),
		array(
			'id' => 3,
			'name' => 'Germany',
			'relate_id' => 2
		)
	);
}

/**
 * ArraysRelate Testing Model
 *
 */
class ArraysRelateModel extends CakeTestModel {

/**
 * Name of Model
 *
 * @var string
 */
	public $name = 'ArraysRelateModel';

/**
 * Database Configuration
 *
 * @var string
 */
	public $useDbConfig = 'test_array';

/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array('array_model_id' => 1, 'relate_id' => 1),
		array('array_model_id' => 1, 'relate_id' => 2),
		array('array_model_id' => 1, 'relate_id' => 3),
		array('array_model_id' => 2, 'relate_id' => 1),
		array('array_model_id' => 2, 'relate_id' => 3),
		array('array_model_id' => 3, 'relate_id' => 1),
		array('array_model_id' => 3, 'relate_id' => 2)
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
 */
	public $name = 'UserModel';

/**
 * Use DB Config
 *
 * @var string
 */
	public $useDbConfig = 'test';

/**
 * Use Table
 *
 * @var string
 */
	public $useTable = 'users';

/**
 * Belongs To
 *
 * @var array
 */
	public $belongsTo = array(
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
 * List of fixtures
 *
 * @var array
 */
	public $fixtures = array('plugin.datasources.user');

/**
 * Array Source Instance
 *
 * @var ArraySource
 */
	public $Model = null;

/**
 * Set up for Tests
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Model = ClassRegistry::init('ArrayModel');
	}

/**
 * testFindAll
 *
 * @return void
 */
	public function testFindAll() {
		$result = $this->Model->find('all');
		$expected = array(
			array('ArrayModel' => array('id' => 1, 'name' => 'USA', 'relate_id' => 1)),
			array('ArrayModel' => array('id' => 2, 'name' => 'Brazil', 'relate_id' => 1)),
			array('ArrayModel' => array('id' => 3, 'name' => 'Germany', 'relate_id' => 2))
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testFindFields
 *
 * @return void
 */
	public function testFindFields() {
		$expected = array(
			array('ArrayModel' => array('id' => 1)),
			array('ArrayModel' => array('id' => 2)),
			array('ArrayModel' => array('id' => 3))
		);
		$result = $this->Model->find('all', array('fields' => array('id')));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('fields' => array('ArrayModel.id')));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('fields' => array('ArrayModel.id', 'Unknow.id')));
		$this->assertEqual($result, $expected);
	}

/**
 * testField
 *
 * @return void
 */
	public function testField() {
		$expected = 2;
		$result = $this->Model->field('id', array('name' => 'Brazil'));
		$this->assertEqual($result, $expected);

		$expected = 'Germany';
		$result = $this->Model->field('name', array('relate_id' => 2));
		$this->assertEqual($result, $expected);

		$expected = 'USA';
		$result = $this->Model->field('name', array('relate_id' => 1));
		$this->assertEqual($result, $expected);
	}

/**
 * testFindLimit
 *
 * @return void
 */
	public function testFindLimit() {
		$result = $this->Model->find('all', array('limit' => 2));
		$expected = array(
			array('ArrayModel' => array('id' => 1, 'name' => 'USA', 'relate_id' => 1)),
			array('ArrayModel' => array('id' => 2, 'name' => 'Brazil', 'relate_id' => 1))
		);
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('limit' => 2, 'page' => 2));
		$expected = array(
			array('ArrayModel' => array('id' => 3, 'name' => 'Germany', 'relate_id' => 2))
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testFindOrder
 *
 * @return void
 */
	public function testFindOrder() {
		$result = $this->Model->find('all', array('order' => 'ArrayModel.name'));
		$expected = array(
			array('ArrayModel' => array('id' => 2, 'name' => 'Brazil', 'relate_id' => 1)),
			array('ArrayModel' => array('id' => 3, 'name' => 'Germany', 'relate_id' => 2)),
			array('ArrayModel' => array('id' => 1, 'name' => 'USA', 'relate_id' => 1))
		);
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('fields' => array('ArrayModel.id'), 'order' => 'ArrayModel.name'));
		$expected = array(
			array('ArrayModel' => array('id' => 2)),
			array('ArrayModel' => array('id' => 3)),
			array('ArrayModel' => array('id' => 1)),
		);
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('fields' => array('ArrayModel.id'), 'order' => 'ArrayModel.name', 'limit' => 1, 'page' => 2));
		$expected = array(
			array('ArrayModel' => array('id' => 3))
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testFindConditions
 *
 * @return void
 */
	public function testFindConditions() {
		$result = $this->Model->find('all', array('conditions' => array('ArrayModel.name' => 'USA')));
		$expected = array(array('ArrayModel' => array('id' => 1, 'name' => 'USA', 'relate_id' => 1)));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('conditions' => array('ArrayModel.name =' => 'USA')));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('conditions' => array('ArrayModel.name = USA')));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('conditions' => array('ArrayModel.name !=' => 'USA')));
		$expected = array(array('ArrayModel' => array('id' => 2, 'name' => 'Brazil', 'relate_id' => 1)), array('ArrayModel' => array('id' => 3, 'name' => 'Germany', 'relate_id' => 2)));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('conditions' => array('ArrayModel.name != USA')));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('conditions' => array('ArrayModel.name LIKE' => '%ra%')));
		$expected = array(array('ArrayModel' => array('id' => 2, 'name' => 'Brazil', 'relate_id' => 1)));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('conditions' => array('ArrayModel.name LIKE %ra%')));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('conditions' => array('ArrayModel.name LIKE _r%')));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('conditions' => array('ArrayModel.name LIKE %b%')));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('conditions' => array('ArrayModel.name LIKE %a%')));
		$expected = array(array('ArrayModel' => array('id' => 1, 'name' => 'USA', 'relate_id' => 1)), array('ArrayModel' => array('id' => 2, 'name' => 'Brazil', 'relate_id' => 1)), array('ArrayModel' => array('id' => 3, 'name' => 'Germany', 'relate_id' => 2)));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('conditions' => array('ArrayModel.name' => array('USA', 'Germany'))));
		$expected = array(array('ArrayModel' => array('id' => 1, 'name' => 'USA', 'relate_id' => 1)), array('ArrayModel' => array('id' => 3, 'name' => 'Germany', 'relate_id' => 2)));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('conditions' => array('ArrayModel.name IN (USA, Germany)')));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('conditions' => array('ArrayModel.name' => 'USA', 'ArrayModel.id' => 2)));
		$expected = array();
		$this->assertIdentical($result, $expected);
	}

/**
 * testFindconditionsRecursive
 *
 * @return void
 */
	public function testFindConditionsRecursive() {
		$result = $this->Model->find('all', array('conditions' => array('AND' => array('ArrayModel.name' => 'USA', 'ArrayModel.id' => 2))));
		$expected = array();
		$this->assertIdentical($result, $expected);

		$result = $this->Model->find('all', array('conditions' => array('OR' => array('ArrayModel.name' => 'USA', 'ArrayModel.id' => 2))));
		$expected = array(
			array('ArrayModel' => array('id' => 1, 'name' => 'USA', 'relate_id' => 1)),
			array('ArrayModel' => array('id' => 2, 'name' => 'Brazil', 'relate_id' => 1))
		);
		$this->assertIdentical($result, $expected);

		$result = $this->Model->find('all', array('conditions' => array('NOT' => array('ArrayModel.id' => 2))));
		$expected = array(
			array('ArrayModel' => array('id' => 1, 'name' => 'USA', 'relate_id' => 1)),
			array('ArrayModel' => array('id' => 3, 'name' => 'Germany', 'relate_id' => 2))
		);
		$this->assertIdentical($result, $expected);
	}

/**
 * testFindConditionsWithComparisonOperators
 *
 * @return void
 * @access public
 */
	public function testFindConditionsWithComparisonOperators() {
		$result = $this->Model->find('all', array('conditions' => array('ArrayModel.id <' => 2)));
		$expected = array(
			array('ArrayModel' => array('id' => 1, 'name' => 'USA', 'relate_id' => 1))
		);
		$this->assertSame($expected, $result);

		$result = $this->Model->find('all', array('conditions' => array('ArrayModel.id <=' => 2)));
		$expected = array(
			array('ArrayModel' => array('id' => 1, 'name' => 'USA', 'relate_id' => 1)),
			array('ArrayModel' => array('id' => 2, 'name' => 'Brazil', 'relate_id' => 1))
		);
		$this->assertSame($expected, $result);

		$result = $this->Model->find('all', array('conditions' => array('ArrayModel.id >' => 2)));
		$expected = array(
			array('ArrayModel' => array('id' => 3, 'name' => 'Germany', 'relate_id' => 2))
		);
		$this->assertSame($expected, $result);

		$result = $this->Model->find('all', array('conditions' => array('ArrayModel.id >=' => 2)));
		$expected = array(
			array('ArrayModel' => array('id' => 2, 'name' => 'Brazil', 'relate_id' => 1)),
			array('ArrayModel' => array('id' => 3, 'name' => 'Germany', 'relate_id' => 2))
		);
		$this->assertSame($expected, $result);
	}

/**
 * testFindFirst
 *
 * @return void
 */
	public function testFindFirst() {
		$result = $this->Model->find('first');
		$expected = array('ArrayModel' => array('id' => 1, 'name' => 'USA', 'relate_id' => 1));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('first', array('fields' => array('name')));
		$expected = array('ArrayModel' => array('name' => 'USA'));
		$this->assertEqual($result, $expected);
	}

/**
 * testFindCount
 *
 * @return void
 */
	public function testFindCount() {
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
 */
	public function testFindList() {
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
 */
	public function testRead() {
		$result = $this->Model->read(null, 1);
		$expected = array('ArrayModel' => array('id' => 1, 'name' => 'USA', 'relate_id' => 1));
		$this->assertEqual($result, $expected);

		$result = $this->Model->read(array('name'), 2);
		$expected = array('ArrayModel' => array('name' => 'Brazil'));
		$this->assertEqual($result, $expected);
	}

/**
 * testDboToArrayBelongsTo
 *
 * @return void
 */
	public function testDboToArrayBelongsTo() {
		ClassRegistry::config(array());
		$model = ClassRegistry::init('UserModel');

		$result = $model->find('all', array('recursive' => 0));
		// unset primaryKey, wich can be integer/serial or hash value
		foreach ($result as &$row) {
			unset($row['UserModel'][$model->primaryKey]);
		}
		$expected = array(
			array('UserModel' => array('born_id' => 1, 'name' => 'User 1'), 'Born' => array('id' => 1, 'name' => 'USA', 'relate_id' => 1)),
			array('UserModel' => array('born_id' => 2, 'name' => 'User 2'), 'Born' => array('id' => 2, 'name' => 'Brazil', 'relate_id' => 1)),
			array('UserModel' => array('born_id' => 1, 'name' => 'User 3'), 'Born' => array('id' => 1, 'name' => 'USA', 'relate_id' => 1)),
			array('UserModel' => array('born_id' => 3, 'name' => 'User 4'), 'Born' => array('id' => 3, 'name' => 'Germany', 'relate_id' => 2))
		);
		$this->assertEqual($result, $expected);

		$model->belongsTo['Born']['fields'] = array('name');
		$result = $model->find('all', array('recursive' => 0));
		// unset primaryKey, wich can be integer/serial or hash value
		foreach ($result as &$row) {
			unset($row['UserModel'][$model->primaryKey]);
		}
		$expected = array(
			array('UserModel' => array('born_id' => 1, 'name' => 'User 1'), 'Born' => array('name' => 'USA')),
			array('UserModel' => array('born_id' => 2, 'name' => 'User 2'), 'Born' => array('name' => 'Brazil')),
			array('UserModel' => array('born_id' => 1, 'name' => 'User 3'), 'Born' => array('name' => 'USA')),
			array('UserModel' => array('born_id' => 3, 'name' => 'User 4'), 'Born' => array('name' => 'Germany'))
		);
		$this->assertEqual($result, $expected);

		$result = $model->read(null, 1);
		unset($result['UserModel'][$model->primaryKey]);
		$expected = array('UserModel' => array('born_id' => 1, 'name' => 'User 1'), 'Born' => array('name' => 'USA'));
		$this->assertEqual($result, $expected);
	}

/**
 * testDboToArrayBelongsToWithoutForeignKey
 *
 * @return void
 */
	public function testDboToArrayBelongsToWithoutForeignKey() {
		ClassRegistry::config(array());
		$model = ClassRegistry::init('UserModel');

		$result = $model->find('all', array(
			'fields' => array('UserModel.id', 'UserModel.name'),
			'recursive' => 0
		));
		// unset primaryKey, wich can be integer/serial or hash value
		foreach ($result as &$row) {
			unset($row['UserModel'][$model->primaryKey]);
		}
		$expected = array(
			array(
				'UserModel' => array('name' => 'User 1'),
				'Born' => array()
			),
			array(
				'UserModel' => array('name' => 'User 2'),
				'Born' => array()
			),
			array(
				'UserModel' => array('name' => 'User 3'),
				'Born' => array()
			),
			array(
				'UserModel' => array('name' => 'User 4'),
				'Born' => array()
			)
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testDboToArrayHasMany
 *
 * @return void
 */
	public function testDboToArrayHasMany() {
		ClassRegistry::config(array());
		$model = ClassRegistry::init('UserModel');
		$model->unBindModel(array('belongsTo' => array('Born')), false);
		$model->bindModel(array('hasMany' => array('Relate' => array('className' => 'ArrayModel', 'foreignKey' => 'relate_id'))), false);

		$result = $model->find('all', array('recursive' => 1));
		// unset primaryKey, wich can be integer/serial or hash value
		foreach ($result as &$row) {
			unset($row['UserModel'][$model->primaryKey]);
		}
		$expected = array(
			array(
				'UserModel' => array('name' => 'User 1', 'born_id' => 1),
				'Relate' => array(
					array('id' => 1, 'name' => 'USA', 'relate_id' => 1),
					array('id' => 2, 'name' => 'Brazil', 'relate_id' => 1)
				),
			),
			array('UserModel' => array('name' => 'User 2', 'born_id' => 2),
				'Relate' => array(
					array('id' => 3, 'name' => 'Germany', 'relate_id' => 2)
				),
			),
			array('UserModel' => array('name' => 'User 3', 'born_id' => 1),
				'Relate' => array(
				),
			),
			array('UserModel' => array('name' => 'User 4', 'born_id' => 3),
				'Relate' => array(
				),
			)
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testDboToArrayHasOne
 *
 * @return void
 */
	public function testDboToArrayHasOne() {
		ClassRegistry::config(array());
		$model = ClassRegistry::init('UserModel');
		$model->unBindModel(array('hasMany' => array('Relate'), 'belongsTo' => array('Born')), false);
		$model->bindModel(array('hasOne' => array('Relate' => array('className' => 'ArrayModel', 'foreignKey' => 'relate_id'))), false);

		$result = $model->find('all', array('recursive' => 1));
		// unset primaryKey, wich can be integer/serial or hash value
		foreach ($result as &$row) {
			unset($row['UserModel'][$model->primaryKey]);
		}
		$expected = array(
			array(
				'UserModel' => array('name' => 'User 1', 'born_id' => 1),
				'Relate' => array('id' => 1, 'name' => 'USA', 'relate_id' => 1),
			),
			array('UserModel' => array('name' => 'User 2', 'born_id' => 2),
				'Relate' => array('id' => 3, 'name' => 'Germany', 'relate_id' => 2),
			),
			array(
				'UserModel' => array('name' => 'User 3', 'born_id' => 1),
				'Relate' => array()
			),
			array(
				'UserModel' => array('name' => 'User 4', 'born_id' => 3),
				'Relate' => array()
			)
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testArrayToArrayBelongsTo
 *
 * @return void
 */
	public function testArrayToArrayBelongsTo() {
		ClassRegistry::config(array());
		$model = ClassRegistry::init('ArrayModel');
		$model->recursive = 0;
		$model->bindModel(array('belongsTo' => array('Relate' => array('className' => 'ArrayModel', 'foreignKey' => 'relate_id'))), false);

		$result = $model->find('all');
		$expected = array(
			array(
				'ArrayModel' => array('id' => 1, 'name' => 'USA', 'relate_id' => 1),
				'Relate' => array('id' => 1, 'name' => 'USA', 'relate_id' => 1)
			),
			array(
				'ArrayModel' => array('id' => 2, 'name' => 'Brazil', 'relate_id' => 1),
				'Relate' => array('id' => 1, 'name' => 'USA', 'relate_id' => 1)
			),
			array(
				'ArrayModel' => array('id' => 3, 'name' => 'Germany', 'relate_id' => 2),
				'Relate' => array('id' => 2, 'name' => 'Brazil', 'relate_id' => 1)
			)
		);
		$this->assertEqual($result, $expected);

		$model->belongsTo['Relate']['fields'] = array('name');

		$result = $model->find('all');
		$expected = array(
			array(
				'ArrayModel' => array('id' => 1, 'name' => 'USA', 'relate_id' => 1),
				'Relate' => array('name' => 'USA')
			),
			array(
				'ArrayModel' => array('id' => 2, 'name' => 'Brazil', 'relate_id' => 1),
				'Relate' => array('name' => 'USA')
			),
			array(
				'ArrayModel' => array('id' => 3, 'name' => 'Germany', 'relate_id' => 2),
				'Relate' => array('name' => 'Brazil')
			)
		);
		$this->assertEqual($result, $expected);

		$result = $model->read(null, 1);
		$expected = array(
			'ArrayModel' => array('id' => 1, 'name' => 'USA', 'relate_id' => 1),
			'Relate' => array('name' => 'USA')
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testArrayToArrayBelongsToWithoutForeignKey
 *
 * @return void
 */
	public function testArrayToArrayBelongsToWithoutForeignKey() {
		ClassRegistry::config(array());
		$model = ClassRegistry::init('ArrayModel');
		$model->bindModel(array('belongsTo' => array('Relate' => array('className' => 'ArrayModel', 'foreignKey' => 'relate_id'))), false);

		$result = $model->find('all', array(
			'fields' => array('ArrayModel.id', 'ArrayModel.name'),
			'recursive' => 0
		));
		$expected = array(
			array(
				'ArrayModel' => array('id' => 1, 'name' => 'USA'),
				'Relate' => array()
			),
			array(
				'ArrayModel' => array('id' => 2, 'name' => 'Brazil'),
				'Relate' => array()
			),
			array(
				'ArrayModel' => array('id' => 3, 'name' => 'Germany'),
				'Relate' => array()
			)
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testArrayToArrayHasMany
 *
 * @return void
 */
	public function testArrayToArrayHasMany() {
		ClassRegistry::config(array());
		$model = ClassRegistry::init('ArrayModel');
		$model->unBindModel(array('belongsTo' => array('Relate')), false);
		$model->bindModel(array('hasMany' => array('Relate' => array('className' => 'ArrayModel', 'foreignKey' => 'relate_id'))), false);

		$result = $model->find('all', array('recursive' => 1));
		$expected = array(
			array(
				'ArrayModel' => array('id' => 1, 'name' => 'USA', 'relate_id' => 1),
				'Relate' => array(
					array('id' => 1, 'name' => 'USA', 'relate_id' => 1),
					array('id' => 2, 'name' => 'Brazil', 'relate_id' => 1)
				),
			),
			array('ArrayModel' => array('id' => 2, 'name' => 'Brazil', 'relate_id' => 1),
				'Relate' => array(
					array('id' => 3, 'name' => 'Germany', 'relate_id' => 2)
				),
			),
			array('ArrayModel' => array('id' => 3, 'name' => 'Germany', 'relate_id' => 2),
				'Relate' => array(),
			)
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testArrayToArrayHasOne
 *
 * @return void
 */
	public function testArrayToArrayHasOne() {
		ClassRegistry::config(array());
		$model = ClassRegistry::init('ArrayModel');
		$model->unBindModel(array('hasMany' => array('Relate')), false);
		$model->bindModel(array('hasOne' => array('Relate' => array('className' => 'ArrayModel', 'foreignKey' => 'relate_id'))), false);

		$result = $model->find('all', array('recursive' => 1));
		$expected = array(
			array(
				'ArrayModel' => array('id' => 1, 'name' => 'USA', 'relate_id' => 1),
				'Relate' => array('id' => 1, 'name' => 'USA', 'relate_id' => 1)
			),
			array(
				'ArrayModel' => array('id' => 2, 'name' => 'Brazil', 'relate_id' => 1),
				'Relate' => array('id' => 3, 'name' => 'Germany', 'relate_id' => 2)
			),
			array(
				'ArrayModel' => array('id' => 3, 'name' => 'Germany', 'relate_id' => 2),
				'Relate' => array()
			)
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testArrayToArrayHasAndBelongsToMany
 *
 * @return void
 */
	public function testArrayToArrayHasAndBelongsToMany() {
		ClassRegistry::config(array());
		$model = ClassRegistry::init('ArrayModel');
		$model->unBindModel(array('hasOne' => array('Relate')), false);
		$model->bindModel(array('hasAndBelongsToMany' => array('Relate' => array('className' => 'ArrayModel', 'with' => 'ArraysRelateModel', 'associationForeignKey' => 'relate_id'))), false);

		$result = $model->find('all', array('recursive' => 1));
		$expected = array(
			array(
				'ArrayModel' => array('id' => 1, 'name' => 'USA', 'relate_id' => 1),
				'Relate' => array(
					array('id' => 1, 'name' => 'USA', 'relate_id' => 1),
					array('id' => 2, 'name' => 'Brazil', 'relate_id' => 1),
					array('id' => 3, 'name' => 'Germany', 'relate_id' => 2)
				),
			),
			array(
				'ArrayModel' => array('id' => 2, 'name' => 'Brazil', 'relate_id' => 1),
				'Relate' => array(
					array('id' => 1, 'name' => 'USA', 'relate_id' => 1),
					array('id' => 3, 'name' => 'Germany', 'relate_id' => 2)
				),
			),
			array(
				'ArrayModel' => array('id' => 3, 'name' => 'Germany', 'relate_id' => 2),
				'Relate' => array(
					array('id' => 1, 'name' => 'USA', 'relate_id' => 1),
					array('id' => 2, 'name' => 'Brazil', 'relate_id' => 1)
				),
			)
		);
		$this->assertEqual($result, $expected);
	}
}
