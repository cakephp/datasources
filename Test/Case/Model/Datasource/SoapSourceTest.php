<?php 

App::uses('SoapSource', 'Datasources.Model/Datasource');
App::uses('ConnectionManager', 'Model');

/**
 * 
 * Test Model for web services
 *
 */
class ServiceModelTest extends CakeTestModel {

/**
 * Test model does not use a table
 * 
 * @var mixed
 */
	public $useTable = false;

/**
 * Database config for test model
 * 
 * @var string
 */
	public $useDbConfig = 'test_soap';
}

/**
 * 
 * SoapSourceTestCase
 * 
 * @package datasources
 * @subpackage datasources.test.cases.models.datasources
 *
 */
class SoapSourceTestCase extends CakeTestCase {

/**
 * Default testing configuration
 * 
 * @var array
 */
	public $config = array();

/**
 * Soap DataSource reference
 * 
 * @var SoapSource
 */
	public $Soap = null;

/**
 * Class name of mocked SoapClient
 * 
 * @var string
 */
	public $soapClientClassName = null;

/**
 * Set up a config, SoapSource and a mock class of SoapClient
 * @see CakeTestCase::setUp()
 */
	public function setUp() {
		$this->config = array(
			'datasource' => 'Datasources.SoapSource',
			'wsdl' => 'file://' . App::pluginPath('Datasources') . 'Test' . DS . 'File' . DS . 'demo2.wsdl'
		);

		$this->Soap = new SoapSource($this->config);

		$this->soapClientClassName = $this->getMockClass('SoapClient', array('__soapCall'));
	}

/**
 * Test the constructor
 * 
 * @return void 
 */
	public function testConstruct() {
		$this->assertTrue($this->Soap->connected);
		$this->assertInstanceOf('SoapClient', $this->Soap->client);
		$this->assertObjectNotHasAttribute('error', $this->Soap);
	}

/**
 * Test invalid soap client
 * 
 * @return void
 */
	public function testInvalidSoapClient() {
		$this->config['soap_client_class'] = 'PretendClass';
		$this->setExpectedException('PHPUnit_Framework_Error');
		$source = new SoapSource($this->config);

		$this->fail('Expected trigger_error');
	}

/**
 * Test correct instance
 * 
 * @return void
 */
	public function testInstance() {
		$this->assertTrue(is_a($this->Soap, 'SoapSource'));
	}

/**
 * Test disabling auto connect
 * 
 * @return void
 */
	public function testNoAutoConnect() {
		$this->Soap =& new SoapSource($this->config, false);
		$this->assertTrue(is_a($this->Soap, 'SoapSource'));
		$this->assertFalse($this->Soap->connected);
	}

/**
 * Test failing to construct the datasource
 * 
 * @return void
 */
	public function testFailedConstruct() {
		$this->setExpectedException('PHPUnit_Framework_Error');
		$source = new SoapSource(array());

		$this->fail('Expected trigger_error');
	}

/**
 * Callback to assist with testing SoapFault
 * 
 * @throws SoapFault
 */
	public function throwSoapFault() {
		throw new SoapFault('my code', 'my soap fault');
	}

/**
 * Testing SoapFault
 * 
 * @return void
 */
	public function testSoapFault() {
		$this->Soap->setSoapClientClass($this->soapClientClassName);

		// direct call via query

		$this->Soap->client->expects($this->once())
			->method('__soapCall')
			->with('someString', array(), null)
			->will($this->returnCallback(array($this, 'throwSoapFault')));

		try {
			$result = $this->Soap->query('someString');
		} catch (Exception $e) {
			$this->assertEquals($this->Soap->error, 'my soap fault');
		}
	}

/**
 * Testing closing
 * 
 * @return void
 */
	public function testClose() {
		$this->Soap->close();

		$this->assertEqual($this->Soap->client, null);
		$this->assertFalse($this->Soap->connected);
	}

/**
 * Testing no connection after close
 * 
 * @return void
 */
	public function testNoConnection() {
		$this->Soap->close();

		$this->assertEqual($this->Soap->client, null);
		$this->assertFalse($this->Soap->connected);

		$result = $this->Soap->query('doSomething');

		$this->assertFalse($result);
	}

/**
 * Testing list sources
 * 
 * @return void
 */
	public function testListSources() {
		$sources = $this->Soap->listSources();
		$this->assertTrue(in_array('string someString()', $sources), 'Can not find someString');
	}

/**
 * Testing direct soap call using query()
 * 
 * @return void
 */
	public function testCallWithQuery() {
		// override __soapCall so it is not actually called

		$this->Soap->setSoapClientClass($this->soapClientClassName);

		// direct call via query
		$this->Soap->client->expects($this->once())
			->method('__soapCall')
			->with('someString', array(), null)
			->will($this->returnValue(true));

		$result = $this->Soap->query('someString');

		$this->assertFalse(!$result, 'Raw query returned false');
	}

/**
 * Testing direct soap call using query with too many parameters
 * 
 * @return void
 */
	public function testCallWithQueryTooMany() {
		$this->Soap->setSoapClientClass($this->soapClientClassName);

		// direct call via query
		$this->Soap->client->expects($this->once())
			->method('__soapCall')
			->with('someString', array(), null)
			->will($this->returnValue(true));

		$result = $this->Soap->query('someString', 'too', 'many', 'parameters');

		$this->assertFalse($result);
	}

/**
 * Testing soap call via model with no parameters
 * 
 * @return void
 */
	public function testCallModelNoParams() {
		$this->config['soap_client_class'] = $this->soapClientClassName;

		$source = ConnectionManager::create('test_soap', $this->config);
		$model = ClassRegistry::init('ServiceModelTest');

		// No parameters
		$source->client->expects($this->once())
			->method('__soapCall')
			->with('someString', array(), null)
			->will($this->returnValue(true));

		$result = $model->someString();

		$this->assertFalse(!$result, 'someString returned false');
	}

/**
 * Testing soap call via model with one parameter
 * 
 * @return void
 */
	public function testCallModelOneParam() {
		$source = ConnectionManager::getDataSource('test_soap');
		$source->setSoapClientClass($this->soapClientClassName);
		$model = ClassRegistry::init('ServiceModelTest');

		// One parameter
		$params = new stdClass();
		$params->theString = 'string to split';
		$request = array('split' => $params);
		$source->client->expects($this->any())
			->method('__soapCall')
			->with('split', $request, null)
			->will($this->returnValue(true));

		$result = $model->split($request);

		$this->assertFalse(!$result, 'split returned false');
	}

/**
 * Testing soap call via model with two parameters
 * 
 * @return void
 */
	public function testCallModelTwoParams() {
		$source = ConnectionManager::getDataSource('test_soap');
		$source->setSoapClientClass($this->soapClientClassName);
		$model = ClassRegistry::init('ServiceModelTest');

		// Two parameters
		$params = new stdClass();
		$params->a = 1;
		$params->b = 2;
		$request = array('add' => $params);
		$source->client->expects($this->once())
			->method('__soapCall')
			->with('add', $request, null)
			->will($this->returnValue(true));

		$result = $model->add($request);

		$this->assertFalse(!$result, 'add returned false');
	}

/**
 * Testing overriding of soap action separator
 * 
 * Used when dealing with .NET web services
 * 
 * @return void
 */
	public function testSoapActionSeparator() {
		ConnectionManager::drop('test_soap');

		$this->config = array(
			'datasource' => 'Datasources.SoapSource',
			'wsdl' => null,
			'location' => 'http://soap.4s4c.com:80/ssss4c/soap.asp',
		    'uri' => 'demoService',
			'soapaction_separator' => '/',
			'soap_client_class' => $this->soapClientClassName
		);

		ConnectionManager::create('test_soap', $this->config);

		$model = ClassRegistry::init('ServiceModelTest');
		$source = ConnectionManager::getDataSource('test_soap');

		$source->client->expects($this->once())
			->method('__soapCall')
			->with('someString', array(), array('soapaction' => 'demoService/someString'))
			->will($this->returnValue(true));

		$result = $model->someString();

		$this->assertFalse(!$result, 'someString return false');
	}

/**
 * Testing supplying soap actions directly
 * 
 * @return void
 */
	public function testSoapOptions() {
		ConnectionManager::drop('test_soap');

		$this->config = array(
			'datasource' => 'Datasources.SoapSource',
			'wsdl' => null,
			'location' => 'http://soap.4s4c.com:80/ssss4c/soap.asp',
		    'uri' => 'demoService',
			'soap_client_class' => $this->soapClientClassName
		);

		ConnectionManager::create('test_soap', $this->config);

		$model = ClassRegistry::init('ServiceModelTest');
		$source = ConnectionManager::getDataSource('test_soap');

		$source->client->expects($this->once())
			->method('__soapCall')
			->with('someString', array(), array('soapaction' => 'demoService/someString'))
			->will($this->returnValue(true));

		$result = $model->someString(null, array('soapaction' => 'demoService/someString'));

		$this->assertFalse(!$result, 'someString return false');
	}

/**
 * Testing supplying soap header
 * 
 * @return void
 */
	public function testSoapHeaders() {
		ConnectionManager::drop('test_soap');

		$this->config = array(
			'datasource' => 'Datasources.SoapSource',
			'wsdl' => null,
			'location' => 'http://soap.4s4c.com:80/ssss4c/soap.asp',
		    'uri' => 'demoService',
			'soap_client_class' => $this->soapClientClassName,
			'headers' => array(
				'ns' => 'namespace',
				'container' => 'MyContainer'
			)
		);

		ConnectionManager::create('test_soap', $this->config);

		$model = ClassRegistry::init('ServiceModelTest');
		$source = ConnectionManager::getDataSource('test_soap');

		$source->client->expects($this->once())
			->method('__soapCall')
			->with('someString', array(), null, new SoapHeader('namespace', 'MyContainer', 'myvalue'))
			->will($this->returnValue(true));

		$result = $model->someString(null, null, 'myvalue');

		$this->assertFalse(!$result, 'someString return false');
	}

/**
 * Testing getting last request
 * 
 * Calls an actual soap service
 * 
 * @return void 
 */
	public function testGetRequest() {
		$config = array(
			'wsdl' => 'file://' . App::pluginPath('Datasources') . 'Test' . DS . 'File' . DS . 'GeoCoderPHP.wsdl'
		);
		$this->Soap = new SoapSource($config);

		$result = $this->Soap->query('geocode', array('location' => '1600 Pennsylvania Av, Washington, DC'));

		$this->assertEquals($result[0]->street, 'Pennsylvania');

		$this->assertEquals($this->Soap->getRequest(),
		'<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
		'<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://rpc.geocoder.us/Geo/Coder/US/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><SOAP-ENV:Body><ns1:geocode><location xsi:type="xsd:string">1600 Pennsylvania Av, Washington, DC</location></ns1:geocode></SOAP-ENV:Body></SOAP-ENV:Envelope>' . "\n");
	}

/**
 * Testing getting last response
 * 
 * Calls an actual soap service
 * 
 * @return void
 */
	public function testGetResponse() {
		$config = array(
			'wsdl' => 'file://' . App::pluginPath('Datasources') . 'Test' . DS . 'File' . DS . 'GeoCoderPHP.wsdl'
		);

		$this->Soap = new SoapSource($config);

		$result = $this->Soap->query('geocode', array('location' => '1600 Pennsylvania Av, Washington, DC'));

		$this->assertEquals($result[0]->street, 'Pennsylvania');

		$this->assertEquals($this->Soap->getResponse(),
		'<?xml version="1.0" encoding="UTF-8"?><soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:soapenc="http://schemas.xmlsoap.org/soap/encoding/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" soap:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><geocodeResponse xmlns="http://rpc.geocoder.us/Geo/Coder/US/"><geo:results soapenc:arrayType="geo:GeocoderAddressResult[1]" xsi:type="soapenc:Array" xmlns:geo="http://rpc.geocoder.us/Geo/Coder/US/"><geo:item xsi:type="geo:GeocoderAddressResult" xmlns:geo="http://rpc.geocoder.us/Geo/Coder/US/"><geo:number xsi:type="xsd:int">1600</geo:number><geo:lat xsi:type="xsd:float">38.898748</geo:lat><geo:street xsi:type="xsd:string">Pennsylvania</geo:street><geo:state xsi:type="xsd:string">DC</geo:state><geo:city xsi:type="xsd:string">Washington</geo:city><geo:zip xsi:type="xsd:int">20502</geo:zip><geo:suffix xsi:type="xsd:string">NW</geo:suffix><geo:long xsi:type="xsd:float">-77.037684</geo:long><geo:type xsi:type="xsd:string">Ave</geo:type><geo:prefix xsi:type="xsd:string" /></geo:item></geo:results></geocodeResponse></soap:Body></soap:Envelope>');
	}
}