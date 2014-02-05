<?php
/**
 * Amazon Associates DataSource Test file
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
 * Import Required libraries
 *
 */
App::uses('AmazonAssociatesSource', 'Datasources.Model/Datasource');

/**
 * Test datasource to test AmazonAssociatesSource::_signQuery();
 */
class AmazonAssociatesTestDataSource extends AmazonAssociatesSource {

/**
 * Public wrapper for the protected _signQuery method.
 *
 * @return string Url.
 */
	public function signQuery() {
		return parent::_signQuery();
	}

}

/**
 * AmazonAssociatesTestCase
 *
 */
class AmazonAssociatesTest extends CakeTestCase {

/**
 * Amazon Datasource object
 *
 * @var MockAmazonAssocatesSource
 */
	public $Amazon = null;

/**
 * Configuration
 *
 * @var array
 */
	public $config = array(
		'key' => 'PUBLICKEY',
		'secret' => 'SECRETKEY',
		'tag' => 'ASSID',
		'locale' => 'com' //(ca,com,co,uk,de,fr,jp)
	);

/**
 * Start Test
 *
 * @return void
 */
	public function startTest($method) {
		$this->Amazon = $this->getMock('AmazonAssociatesSource', array('_request'), array($this->config));
	}

/**
 * testFind
 *
 * @return void
 */
	public function testFind() {
		$this->Amazon->expects($this->any())
			->method('_request');
		$this->Amazon->find('DVD', array('title' => 'harry'));

		$this->assertEquals('AWSECommerceService', $this->Amazon->query['Service']);
		$this->assertEquals('PUBLICKEY', $this->Amazon->query['AWSAccessKeyId']);
		$this->assertEquals('ASSID', $this->Amazon->query['AccociateTag']);
		$this->assertEquals('DVD', $this->Amazon->query['SearchIndex']);
		$this->assertEquals('2009-03-31', $this->Amazon->query['Version']);
		$this->assertEquals('harry', $this->Amazon->query['Title']);
		$this->assertEquals('ItemSearch', $this->Amazon->query['Operation']);

		$this->Amazon->expects($this->any())
			->method('_request');
		$this->Amazon->find('DVD', 'harry');
		$this->assertEquals('AWSECommerceService', $this->Amazon->query['Service']);
		$this->assertEquals('PUBLICKEY', $this->Amazon->query['AWSAccessKeyId']);
		$this->assertEquals('ASSID', $this->Amazon->query['AccociateTag']);
		$this->assertEquals('DVD', $this->Amazon->query['SearchIndex']);
		$this->assertEquals('2009-03-31', $this->Amazon->query['Version']);
		$this->assertEquals('harry', $this->Amazon->query['Title']);
		$this->assertEquals('ItemSearch', $this->Amazon->query['Operation']);
	}

/**
 * testFindById
 *
 * @return void
 */
	public function testFindById() {
		$this->Amazon->expects($this->any())
			->method('_request');
		$this->Amazon->findById('ITEMID');

		$this->assertEquals('AWSECommerceService', $this->Amazon->query['Service']);
		$this->assertEquals('PUBLICKEY', $this->Amazon->query['AWSAccessKeyId']);
		$this->assertEquals('ASSID', $this->Amazon->query['AccociateTag']);
		$this->assertEquals('ItemLookup', $this->Amazon->query['Operation']);
		$this->assertEquals('2009-03-31', $this->Amazon->query['Version']);
		$this->assertEquals('ITEMID', $this->Amazon->query['ItemId']);
	}

/**
 * testSignQuery
 *
 * @return void
 */
	public function testSignQuery() {
		$query = array(
			'Service' => 'AWSECommerceService',
			'AWSAccessKeyId' => 'PUBLICKEY',
			'Timestamp' => '2010-03-01T07:44:03Z',
			'AccociateTag' => 'ASSID',
			'Version' => '2009-03-31',
			'Operation' => 'ItemSearch',
		);

		$Amazon = new AmazonAssociatesTestDataSource($this->config);
		$Amazon->query = $query;
		$expected = 'http://ecs.amazonaws.com/onca/xml?AWSAccessKeyId=PUBLICKEY&AccociateTag=ASSID&Operation=ItemSearch&Service=AWSECommerceService&Timestamp=2010-03-01T07%3A44%3A03Z&Version=2009-03-31&Signature=oEbqdS17pJmjRaSzbBX14zcnlprDbRlpDhQEvjo9mUA%3D';
		$this->assertEquals($expected, $Amazon->signQuery());
	}
/**
 * End Test
 *
 * @return void
 */
	public function endTest($method) {
		unset($this->Amazon);
	}
}
