<?php

App::import('Datasource', 'XmlrpcDatasource.XmlrpcSource');

class XmlrpcSourceTest extends CakeTestCase {

	var $Xmlrpc = null;

	function setUp() {
		parent::setUp();
		$this->Xmlrpc =& new XmlrpcSource();
	}

	function testGenerateXMLWithoutParams() {
		$header = '<' . '?xml version="1.0" encoding="UTF-8" ?' .'>' . "\n";
		$expected = $header . '<methodCall><methodName>test</methodName><params /></methodCall>';
		$this->assertEqual($expected, $this->Xmlrpc->generateXML('test'));
	}

	function testGenerateXMLOneParam() {
		$header = '<' . '?xml version="1.0" encoding="UTF-8" ?' .'>' . "\n";

		// Integer
		$expected = $header . '<methodCall><methodName>test</methodName><params><param><value><int>1</int></value></param></params></methodCall>';
		$this->assertEqual($expected, $this->Xmlrpc->generateXML('test', array(1)));

		// String
		$expected = $header . '<methodCall><methodName>test</methodName><params><param><value><string>testing</string></value></param></params></methodCall>';
		$this->assertEqual($expected, $this->Xmlrpc->generateXML('test', array('testing')));

		// Double
		$expected = $header . '<methodCall><methodName>test</methodName><params><param><value><double>5.2</double></value></param></params></methodCall>';
		$this->assertEqual($expected, $this->Xmlrpc->generateXML('test', array(5.2)));

		// Boolean
		$expected = $header . '<methodCall><methodName>test</methodName><params><param><value><boolean>0</boolean></value></param></params></methodCall>';
		$this->assertEqual($expected, $this->Xmlrpc->generateXML('test', array(false)));
		$expected = $header . '<methodCall><methodName>test</methodName><params><param><value><boolean>1</boolean></value></param></params></methodCall>';
		$this->assertEqual($expected, $this->Xmlrpc->generateXML('test', array(true)));

		// Array
		$expected = $header . '<methodCall><methodName>test</methodName><params><param><value><array><data><value><int>12</int></value><value><string>Egypt</string></value><value><boolean>0</boolean></value><value><int>-31</int></value></data></array></value></param></params></methodCall>';
		$this->assertEqual($expected, $this->Xmlrpc->generateXML('test', array(array(12, 'Egypt', false, -31))));

		// Struct
		$expected = $header . '<methodCall><methodName>test</methodName><params><param><value><struct><member><name>lowerBound</name><value><int>18</int></value></member><member><name>upperBound</name><value><int>139</int></value></member></struct></value></param></params></methodCall>';
		$this->assertEqual($expected, $this->Xmlrpc->generateXML('test', array(array('lowerBound' => 18, 'upperBound' => 139))));
	}

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

	function testGenerateXMLMultiDimensions() {
		$header = '<' . '?xml version="1.0" encoding="UTF-8" ?' .'>' . "\n";

		// Array
		$expected = $header . '<methodCall><methodName>test</methodName><params><param><value><array><data><value><int>1</int></value><value><array><data><value><int>2</int></value><value><string>b</string></value></data></array></value></data></array></value></param></params></methodCall>';
		$this->assertEqual($expected, $this->Xmlrpc->generateXML('test', array(array(1, array(2, 'b')))));

		// Struct
		$expected = $header . '<methodCall><methodName>test</methodName><params><param><value><struct><member><name>base</name><value><struct><member><name>value</name><value><double>-50.72</double></value></member></struct></value></member></struct></value></param></params></methodCall>';
		$this->assertEqual($expected, $this->Xmlrpc->generateXML('test', array(array('base' => array('value' => -50.720)))));
	}

}

?>