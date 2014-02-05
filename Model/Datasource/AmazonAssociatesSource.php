<?php
/**
 * Amazon Associates API Datasource
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
 *
 * A CakePHP datasource for interacting with the amazon associates API.
 *
 * Create a datasource in your config/database.php
 *  public $amazon = array(
 *    'datasource' => 'Datasources.AmazonAssociatesSource',
 *    'key' => 'PUBLIC KEY',
 *    'secret' => 'SECRET KEY',
 *    'tag' => 'YOUR ASSOCIATE ID',
 *    'locale' => 'com' //(ca,com,co,uk,de,fr,jp)
 *  );
 */

/**
 * Import XML, required library
 *
 */
App::uses('Xml', 'Utility');
App::uses('DataSource', 'Model/Datasource');
App::uses('HttpSocket', 'Network/Http');

/**
 * Amazon Associates Datasource
 *
 */
class AmazonAssociatesSource extends DataSource {

/**
 * Description of datasource
 *
 * @var string
 */
	public $description = 'AmazonAssociates Data Source';

/**
 * Region / Locale
 * (ca,com,co,ul,de.fr,jp)
 *
 * @var string
 */
	public $region = 'com';

/**
 * Query array
 *
 * @var array
 */
	public $query = null;

/**
 * Signed request string to pass to Amazon
 *
 * @var string
 */
	protected $_request = null;

/**
 * HttpSocket object
 *
 * @var HttpSocket
 */
	public $Http = null;

/**
 * Request Logs
 *
 * @var array
 */
	protected $_requestLog = array();

/**
 * Constructor
 *
 * Creates new HttpSocket
 *
 * @param array $config Configuration array
 */
	public function __construct($config = array()) {
		parent::__construct($config);

		$this->Http = new HttpSocket();
	}

/**
 * Query the Amazon Database
 *
 * @param string $type (DVD, Book, Movie, etc...)
 * @param array $query (title search, etc...)
 * @return mixed array of the resulting request or false if unable to contact server
 */
	public function find($type = null, $query = array()) {
		if (!is_array($query)) {
			$query = array('Title' => $query);
		}
		if ($type) {
			$query['SearchIndex'] = $type;
		}
		foreach ($query as $key => $val) {
			if (preg_match('/^[a-z]/', $key)) {
				$query[Inflector::camelize($key)] = $val;
				unset($query[$key]);
			}
		}

		$this->query = array_merge(
			array(
				'Service' => 'AWSECommerceService',
				'AWSAccessKeyId' => $this->config['key'],
				'Timestamp' => gmdate("Y-m-d\TH:i:s\Z"),
				'AccociateTag' => $this->config['tag'],
				'Operation' => 'ItemSearch',
				'Version' => '2009-03-31',
			),
			$query
		);
		return $this->_request();
	}

/**
 * Find an item by it's ID
 *
 * @param string $id of amazon product
 * @return mixed array of the resulting request or false if unable to contact server
 */
	public function findById($id) {
		$this->query = array_merge(
			array(
				'Service' => 'AWSECommerceService',
				'AWSAccessKeyId' => $this->config['key'],
				'Timestamp' => gmdate("Y-m-d\TH:i:s\Z"),
				'AccociateTag' => $this->config['tag'],
				'Version' => '2009-03-31',
				'Operation' => 'ItemLookup',
			),
			array('ItemId' => $id)
		);
		return $this->_request();
	}

/**
 * Play nice with the DebugKit
 *
 * @param boolean sorted ignored
 * @param boolean clear will clear the log if set to true (default)
 * @return array of log requested
 */
	public function getLog($sorted = false, $clear = true) {
		$log = $this->_requestLog;
		if ($clear) {
			$this->_requestLog = array();
		}
		return array('log' => $log, 'count' => count($log), 'time' => 'Unknown');
	}

/**
 * Perform the request to AWS
 *
 * @return mixed array of the resulting request or false if unable to contact server
 */
	protected function _request() {
		$this->_request = $this->_signQuery();
		$this->_requestLog[] = $this->_request;
		$retval = $this->Http->get($this->_request);
		return Set::reverse(new Xml($retval));
	}

/**
 * Sign a query using sha256.
 * This is a required step for the new Amazon API
 *
 * @return string request signed string.
 */
	protected function _signQuery() {
		$method = 'GET';
		$host = 'ecs.amazonaws.' . $this->region;
		$uri = '/onca/xml';

		ksort($this->query);
		// create the canonicalized query
		$canonicalizedQuery = array();
		foreach ($this->query as $param => $value) {
			$param = str_replace('%7E', '~', rawurlencode($param));
			$value = str_replace('%7E', '~', rawurlencode($value));
			$canonicalizedQuery[] = $param . "=" . $value;
		}
		$canonicalizedQuery = implode('&', $canonicalizedQuery);
		$stringToSign = implode("\n", array($method, $host, $uri, $canonicalizedQuery));

		// calculate HMAC with SHA256 and base64-encoding
		$signature = base64_encode(hash_hmac("sha256", $stringToSign, $this->config['secret'], true));

		// encode the signature for the request
		$signature = str_replace('%7E', '~', rawurlencode($signature));

		// create request
		return sprintf('http://%s%s?%s&Signature=%s', $host, $uri, $canonicalizedQuery, $signature);
	}
}
