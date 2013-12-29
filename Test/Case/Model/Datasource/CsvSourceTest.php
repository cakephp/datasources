<?php
/**
 * Csv DataSource Test file
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
 * @since         CakePHP Datasources v 0.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Import required classes
 *
 */
App::uses('CsvSource', 'Datasources.Model/Datasource');

/**
 * Test Model for users.csv
 *
 * @author Predominant
 */
class UserTest extends CakeTestModel {

/**
 * Table to use
 *
 * @var string
 */
	public $useTable = 'User';

/**
 * Datasource
 *
 * @var string
 */
	public $useDbConfig = 'test_csv';
}

/**
 * Test Model for blogs.csv
 *
 */
class BlogTest extends CakeTestModel {

/**
 * Table to use
 *
 * @var string
 */
	public $useTable = 'Blog';

/**
 * Datasource
 *
 * @var string
 */
	public $useDbConfig = 'test_csv';

/**
 * Primary Key
 *
 * @var string
 */
	public $primaryKey = 'key';
}

/**
 * CsvSourceTestCase
 *
 */
class CsvSourceTest extends CakeTestCase {

/**
 * Default testing configuration
 *
 * @var array
 */
	public $config = array();

/**
 * Csv DataSource Reference
 *
 * @var CsvSource
 */
	public $Csv = null;

/**
 * Start Test
 *
 * @return void
 */
	public function startTest($method) {
		$this->config = array(
			'datasource' => 'Datasources.CsvSource',
			'path' => App::pluginPath('Datasources') . 'Test' . DS . 'File',
			'extension' => 'csv',
			'readonly' => true,
			'recursive' => false
		);
		$this->Csv = new CsvSource($this->config);
	}

/**
 * testInstance
 *
 * @return void
 */
	public function testInstance() {
		$this->assertInstanceOf('CsvSource', $this->Csv);
	}

/**
 * testNoAutoConnect
 *
 * @return void
 */
	public function testNoAutoConnect() {
		$this->Csv = new CsvSource($this->config, false);
		$this->assertInstanceOf('CsvSource', $this->Csv);
		$this->assertFalse($this->Csv->connected);
	}

/**
 * testSources
 *
 * @return void
 */
	public function testSources() {
		$this->Csv->cacheSources = false;

		$expected = array('Blog', 'Post', 'User');
		$result = $this->Csv->listSources();
		$this->assertSame($expected, $result);

		$expected = array('Blog', 'Post', 'User');
		$result = $this->Csv->listSources();
		$this->assertSame($expected, $result);
	}

/**
 * testRecursiveSources
 *
 * @return void
 */
	public function testRecursiveSources() {
		$config = array_merge($this->config, array('recursive' => true));
		$this->Csv = new CsvSource($config);
		$expected = array('Blog', 'Post', 'User', 'SecondLevel' . DS . 'Thing');
		$result = $this->Csv->listSources();
		$this->assertSame($expected, $result);
	}

/**
 * testDescribe
 *
 * @return void
 */
	public function testDescribe() {
		ConnectionManager::create('test_csv', $this->config);
		$model = ClassRegistry::init('UserTest');
		$expected = array(
			'id', 'name', 'age'
		);
		$this->assertEquals($expected, $this->Csv->describe($model));
	}

/**
 * testFind
 *
 * @return void
 */
	public function testFind() {
		// Add new db config
		ConnectionManager::create('test_csv', $this->config);
		$model = ClassRegistry::init('UserTest');

		$expected = array(
			array(
				'UserTest' => array(
					'id' => '1',
					'name' => 'predominant',
					'age' => '29',
				),
			),
			array(
				'UserTest' => array(
					'id' => '2',
					'name' => 'mr_sandman',
					'age' => '21',
				),
			),
		);

		$result = $model->find('all');
		$this->assertEquals($result[0], $expected[0]);

		$result = $model->find('first');
		$this->assertEquals($result, $expected[0]);

		$result = $model->find('first', array('conditions' => array('UserTest.id' => 2)));
		$this->assertEquals($result, $expected[1]);

		$result = $model->find('count');
		$this->assertEquals(3, $result);

		$result = $model->find('all', array('conditions' => array('UserTest.id <' => 3)));
		$this->assertEquals($expected, $result);

		$result = $model->find('all', array('conditions' => array('UserTest.id <' => 3), 'limit' => 1));
		$expected = array($expected[0]);
		$this->assertEquals($expected, $result);
	}

/**
 * testFindNonNumericalPrimaryKey
 *
 * @return void
 */
	public function testFindNonNumericalPrimaryKey() {
		// Add new db config
		ConnectionManager::create('test_csv', $this->config);
		$model = ClassRegistry::init('BlogTest');

		$expected = array(
			array(
				'BlogTest' => array(
					'key'	=> '1st',
					'title' => '1st Blog',
				),
			),
			array(
				'BlogTest' => array(
					'key'	=> 'myblog',
					'title' => 'Hello World!',
				),
			),
		);

		$result = $model->find('all');
		$this->assertEquals($expected, $result);

		$result = $model->find('first', array('conditions' => array('BlogTest.key' => 'myblog')));
		$this->assertEquals($result, $expected[1]);

		$result = $model->find('all', array('conditions' => array(
			'OR' => array(
				'BlogTest.key' => array('myblog', '1st')
			),
		)));
		$this->assertEquals($expected, $result);

		$result = $model->find('all', array('conditions' => array(
			'OR' => array(
				'BlogTest.key' => 'myblog',
				'BlogTest.title' => '1st Blog',
			),
		)));
		$this->assertEquals($expected, $result);

		$expected = array(
			array(
				'BlogTest' => array(
					'key'	=> 'myblog',
					'title' => 'Hello World!',
				),
			),
		);
		$result = $model->find('all', array('conditions' => array(
			'OR' => array(
				'BlogTest.key' => 'myblog',
				'BlogTest.title' => 'Not found',
			),
		)));
		$this->assertEquals($expected, $result);
	}

/**
 * testClose
 *
 * @return void
 */
	public function testClose() {
		// Add new db config
		ConnectionManager::create('test_csv', $this->config);
		$model = ClassRegistry::init('BlogTest');
		$this->Csv->close();
		$this->assertFalse($this->Csv->connected);
		$this->assertEmpty($this->Csv->handle);
	}
/**
 * End Test
 *
 * @return void
 * @author Predominant
 */
	public function endTest($method) {
		$this->Csv = null;
	}
}
