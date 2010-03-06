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

class CsvSourceTestModel extends CakeTestModel {
	
	var $useTable = 'users';
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

	function testSources() {
		$expected = array('posts', 'users');
		sort($expected);
		$result = $this->Csv->listSources();
		sort($result);
		$this->assertIdentical($expected, $result);
	}

	function testSourceCaching() {
		$this->Csv->cacheSources = false;

		$expected = array('posts', 'users');
		sort($expected);
		$result = $this->Csv->listSources();
		sort($result);
		$this->assertIdentical($expected, $result);

		$expected = array('posts', 'users');
		sort($expected);
		$result = $this->Csv->listSources();
		sort($result);
		$this->assertIdentical($expected, $result);

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
?>