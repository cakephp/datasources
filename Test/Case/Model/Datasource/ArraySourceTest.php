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
		array('array_model_id' => 1, 'relate_id' => 1, 'additional' => 98),
		array('array_model_id' => 1, 'relate_id' => 2, 'additional' => null),
		array('array_model_id' => 1, 'relate_id' => 3, 'additional' => 45),
		array('array_model_id' => 2, 'relate_id' => 1, 'additional' => null),
		array('array_model_id' => 2, 'relate_id' => 3, 'additional' => 68),
		array('array_model_id' => 3, 'relate_id' => 1, 'additional' => null),
		array('array_model_id' => 3, 'relate_id' => 2, 'additional' => 148)
	);
}

/**
 * User Testing Model
 *
 */
class UserModel extends CakeTestModel {

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
 * ArraySourceTestModel
 *
 * Base model for the following array models.
 */
abstract class ArraySourceTestModel extends CakeTestModel {

/**
 * Use the array config made earlier.
 *
 * @var string
 */
	public $useDbConfig = 'test_array';
}

/**
 * ArraySourceTestProfile
 *
 * Profile simulation model.
 */
class ArraySourceTestProfile extends ArraySourceTestModel {

/**
 * hasOne
 *
 * Associate with User.
 *
 * @var array
 */
	public $hasOne = array('ArraySourceTestUser');

/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array('id' => 1, 'title' => 'Lad'),
		array('id' => 2, 'title' => 'Lord'),
		array('id' => 3, 'title' => 'Sir')
	);
}

/**
 * ArraySourceTestUser
 *
 * User simulation model.
 */
class ArraySourceTestUser extends ArraySourceTestModel {

/**
 * belongsTo
 *
 * Associate with Profile.
 *
 * @var array
 */
	public $belongsTo = array('ArraySourceTestProfile');

/**
 * hasMany
 *
 * Associate with Post & Comment.
 *
 * @var array
 */
	public $hasMany = array('ArraySourceTestPost', 'ArraySourceTestComment');

/**
 * $hasAndBelongsToMany
 *
 * Associate with IpAddress
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('ArraySourceTestIpAddress');

/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array('id' => 1, 'array_source_test_profile_id' => 3, 'username' => 'Phally'),
		array('id' => 2, 'array_source_test_profile_id' => 2, 'username' => 'ADmad'),
		array('id' => 3, 'array_source_test_profile_id' => 1, 'username' => 'Jippi')
	);
}

/**
 * ArraySourceTestPost
 *
 * Post simulation model.
 */
class ArraySourceTestPost extends ArraySourceTestModel {

/**
 * belongsTo
 *
 * Associate with User.
 *
 * @var array
 */
	public $belongsTo = array('ArraySourceTestUser');

/**
 * hasMany
 *
 * Associate with Comment.
 *
 * @var array
 */
	public $hasMany = array('ArraySourceTestComment');

/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array('id' => 1, 'array_source_test_user_id' => 1, 'title' => 'First post'),
		array('id' => 2, 'array_source_test_user_id' => 1, 'title' => 'Second post'),
		array('id' => 3, 'array_source_test_user_id' => 2, 'title' => 'Third post'),
	);
}

/**
 * ArraySourceTestComment
 *
 * Comment simulation model.
 */
class ArraySourceTestComment extends ArraySourceTestModel {

/**
 * belongsTo
 *
 * Associate with Post & User
 *
 * @var array
 */
	public $belongsTo = array('ArraySourceTestPost', 'ArraySourceTestUser');

/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array('id' => 1, 'array_source_test_post_id' => 1, 'array_source_test_user_id' => 3, 'comment' => 'Cool story bro.'),
		array('id' => 2, 'array_source_test_post_id' => 1, 'array_source_test_user_id' => 1, 'comment' => 'Thanks!'),
		array('id' => 3, 'array_source_test_post_id' => 1, 'array_source_test_user_id' => 2, 'comment' => 'I dunno, wasn\'t that good.'),
		array('id' => 4, 'array_source_test_post_id' => 2, 'array_source_test_user_id' => 3, 'comment' => 'Literary masterpiece.'),
		array('id' => 5, 'array_source_test_post_id' => 2, 'array_source_test_user_id' => 2, 'comment' => 'Yep!'),
		array('id' => 6, 'array_source_test_post_id' => 2, 'array_source_test_user_id' => 3, 'comment' => 'I read it again, still brilliant.'),
	);
}

