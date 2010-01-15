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
	}
}
?>