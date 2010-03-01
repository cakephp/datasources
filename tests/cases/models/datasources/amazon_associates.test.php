<?php
App::import('Datsource', 'WebTechNick.AmazonAssociatesSource');
Mock::generatePartial('AmazonAssociatesSource', 'MockAmazonAssociatesSource', array('__request'));
class AmazonAssociatesTestCase extends CakeTestCase {
  var $Amazon = null;
  var $config = array(
	  'key' => 'PUBLICKEY',
	  'secret' => 'SECRETKEY',
	  'tag' => 'ASSID',
	  'locale' => 'com' //(ca,com,co,uk,de,fr,jp)
  );
  
  function startTest(){
    $this->Amazon = new MockAmazonAssociatesSource(null);
    $this->Amazon->config = $this->config;
  }
  
  function testFind(){
    $this->Amazon->expectOnce('__request');
    $this->Amazon->find('DVD', array('title' => 'harry'));
    
    $this->assertEqual('AWSECommerceService', $this->Amazon->query['Service']);
    $this->assertEqual('PUBLICKEY', $this->Amazon->query['AWSAccessKeyId']);
    $this->assertEqual('ASSID', $this->Amazon->query['AccociateTag']);
    $this->assertEqual('DVD', $this->Amazon->query['SearchIndex']);
    $this->assertEqual('2009-03-31', $this->Amazon->query['Version']);
    $this->assertEqual('harry', $this->Amazon->query['Title']);
    $this->assertEqual('ItemSearch', $this->Amazon->query['Operation']);
  }
  
  function testFindById(){
    $this->Amazon->expectOnce('__request');
    $this->Amazon->findById('ITEMID');
    
    $this->assertEqual('AWSECommerceService', $this->Amazon->query['Service']);
    $this->assertEqual('PUBLICKEY', $this->Amazon->query['AWSAccessKeyId']);
    $this->assertEqual('ASSID', $this->Amazon->query['AccociateTag']);
    $this->assertEqual('ItemLookup', $this->Amazon->query['Operation']);
    $this->assertEqual('2009-03-31', $this->Amazon->query['Version']);
    $this->assertEqual('ITEMID', $this->Amazon->query['ItemId']);
  }
  
  function testSignQuery(){
    $this->Amazon->query = array(
      'Service' => 'AWSECommerceService',
      'AWSAccessKeyId' => 'PUBLICKEY',
      'Timestamp' => '2010-03-01T07:44:03Z',
      'AccociateTag' => 'ASSID',
      'Version' => '2009-03-31',
      'Operation' => 'ItemSearch',
    );
    
    $results = $this->Amazon->__signQuery();
    $this->assertEqual('http://ecs.amazonaws.com/onca/xml?AWSAccessKeyId=PUBLICKEY&AccociateTag=ASSID&Operation=ItemSearch&Service=AWSECommerceService&Timestamp=2010-03-01T07%3A44%3A03Z&Version=2009-03-31&Signature=oEbqdS17pJmjRaSzbBX14zcnlprDbRlpDhQEvjo9mUA%3D', $results);
  }
  
  function endTest(){
    unset($this->Amazon);
  }
}
?>