/**
 * ArraySourceTestIpAddress
 *
 * IpAddress simulation model.
 */
class ArraySourceTestIpAddress extends ArraySourceTestModel {

/**
 * $hasAndBelongsToMany
 *
 * Associate with User
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('ArraySourceTestUser');

/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array('id' => 1, 'ip' => '127.0.0.1'),
		array('id' => 2, 'ip' => '192.168.1.1'),
		array('id' => 3, 'ip' => '8.8.4.4')
	);
}

/**
 * ArraySourceTestIpAddressesArraySourceTestUser
 *
 * User - IpAddress simulation join model.
 */
class ArraySourceTestIpAddressesArraySourceTestUser extends ArraySourceTestModel {

/**
 * belongsTo
 *
 * Associate with User & IpAddress
 *
 * @var array
 */
	public $belongsTo = array('ArraySourceTestUser', 'ArraySourceTestIpAddress');

/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array('id' => 1, 'array_source_test_ip_address_id' => 1, 'array_source_test_user_id' => 2),
		array('id' => 2, 'array_source_test_ip_address_id' => 1, 'array_source_test_user_id' => 1),
		array('id' => 3, 'array_source_test_ip_address_id' => 2, 'array_source_test_user_id' => 1),
		array('id' => 4, 'array_source_test_ip_address_id' => 3, 'array_source_test_user_id' => 3),
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
 * Tear down for tests
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		ClassRegistry::flush();
		$this->Model = null;
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

		$model = ClassRegistry::init('ArraysRelateModel');

		$expected = array(
			array('ArraysRelateModel' => array('array_model_id' => 1, 'relate_id' => 2, 'additional' => null)),
			array('ArraysRelateModel' => array('array_model_id' => 2, 'relate_id' => 1, 'additional' => null)),
			array('ArraysRelateModel' => array('array_model_id' => 3, 'relate_id' => 1, 'additional' => null))
		);
		$result = $model->find('all', array('conditions' => array('additional' => null)));
		$this->assertSame($expected, $result);

