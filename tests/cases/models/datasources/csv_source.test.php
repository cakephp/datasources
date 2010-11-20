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
 * @package       datasources
 * @subpackage    datasources.tests.cases.models.datasources
 * @since         CakePHP Datasources v 0.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Import required classes
 *
 */
App::import('Datasource', 'Datasources.CsvSource');

/**
 * Test Model for users.csv
 *
 * @package default
 * @author Predominant
 */
class UserTest extends CakeTestModel {

/**
 * Table to use
 *
 * @var string
 */	
	var $useTable = 'users';

/**
 * Datasource
 *
 * @var string
 */
	var $useDbConfig = 'test_csv';
}

/**
 * Test Model for blogs.csv
 *
 * @package default
 */
class BlogTest extends CakeTestModel {

/**
 * Table to use
 *
 * @var string
 */	
	var $useTable = 'blogs';

/**
 * Datasource
 *
 * @var string
 */
	var $useDbConfig = 'test_csv';

/**
 * Primary Key
 *
 * @var string
 */
	var $primaryKey = 'key';
}

/**
 * CsvSourceTestCase
 *
 * @package datasources
 * @subpackage datasources.tests.cases.models.datasources
 */
class CsvSourceTestCase extends CakeTestCase {

/**
 * Default testing configuration
 *
 * @var array
 * @access public
 */
	var $config = array();

/**
 * Csv DataSource Reference
 *
 * @var CsvSource
 * @access public
 */
	var $Csv = null;

/**
 * Start Test
 *
 * @return void
 * @access public
 */
	function startTest() {
		$this->config = array(
			'datasource' => 'Datasources.CsvSource',
			'path' => APP . 'plugins' . DS . 'datasources' . DS . 'tests' . DS . 'files',
			'extension' => 'csv',
			'readonly' => true,
			'recursive' => false);
		$this->Csv =& new CsvSource($this->config);
	}

/**
 * testInstance
 *
 * @return void
 * @access public
 */
	function testInstance() {
		$this->assertTrue(is_a($this->Csv, 'CsvSource'));
	}

/**
 * testNoAutoConnect
 *
 * @return void
 * @access public
 */
	function testNoAutoConnect() {
		$this->Csv =& new CsvSource($this->config, false);
		$this->assertTrue(is_a($this->Csv, 'CsvSource'));
		$this->assertFalse($this->Csv->connected);
	}

/**
 * testSources
 *
 * @return void
 * @access public
 */
	function testSources() {
		$this->Csv->cacheSources = false;

		$expected = array('blogs', 'posts', 'users');
		$result = $this->Csv->listSources();
		$this->assertIdentical($expected, $result);

		$expected = array('blogs', 'posts', 'users');
		$result = $this->Csv->listSources();
		$this->assertIdentical($expected, $result);
	}

/**
 * testRecursiveSources
 *
 * @return void
 * @access public
 */
	function testRecursiveSources() {
		$config = array_merge($this->config, array('recursive' => true));
		$this->Csv =& new CsvSource($config);
		$expected = array('blogs', 'posts', 'users', 'second_level' . DS . 'things');
		$result = $this->Csv->listSources();
		$this->assertIdentical($expected, $result);
	}

/**
 * testDescribe
 *
 * @return void
 * @access public
 */
	function testDescribe() {
	}

/**
 * testFind
 *
 * @return void
 * @access public
 */
	function testFind()
	{
		// Add new db config
		ConnectionManager::create('test_csv', $this->config);

		$model = ClassRegistry::init('UserTest');

		$expected = array(
			array(
				'UserTest' => array(
					'id'   => '1',
					'name' => 'predominant',
					'age'  => '29',
				),
			),
			array(
				'UserTest' => array(
					'id'   => '2',
					'name' => 'mr_sandman',
					'age'  => '21',
				),
			),
		);


		$result = $model->find('all');
		$this->assertEqual($result[0], $expected[0]);

		$result = $model->find('first');
		$this->assertEqual($result, $expected[0]);

		$result = $model->find('first', array('conditions' => array('UserTest.id' => 2)));
		$this->assertEqual($result, $expected[1]);

		$result = $model->find('count');
		$this->assertEqual(3, $result);

		$result = $model->find('all', array('conditions' => array('UserTest.id <' => 3)));
		$this->assertEqual($result, $expected);

		$result = $model->find('all', array('conditions' => array('UserTest.id <' => 3), 'limit' => 1));
		$expected_ = array($expected[0]);
		$this->assertEqual($result, $expected_);
	}

/**
 * testFindNonNumericalPrimaryKey
 *
 * @return void
 * @access public
 */
	function testFindNonNumericalPrimaryKey()
	{
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
		$this->assertEqual($result, $expected);

		$result = $model->find('first', array('conditions' => array('BlogTest.key' => 'myblog')));
		$this->assertEqual($result, $expected[1]);
	}

/**
 * End Test
 *
 * @return void
 * @author Predominant
 */
	function endTest() {
		$this->Csv = null;
	}
}
