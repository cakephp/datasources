<?php
/**
 * Couchdb DataSource Test file
 * PHP version 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link http://cakephp.org CakePHP(tm) Project
 * @package datasources
 * @subpackage datasources.models.datasources
 * @since CakePHP Datasources v 0.3
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Post Model for the test
 *
 * @package app
 * @subpackage app.model.post
 */
class Post extends AppModel {

	public $name = 'Post';
	public $useDbConfig = 'couchdb_test';
	public $displayField = 'title';
	public $recursive = -1;

	public $validate = array(
		'title' => array(
			'notempty' => array(
				'rule' => array('notempty'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
	);

	public $schema = array(
		'id' => array(
			'type' => 'string',
			'null' => true,
			'key' => 'primary',
			'length' => 32,
		),
		'rev' => array(
			'type' => 'string',
			'null' => true,
			'key' => 'primary',
			'length' => 34,
		),
		'title' => array(
			'type' => 'string',
			'null' => true,
			'length' => 255,
		),
		'description' => array(
			'type' => 'text',
			'null' => true,
		)
	);
}

/**
 * CouchdbTestCase
 *
 * @package       datasources
 * @subpackage    datasources.tests.cases.models.datasources
 */
class CouchdbTestCase extends CakeTestCase {

/**
 * CouchDB Datasource object
 *
 * @var object
 */
	public $Couchdb = null;

/**
 * Configuration
 *
 * @var array
 */
	protected $config = array(
		'datasource' => 'couchdb',
		'persistent' => false,
		'host' => 'localhost',
		'port' => '5984',
		'login' => '',
		'password' => '',
		'database' => null
	);

/**
 * Start Test
 *
 * @return void
 */
	public function startTest() {
		$config = new DATABASE_CONFIG();

		if (isset($config->couchdb_test)) {
			$this->config = $config->couchdb_test;
		}

		ConnectionManager::create('couchdb_test', $this->config);

		$this->Post = ClassRegistry::init('Post');
		$this->removeAllDocuments();
	}

/**
 * testConnection
 *
 * @return void
 */
	public function testConnection() {
		$this->Couchdb = new CouchdbSource($this->config);
		$this->Couchdb =& ConnectionManager::getDataSource($this->Post->useDbConfig);

		$reconnect = $this->Couchdb->reconnect($this->config);
		$this->assertTrue($reconnect);

		$disconnect = $this->Couchdb->disconnect();
		$this->assertTrue($disconnect);
	}

/**
 * testFind
 *
 * @return void
 */
	public function testFind() {
		$data = array(
			'title' => 'My first post',
			'description' => 'My first post'
		);
		$this->Post->save($data);

		$result = $this->Post->find('all');
		$this->assertEqual(1, count($result));

		$resultData = $result[0]['Post'];
		$this->assertEqual(4, count($resultData));
		$this->assertTrue(!empty($resultData['id']));
		$this->assertEqual($this->Post->id, $resultData['id']);
		$this->assertEqual($this->Post->rev, $resultData['rev']);
		$this->assertEqual($data['title'], $resultData['title']);
		$this->assertEqual($data['description'], $resultData['description']);
	}


/**
 * testFindConditions
 *
 * @return void
 */
	public function testFindConditions() {
		$data = array(
			'title' => 'My first post',
			'description' => 'My first post'
		);
		$this->Post->save($data);

		$this->Post->create();
		$this->Post->save($data);

		$result = $this->Post->find('all');
		$this->assertEqual(2, count($result));

		$result = $this->Post->find('all', array('conditions' => array('Post.id' => $this->Post->id)));
		$this->assertEqual(1, count($result));

		$result = $this->Post->find('all', array('conditions' => array('id' => $this->Post->id)));
		$this->assertEqual(1, count($result));
	}

/**
 * testFindRevs
 *
 * @return void
 */
	public function testFindRevs() {
		$data = array(
			'title' => 'My first post',
			'description' => 'My first post'
		);
		$this->Post->save($data);
		$this->Post->save($data);

		$this->Post->recursive = 0;
		$result = $this->Post->find('all', array('conditions' => array('id' => $this->Post->id)));
		$this->assertEqual(2, count($result[0]['Post']['_revs_info']));
	}

/**
 * Tests save method.
 *
 * @return void
 */
	public function testSave() {
		$data = array(
			'title' => 'My first post',
			'description' => 'My first post'
		);

		$this->Post->create();
		$saveResult = $this->Post->save($data);
		$this->assertTrue($saveResult);

		$result = $this->Post->find('all');
		$this->assertEqual(1, count($result));

		$resultData = $result[0]['Post'];
		$this->assertEqual(4, count($resultData));
		$this->assertTrue(!empty($resultData['id']));
		$this->assertEqual($this->Post->id, $resultData['id']);
		$this->assertEqual($this->Post->rev, $resultData['rev']);
		$this->assertEqual($data['title'], $resultData['title']);
		$this->assertEqual($data['description'], $resultData['description']);
	}

/**
 * Tests save method.
 *
 * @return void
 */
	public function testSaveWithId() {
		$data = array(
			'id' => String::uuid(),
			'title' => 'My first post',
			'description' => 'My first post'
		);

		$this->Post->create();
		$saveResult = $this->Post->save($data);
		$this->assertTrue($saveResult);

		$result = $this->Post->find('all');
		$this->assertEqual(1, count($result));

		$resultData = $result[0]['Post'];
		$this->assertEqual(4, count($resultData));
		$this->assertTrue(!empty($resultData['id']));
		$this->assertEqual($resultData['id'], $data['id']);
		$this->assertEqual($this->Post->id, $resultData['id']);
		$this->assertEqual($this->Post->rev, $resultData['rev']);
		$this->assertEqual($data['title'], $resultData['title']);
		$this->assertEqual($data['description'], $resultData['description']);
	}

/**
 * Tests saveAll method.
 *
 * @return void
 */
	public function testSaveAll() {
		$data[0]['Post'] = array(
			'title' => 'My first post',
			'description' => 'My first post'
		);

		$data[1]['Post'] = array(
			'title' => 'My second post',
			'description' => 'My second post'
		);

		$this->Post->create();
		$saveResult = $this->Post->saveAll($data);

		$result = $this->Post->find('all');
		$this->assertEqual(2, count($result));

		$resultData = $result[0]['Post'];
		$this->assertEqual(4, count($resultData));
		$this->assertTrue(!empty($resultData['id']));
		$this->assertEqual($data[0]['Post']['title'], $resultData['title']);
		$this->assertEqual($data[0]['Post']['description'], $resultData['description']);

		$resultData = $result[1]['Post'];
		$this->assertEqual(4, count($resultData));
		$this->assertTrue(!empty($resultData['id']));
		$this->assertEqual($data[1]['Post']['title'], $resultData['title']);
		$this->assertEqual($data[1]['Post']['description'], $resultData['description']);
	}

/**
 * Tests update method.
 *
 * @return void
 */
	public function testUpdate() {
		// Count posts
		$uri = '/posts/_temp_view?group=true';
		$post = array(
			'map' => 'function(doc) { emit(doc._id,1); }',
			'reduce' => 'function(keys, values) { return sum(values); }'
		);

		$mapReduce = $this->Post->query($uri, $post);
		if(isset($mapReduce->rows[0]->value)) $count0 = $mapReduce->rows[0]->value;
		else $count0 = 0;

		$count1 = $this->testUpdate1($uri, $post, $count0);
		$count2 = $this->testUpdate2($uri, $post, $count1);
		$count3 = $this->testUpdate3($uri, $post, $count2);
		$count4 = $this->testUpdate4($uri, $post, $count2);
		$updateData = $this->testUpdate5($uri, $post, $count4);

		// Final test
		$result = $this->Post->find('all');
		$this->assertEqual(1, count($result));

		$resultData = $result[0]['Post'];
		$this->assertEqual(4, count($resultData));
		$this->assertTrue(!empty($resultData['id']));
		$this->assertEqual($this->Post->id, $resultData['id']);
		$this->assertEqual($this->Post->rev, $resultData['rev']);
		$this->assertNotEqual($updateData['title'], $resultData['title']);
		$this->assertNotEqual($updateData['description'], $resultData['description']);

	}

/**
 * Tests update1 method.
 *
 * @param string $uri
 * @param array $post
 * @param interger $previousCount
 * @return void
 */
	private function testUpdate1($uri, $post, $previousCount) {
		$data = array(
			'title' => 'My first post',
			'description' => 'My first post'
		);

		$this->Post->create();
		$saveResult = $this->Post->save($data);
		$this->assertTrue($saveResult);
		$this->assertTrue($this->Post->id);

		$mapReduce = $this->Post->query($uri, $post);
		$count1 = $mapReduce->rows[0]->value;

		$this->assertIdentical($count1 - $previousCount, 1);

		return $count1;
	}

/**
 * Tests update2 method.
 *
 * @param string $uri
 * @param array $post
 * @param interger $previousCount
 * @return void
 */
	private function testUpdate2($uri, $post, $previousCount) {
		$findResult = $this->Post->find('first');
		$this->assertEqual(4, count($findResult['Post']));

		$updateData = array(
			'title' => 'My post update',
			'description' => 'My post update'
		);

		$this->Post->id = $findResult['Post']['id'];
		$this->Post->rev = $findResult['Post']['rev'];
		$saveResult = $this->Post->save($updateData);
		$this->assertTrue($saveResult);

		$mapReduce = $this->Post->query($uri, $post);
		$count2 = $mapReduce->rows[0]->value;

		$this->assertIdentical($count2 - $previousCount, 0);

		return $count2;
	}

/**
 * Tests update3 method.
 *
 * @param string $uri
 * @param array $post
 * @param interger $previousCount
 * @return void
 */
	private function testUpdate3($uri, $post, $previousCount) {
		$findResult = $this->Post->find('first');
		$this->assertEqual(4, count($findResult['Post']));

		$updateData = array(
			'id' => $findResult['Post']['id'],
			'title' => 'My post update',
			'description' => 'My post update'
		);

		$this->Post->rev = $findResult['Post']['rev'];
		$saveResult = $this->Post->save($updateData);
		$this->assertTrue($saveResult);
		$this->assertIdentical($this->Post->id, $findResult['Post']['id']);

		$mapReduce = $this->Post->query($uri, $post);
		$count3 = $mapReduce->rows[0]->value;

		$this->assertIdentical($count3 - $previousCount, 0);

		return $count3;
	}

/**
 * Tests update4 method.
 *
 * @param string $uri
 * @param array $post
 * @param interger $previousCount
 * @return void
 */
	private function testUpdate4($uri, $post, $previousCount) {
		$findResult = $this->Post->find('first');
		$this->assertEqual(4, count($findResult['Post']));

		$updateData = array(
			'id' => $findResult['Post']['id'],
			'rev' => $findResult['Post']['rev'],
			'title' => 'My post update',
			'description' => 'My post update'
		);

		$saveResult = $this->Post->save($updateData);
		$this->assertTrue($saveResult);
		$this->assertIdentical($this->Post->id, $findResult['Post']['id']);

		$mapReduce = $this->Post->query($uri, $post);
		$count4 = $mapReduce->rows[0]->value;

		$this->assertIdentical($count4 - $previousCount, 0);

		return $count4;
	}

/**
 * Tests update5 method.
 *
 * @param string $uri
 * @param array $post
 * @param interger $previousCount
 * @return void
 */
	private function testUpdate5($uri, $post, $previousCount) {
		$findResult = $this->Post->find('first');
		$this->assertEqual(4, count($findResult['Post']));

		$updateData = array(
			'id' => $findResult['Post']['id'],
			'rev' => 'whatever',
			'title' => 'My post fail',
			'description' => 'My post fail'
		);

		$saveResult = $this->Post->save($updateData);
		$this->assertFalse($saveResult);
		$this->assertIdentical($this->Post->id, $findResult['Post']['id']);

		$mapReduce = $this->Post->query($uri, $post);
		$count5 = $mapReduce->rows[0]->value;

		$this->assertIdentical($count5 - $previousCount, 0);

		return $updateData;
	}

/**
 * Tests delete method.
 *
 * @return void
 */
	public function testDelete() {
		$data = array(
			'title' => 'My first post',
			'description' => 'My first post'
		);

		$this->Post->create();
		$saveResult = $this->Post->save($data);

		$result = $this->Post->find('all');
		$this->assertEqual(1, count($result));

		$this->Post->id = $result[0]['Post']['id'];
		$this->Post->rev = $result[0]['Post']['rev'];
		$this->Post->delete();

		$result = $this->Post->find('all');
		$this->assertEqual(0, count($result));
	}

/**
 * Remove all documents from database
 *
 * @return void
 */
	private function removeAllDocuments() {
		$posts = $this->Post->find('list', array('fields' => array('Post.rev')));
		foreach($posts as $id => $post) {
			$this->Post->rev = $post;
			$this->Post->delete($id);
		}
	}

/**
 * End Test
 *
 * @return void
 */
	public function endTest() {
		$this->removeAllDocuments();
		unset($this->Post);
		unset($this->Couchdb);
		ClassRegistry::flush();
	}
}