		$expected = array(
			array('ArraysRelateModel' => array('array_model_id' => 1, 'relate_id' => 1, 'additional' => 98)),
			array('ArraysRelateModel' => array('array_model_id' => 1, 'relate_id' => 3, 'additional' => 45)),
			array('ArraysRelateModel' => array('array_model_id' => 2, 'relate_id' => 3, 'additional' => 68)),
			array('ArraysRelateModel' => array('array_model_id' => 3, 'relate_id' => 2, 'additional' => 148))
		);
		$result = $model->find('all', array('conditions' => array('additional != ' => null)));
		$this->assertSame($expected, $result);
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
		$model->bindModel(array('hasAndBelongsToMany' => array(
			'Relate' => array(
				'className' => 'ArrayModel',
				'with' => 'ArraysRelateModel',
				'associationForeignKey' => 'relate_id'
			)
		)), false);

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

/**
 * testArrayToTableHasAndBelongsToMany
 *
 * @return void
 */
	public function testArrayToTableHasAndBelongsToMany() {
		$User = ClassRegistry::init('UserModel');
		$result = $User->find('all', array('recursive' => 1));
		$User->bindModel(array('hasAndBelongsToMany' => array(
			'Relate' => array(
				'className' => 'ArrayModel',
				'with' => 'ArraysRelateModel',
				'foreignKey' => 'array_model_id',
				'associationForeignKey' => 'relate_id'
			)
		)), false);
		$User->unBindModel(array('belongsTo' => array('Born')), false);
		$result = $User->find('all', array('recursive' => 1));

		$User->ArraysRelateModel->records = array(
			array('array_model_id' => 1, 'relate_id' => 1)
		);
		$result = $User->find('all', array('recursive' => 1));
		$expected = array(
			array(
				'UserModel' => array('id' => 1, 'born_id' => 1, 'name' => 'User 1'),
				'Relate' => array(
					array('id' => 1, 'name' => 'USA', 'relate_id' => 1),
				),
			),
			array(
				'UserModel' => array('id' => 2, 'born_id' => 2, 'name' => 'User 2'),
				'Relate' => array(),
			),
			array(
				'UserModel' => array('id' => 3, 'born_id' => 1, 'name' => 'User 3'),
				'Relate' => array(),
			),
			array(
				'UserModel' => array('id' => 4, 'born_id' => 3, 'name' => 'User 4'),
				'Relate' => array(),
			)
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testDeepRecursion
 *
 * @return void
 */
	public function testDeepRecursion() {

		$Post = ClassRegistry::init('ArraySourceTestPost');

		$expected = array(
			0 => array(
				'ArraySourceTestPost' => array(
					'id' => 1,
					'array_source_test_user_id' => 1,
					'title' => 'First post'
				),
				'ArraySourceTestUser' => array(
					'id' => 1,
					'array_source_test_profile_id' => 3,
					'username' => 'Phally',
				)
			),
			1 => array(
				'ArraySourceTestPost' => array(
					'id' => 2,
					'array_source_test_user_id' => 1,
					'title' => 'Second post'
				),
				'ArraySourceTestUser' => array(
					'id' => 1,
					'array_source_test_profile_id' => 3,
					'username' => 'Phally'
				)
			)
		);

		$result = $Post->find('all', array(
			'recursive' => 0,
			'limit' => 2
		));

		$this->assertSame($expected, $result);

		$expected = array(
			0 => array(
				'ArraySourceTestPost' => array(
					'id' => 1,
					'array_source_test_user_id' => 1,
					'title' => 'First post'
				),
				'ArraySourceTestUser' => array(
					'id' => 1,
					'array_source_test_profile_id' => 3,
					'username' => 'Phally',
				),
				'ArraySourceTestComment' => array(
					0 => array(
						'id' => 1,
						'array_source_test_post_id' => 1,
						'array_source_test_user_id' => 3,
						'comment' => 'Cool story bro.'
					),
					1 => array(
						'id' => 2,
						'array_source_test_post_id' => 1,
						'array_source_test_user_id' => 1,
						'comment' => 'Thanks!'

					),
					2 => array(
						'id' => 3,
						'array_source_test_post_id' => 1,
						'array_source_test_user_id' => 2,
						'comment' => 'I dunno, wasn\'t that good.',
					)
				)
			)
		);

		$result = $Post->find('all', array(
			'recursive' => 1,
			'limit' => 1
		));

		$this->assertSame($expected, $result);

		$results = $Post->find('first', array(
			'recursive' => 2,
		));

		$expected = array('id' => 3, 'title' => 'Sir');
		$this->assertSame($expected, $results['ArraySourceTestUser']['ArraySourceTestProfile']);

		$expected = array(1, 2);
		$result = Hash::extract($results['ArraySourceTestUser']['ArraySourceTestPost'], '{n}.id');
		$this->assertSame($expected, $result);

		$expected = array(2);
		$result = Hash::extract($results['ArraySourceTestUser']['ArraySourceTestComment'], '{n}.id');
		$this->assertSame($expected, $result);

		$expected = array(
			'id', 'array_source_test_profile_id', 'username', 'ArraySourceTestProfile',
			'ArraySourceTestPost', 'ArraySourceTestComment', 'ArraySourceTestIpAddress'
		);
		$result = array_keys($results['ArraySourceTestUser']);
		$this->assertSame($expected, $result);

		$expected = array(1, 2, 3);
		$result = Hash::extract($results['ArraySourceTestComment'], '{n}.id');
		$this->assertSame($expected, $result);

		$expected = array(1, 1, 1);
		$result = Hash::extract($results['ArraySourceTestComment'], '{n}.ArraySourceTestPost.id');
		$this->assertSame($expected, $result);

		$expected = array(3, 1, 2);
		$result = Hash::extract($results['ArraySourceTestComment'], '{n}.ArraySourceTestUser.id');
		$this->assertSame($expected, $result);

		$this->assertFalse(isset($results['ArraySourceTestUser']['ArraySourceTestPost'][0]['ArraySourceTestUser']));
		$this->assertFalse(isset($results['ArraySourceTestUser']['ArraySourceTestPost'][0]['ArraySourceTestComment']));
		$this->assertFalse(isset($results['ArraySourceTestUser']['ArraySourceTestComment'][0]['ArraySourceTestPost']));
		$this->assertFalse(isset($results['ArraySourceTestUser']['ArraySourceTestComment'][0]['ArraySourceTestUser']));

		$this->assertFalse(isset($results['ArraySourceTestComment'][0]['ArraySourceTestPost']['ArraySourceTestUser']));
		$this->assertFalse(isset($results['ArraySourceTestComment'][0]['ArraySourceTestPost']['ArraySourceTestComment']));
		$this->assertFalse(isset($results['ArraySourceTestComment'][0]['ArraySourceTestUser']['ArraySourceTestProfile']));
		$this->assertFalse(isset($results['ArraySourceTestComment'][0]['ArraySourceTestUser']['ArraySourceTestComment']));


		$Profile = ClassRegistry::init('ArraySourceTestProfile');

		$expected = array(
			'ArraySourceTestProfile' => array(
				'id' => 1,
				'title' => 'Lad'
			),
			'ArraySourceTestUser' => array(
				'id' => 3,
				'array_source_test_profile_id' => 1,
				'username' => 'Jippi',
				'ArraySourceTestProfile' => array(
					'id' => 1,
					'title' => 'Lad'
				),
				'ArraySourceTestPost' => array(),
				'ArraySourceTestComment' => array(
					0 => array(
						'id' => 1,
						'array_source_test_post_id' => 1,
						'array_source_test_user_id' => 3,
						'comment' => 'Cool story bro.'
					),
					1 => array(
						'id' => 4,
						'array_source_test_post_id' => 2,
						'array_source_test_user_id' => 3,
						'comment' => 'Literary masterpiece.'
					),
					2 => array(
						'id' => 6,
						'array_source_test_post_id' => 2,
						'array_source_test_user_id' => 3,
						'comment' => 'I read it again, still brilliant.'
					)
				),
				'ArraySourceTestIpAddress' => array(
					0 => array(
						'id' => 3,
						'ip' => '8.8.4.4'
					)
				)

			)
		);

		$result = $Profile->find('first', array('recursive' => 2));
		$this->assertSame($expected, $result);
	}

/**
 * testDeepRecursionWithContainable
 *
 * @return void
 */
	public function testDeepRecursionWithContainable() {
		$Profile = ClassRegistry::init('ArraySourceTestProfile');
		$Profile->Behaviors->attach('Containable');

		$expected = array(
			'ArraySourceTestProfile' => array(
				'id' => 1,
				'title' => 'Lad'
			),
			'ArraySourceTestUser' => array(
				'id' => 3,
				'array_source_test_profile_id' => 1,
				'username' => 'Jippi',
				'ArraySourceTestComment' => array(
					0 => array(
						'id' => 1,
						'array_source_test_post_id' => 1,
						'array_source_test_user_id' => 3,
						'comment' => 'Cool story bro.',
						'ArraySourceTestPost' => array(
							'id' => 1,
							'array_source_test_user_id' => 1,
							'title' => 'First post'
						)
					),
					1 => array(
						'id' => 4,
						'array_source_test_post_id' => 2,
						'array_source_test_user_id' => 3,
						'comment' => 'Literary masterpiece.',
						'ArraySourceTestPost' => array(
							'id' => 2,
							'array_source_test_user_id' => 1,
							'title' => 'Second post'
						)
					),
					2 => array(
						'id' => 6,
						'array_source_test_post_id' => 2,
						'array_source_test_user_id' => 3,
						'comment' => 'I read it again, still brilliant.',
						'ArraySourceTestPost' => array(
							'id' => 2,
							'array_source_test_user_id' => 1,
							'title' => 'Second post'
						)
					)
				)
			)
		);

		$result = $Profile->find('first', array(
			'contain' => array(
				'ArraySourceTestUser' => array(
					'ArraySourceTestComment' => 'ArraySourceTestPost'
				)
			)
		));
		$this->assertSame($expected, $result);
	}

}
