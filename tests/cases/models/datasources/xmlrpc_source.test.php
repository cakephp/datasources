<?php
/**
 * XML RPC Test file
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
 * @since         CakePHP Datasources v 0.1
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::import('Datasource', 'Datasources.XmlrpcSource');

/**
 * XML RPC Testing Model
 *
 * @package datasources
 * @subpackage datasources.tests.models.datasources
 */
class XmlrpcModel extends CakeTestModel {

/**
 * Table to use
 *
 * @var mixed
 * @access public
 */
	var $useTable = false;

/**
 * Database Configuration
 *
 * @var string
 * @access public
 */
	var $useDbConfig = 'test_xmlrpc';

/**
 * Get State Name
 *
 * @param string $number State Number
 * @return mixed
 * @access public
 */
	function getStateName($number) {
		return $this->query('examples.getStateName', $number);
	}

/**
 * call__ Pass to $db Object
 *
 * @param string $method Method Name
 * @param string $params Parameters
 * @return mixed
 * @access public
 */
	function call__($method, $params) {
		array_unshift($params, 'interopEchoTests.' . $method);

		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		return call_user_func_array(array(&$db, 'query'), $params);
	}
}

/**
 * XML RPC Datasource Test
 *
 * @package datasources
 * @subpackage datasources.tests.models.datasources.dbo
 */
class XmlrpcSourceTest extends CakeTestCase {

/**
 * XML RPC Source Instance
 *
 * @var XmlrpcSource
 * @access public
 */
	var $Xmlrpc = null;

/**
 * Set up for Tests
 *
 * @return void
 * @access public
 */
	function setUp() {
		parent::setUp();
		$this->Xmlrpc =& new XmlrpcSource();
	}

/**
 * testGenerateXMLWithoutParams
 *
 * @return void
 * @access public
 */
	function testGenerateXMLWithoutParams() {
		$header = '<' . '?xml version="1.0" encoding="UTF-8" ?' .'>' . "\n";
		$expected = $header . '<methodCall><methodName>test</methodName><params /></methodCall>';
		$this->assertEqual($expected, $this->Xmlrpc->generateXML('test'));
	}

/**
 * testGenerateXMLOneParam
 *
 * @return void
 * @access public
 */
	function testGenerateXMLOneParam() {
		$header = '<' . '?xml version="1.0" encoding="UTF-8" ?' .'>' . "\n";

		// Integer
		$expected = $header .
			'<methodCall><methodName>test</methodName><params><param><value><int>1</int></value></param></params></methodCall>';
		$this->assertEqual($expected, $this->Xmlrpc->generateXML('test', array(1)));

		// Double
		$expected = $header .
			'<methodCall><methodName>test</methodName><params><param><value><double>5.2</double></value></param></params></methodCall>';
		$this->assertEqual($expected, $this->Xmlrpc->generateXML('test', array(5.2)));

		// String
		$expected = $header .
			'<methodCall><methodName>test</methodName><params><param><value><string>testing</string></value></param></params></methodCall>';
		$this->assertEqual($expected, $this->Xmlrpc->generateXML('test', array('testing')));

		// Boolean
		$expected = $header .
			'<methodCall><methodName>test</methodName><params><param><value><boolean>0</boolean></value></param></params></methodCall>';
		$this->assertEqual($expected, $this->Xmlrpc->generateXML('test', array(false)));
		$expected = $header .
			'<methodCall><methodName>test</methodName><params><param><value><boolean>1</boolean></value></param></params></methodCall>';
		$this->assertEqual($expected, $this->Xmlrpc->generateXML('test', array(true)));

		// Array
		$expected = $header . '<methodCall><methodName>test</methodName><params><param><value><array><data><value><int>12</int></value><value><string>Egypt</string></value><value><boolean>0</boolean></value><value><int>-31</int></value></data></array></value></param></params></methodCall>';
		$this->assertEqual($expected, $this->Xmlrpc->generateXML('test', array(array(12, 'Egypt', false, -31))));

		// Struct
		$expected = $header . '<methodCall><methodName>test</methodName><params><param><value><struct><member><name>lowerBound</name><value><int>18</int></value></member><member><name>upperBound</name><value><int>139</int></value></member></struct></value></param></params></methodCall>';
		$this->assertEqual($expected, $this->Xmlrpc->generateXML('test', array(array('lowerBound' => 18, 'upperBound' => 139))));
	}

/**
 * testGenerateXMLMultiParams
 *
 * @return void
 * @access public
 */
	function testGenerateXMLMultiParams() {
		$header = '<' . '?xml version="1.0" encoding="UTF-8" ?' .'>' . "\n";

		$expected = $header . '<methodCall><methodName>test</methodName><params><param><value><int>1</int></value></param><param><value><string>testing</string></value></param></params></methodCall>';
		$this->assertEqual($expected, $this->Xmlrpc->generateXML('test', array(1, 'testing')));

		$expected = $header . '<methodCall><methodName>test</methodName><params>';
		$expected .= '<param><value><array><data><value><int>12</int></value><value><string>Egypt</string></value><value><boolean>0</boolean></value><value><int>-31</int></value></data></array></value></param>';
		$expected .= '<param><value><int>1</int></value></param>';
		$expected .= '<param><value><struct><member><name>test</name><value><boolean>1</boolean></value></member></struct></value></param>';
		$expected .= '</params></methodCall>';
		$this->assertEqual($expected, $this->Xmlrpc->generateXML('test', array(array(12, 'Egypt', false, -31), 1, array('test' => true))));
	}

/**
 * testGenerateXMLMultiDimensions
 *
 * @return void
 * @access public
 */
	function testGenerateXMLMultiDimensions() {
		$header = '<' . '?xml version="1.0" encoding="UTF-8" ?' .'>' . "\n";

		// Array
		$expected = $header . '<methodCall><methodName>test</methodName><params><param><value><array><data><value><int>1</int></value><value><array><data><value><int>2</int></value><value><string>b</string></value></data></array></value></data></array></value></param></params></methodCall>';
		$this->assertEqual($expected, $this->Xmlrpc->generateXML('test', array(array(1, array(2, 'b')))));

		// Struct
		$expected = $header . '<methodCall><methodName>test</methodName><params><param><value><struct><member><name>base</name><value><struct><member><name>value</name><value><double>-50.72</double></value></member></struct></value></member></struct></value></param></params></methodCall>';
		$this->assertEqual($expected, $this->Xmlrpc->generateXML('test', array(array('base' => array('value' => -50.720)))));
	}

/**
 * testParseResponse
 *
 * @return void
 * @access public
 */
	function testParseResponse() {
		// Integer
		$xml = '<?xml version="1.0"?><methodResponse><params><param><value><int>555</int></value></param></params></methodResponse>';
		$this->assertEqual(555, $this->Xmlrpc->parseResponse($xml));
		$xml = '<?xml version="1.0"?><methodResponse><params><param><value><i4>555</i4></value></param></params></methodResponse>';
		$this->assertEqual(555, $this->Xmlrpc->parseResponse($xml));

		// Double
		$xml = '<?xml version="1.0"?><methodResponse><params><param><value><double>57.20</double></value></param></params></methodResponse>';
		$this->assertEqual(57.2, $this->Xmlrpc->parseResponse($xml));

		// String
		$xml = '<?xml version="1.0"?><methodResponse><params><param><value><string>South Dakota</string></value></param></params></methodResponse>';
		$this->assertEqual('South Dakota', $this->Xmlrpc->parseResponse($xml));

		// Boolean
		$xml = '<?xml version="1.0"?><methodResponse><params><param><value><boolean>1</boolean></value></param></params></methodResponse>';
		$this->assertEqual(true, $this->Xmlrpc->parseResponse($xml));

		// Array
		$xml = '<?xml version="1.0"?><methodResponse><params><param><value><array><data><value><int>1</int></value><value><string>testing</string></value></data></array></value></param></params></methodResponse>';
		$this->assertEqual(array(1, 'testing'), $this->Xmlrpc->parseResponse($xml));
		$xml = '<?xml version="1.0"?><methodResponse><params><param><value><array><data><value><array><data><value><string>a</string></value><value><string>b</string></value></data></array></value><value><string>testing</string></value></data></array></value></param></params></methodResponse>';
		$this->assertEqual(array(array('a', 'b'), 'testing'), $this->Xmlrpc->parseResponse($xml));

		// Struct
		$xml = '<?xml version="1.0"?><methodResponse><params><param><value><struct><member><name>test</name><value><string>testing</string></value></member><member><name>boolean</name><value><boolean>1</boolean></value></member></struct></value></param></params></methodResponse>';
		$this->assertEqual(array('test' => 'testing', 'boolean' => true), $this->Xmlrpc->parseResponse($xml));

		$xml = '<?xml version="1.0"?><methodResponse><params><param><value><struct><member><name>test</name><value><struct><member><name>a</name><value><string>b</string></value></member><member><name>c</name><value><string>d</string></value></member></struct></value></member><member><name>test2</name><value><array><data><value><int>1</int></value><value><i4>2</i4></data></array></value></member></struct></value></param></params></methodResponse>';
		$this->assertEqual(array('test' => array('a' => 'b', 'c' => 'd'), 'test2' => array(1, 2)), $this->Xmlrpc->parseResponse($xml));

		// Struct in Array
		$xml = '<?xml version="1.0"?><methodResponse><params><param><value><array><data><value><struct><member><name>longitude</name><value><string>53</string></value></member><member><name>altitude</name><value><string>8.72543</string></value></member></struct></value></data></array></value></param></params></methodResponse>';
		$this->assertEqual(array(array('longitude' => 53, 'altitude' => 8.72543)), $this->Xmlrpc->parseResponse($xml));

		// Array in Array
		$xml = '<?xml version="1.0"?><methodResponse><params><param><value><array><data><value><array><data><value><int>12</int></value></data></array></value></data></array></value></param></params></methodResponse>';
		$this->assertEqual(array(array(12)), $this->Xmlrpc->parseResponse($xml));
	}

/**
 * testParseResponseError
 *
 * @return void
 * @access public
 */
	function testParseResponseError() {
		$xml = '<?xml version="1.0"?><methodResponse><fault><value><struct><member><name>faultCode</name><value><int>4</int></value></member><member><name>faultString</name><value><string>Too many parameters.</string></value></member></struct></value></fault></methodResponse>';
		$this->assertFalse($this->Xmlrpc->parseResponse($xml));
		$this->assertEqual(4, $this->Xmlrpc->errno);
		$this->assertEqual('Too many parameters.', $this->Xmlrpc->error);

		$xml = '<?xml version="1.0"?><methodInvalid><params /></methodInvalid>';
		$this->assertFalse($this->Xmlrpc->parseResponse($xml));
		$this->assertEqual(-32700, $this->Xmlrpc->errno);
		$this->assertFalse(empty($this->Xmlrpc->error));

		// This is a valid response, but the error must be cleared
		$xml = '<?xml version="1.0"?><methodResponse><params><param><value><boolean>0</boolean></value></param></params></methodResponse>';
		$this->assertFalse($this->Xmlrpc->parseResponse($xml));
		$this->assertEqual(0, $this->Xmlrpc->errno);
		$this->assertTrue(empty($this->Xmlrpc->error));
	}

/**
 * testRequest
 *
 * @return void
 * @access public
 */
	function testRequest() {
		// All nice
		$config = array(
			'host' => 'phpxmlrpc.sourceforge.net',
			'port' => 80,
			'url' => '/server.php'
		);
		$Xmlrpc = new XmlrpcSource($config);
		$this->assertEqual('Alabama', $Xmlrpc->query('examples.getStateName', 1));
		$this->assertEqual(5, $Xmlrpc->query('examples.addtwo', 2, 3));
		$this->assertTrue(is_array($Xmlrpc->query('system.listMethods')));

		// Not 200 (no connection)
		$config = array(
			'host' => 'invalid.host',
			'port' => 80,
			'url' => '/RPC2'
		);
		$Xmlrpc = new XmlrpcSource($config);
		$this->assertFalse($Xmlrpc->query('examples.getStateName', 1));
		$this->assertEqual(-32300, $Xmlrpc->errno);

		// Not 200 (HTTP 404)
		$config = array(
			'host' => 'code.google.com',
			'port' => 80,
			'url' => '/InvalidPath'
		);
		$Xmlrpc = new XmlrpcSource($config);
		$this->assertFalse($Xmlrpc->query('examples.getStateName', 1));
		$this->assertEqual(-32300, $Xmlrpc->errno);

		// Not XML-RPC Response
		$config = array(
			'host' => 'groups.google.com',
			'port' => 80,
			'url' => '/group/cake-php/feed/rss_v2_0_msgs.xml'
		);
		$Xmlrpc = new XmlrpcSource($config);
		$this->assertFalse($Xmlrpc->query('examples.getStateName', 1));
		$this->assertEqual(-32700, $Xmlrpc->errno);
	}

/**
 * testDescribe
 *
 * @return void
 * @access public
 */
	function testDescribe() {
		$config = array(
			'host' => 'phpxmlrpc.sourceforge.net',
			'port' => 80,
			'url' => '/server.php'
		);
		$Xmlrpc = new XmlrpcSource($config);
		$result = $Xmlrpc->describe();
		$this->assertTrue(is_array($result));
		$this->assertTrue(in_array('examples.getStateName', $result));

		// Not XML-RPC Response
		$config = array(
			'host' => 'groups.google.com',
			'port' => 80,
			'url' => '/group/cake-php/feed/rss_v2_0_msgs.xml'
		);
		$Xmlrpc = new XmlrpcSource($config);
		$this->assertFalse($Xmlrpc->describe());
	}

/**
 * testWithModel
 *
 * @return void
 * @access public
 */
	function testWithModel() {
		$connection = array(
			'datasource' => 'Datasources.XmlrpcSource',
			'host' => 'phpxmlrpc.sourceforge.net',
			'port' => 80,
			'url' => '/server.php'
		);
		ConnectionManager::create('test_xmlrpc', $connection);
		$model = ClassRegistry::init('XmlrpcModel');

		// Test implemented method in model
		$this->assertEqual('Alabama', $model->getStateName(1));

		// Test with call__
		$this->assertEqual('XmlrpcEcho', $model->echoString('XmlrpcEcho'));
	}
}
?>