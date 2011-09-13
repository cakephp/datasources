<?php
/**
 * XML-RPC Datasource
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
 * @link          http://www.xmlrpc.com/spec Specification
 * @package       datasources
 * @subpackage    datasources.models.datasources
 * @since         CakePHP Datasources v 0.1
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('DataSource', 'Model/Datasource');
App::uses('Xml', 'Utility');
App::uses('HttpSocket', 'Network/Http');

/**
 * XmlrpcSource
 *
 * Datasource for XML-RPC
 */
class XmlrpcSource extends DataSource {

/**
 * Description string for this Data Source.
 *
 * @var string
 */
	public $description = 'XmlRpc Datasource';

/**
 * HttpSocket Object
 *
 * @var object HttpSocket
 */
	public $HttpSocket = null;

/**
 * Cache for describe
 *
 * @var mixed Array with methods or false if not supported by server
 */
	protected $_cacheDescribe = null;

/**
 * Configuration base
 *
 * @var array
 */
	public $_baseConfig = array(
		'host' => '127.0.0.1',
		'port' => 80,
		'url' => '/RPC2',
		'timeout' => 20
	);

/**
 * Default Constructor
 *
 * @param array $config options
 */
	public function __construct($config = array()) {
		parent::__construct($config);
	}

/**
 * Checks if the source is connected.
 *
 * @return boolean
 */
	public function isConnected() {
		return true;
	}

/**
 * Perform a XML RPC call
 *
 * @param string $method XML-RPC method name
 * @param array $params List with XML-RPC parameters
 * @param Model $model Reference to model (unused)
 * @return mixed Response of XML-RPC Server. If return false, $this->error contain a error message.
 */
	public function query($method, $params = array(), &$model = null) {
		if (!is_string($method)) {
			return false;
		}
		return $this->_request($method, $params);
	}

/**
 * List supported methods by server.
 *
 * @param Model $model
 * @return mixed Array with methods or false if not supported
 */
	public function describe($model = null) {
		if (!is_null($this->_cacheDescribe)) {
			return $this->_cacheDescribe;
		}
		$this->_cacheDescribe = $this->query('system.listMethods');
		return $this->_cacheDescribe;
	}

/**
 * Perform a request via HTTP
 *
 * @param string $method Name of method
 * @param array $params List of methods
 * @return mixed Response of XML-RPC Server
 */
	protected function _request($method, $params) {
		$xmlRequest = $this->generateXML($method, $params);
		if (!$this->HttpSocket) {
			$this->HttpSocket =& new HttpSocket(array('timeout' => $this->config['timeout']));
		}
		$uri = array(
			'host' => $this->config['host'],
			'port' => $this->config['port'],
			'path' => $this->config['url']
		);
		try {
			$response = $this->HttpSocket->post($uri, $xmlRequest, array('header' => array('Content-Type' => 'text/xml')));			
		} catch (Exception $e) {
			return $this->_error(-32300, $e->getMessage());
		}
		if (!$this->HttpSocket->response['status']['code']) {
			return $this->_error(-32300, __('Transport error - could not open socket', true));
		}
		if ($this->HttpSocket->response['status']['code'] != 200) {
			return $this->_error(-32300, __('Transport error - HTTP status code was not 200', true));
		}

		return $this->parseResponse($response);
	}

/**
 * Generate a XML for request
 *
 * @param string $method Name of method
 * @param array $params List of methods
 * @return string XML of request
 */
	public function generateXML($method, $params = array()) {
		$query = array(
			'methodCall' => array(
				'methodName' => $method,
				'params' => array()
			)
		);
		if (!empty($params)) {
			$query['methodCall']['params']['param'] = array();
			foreach ($params as $param) {
				$query['methodCall']['params']['param'][] = $this->_normalizeParam($param);
			}
		}
		$Xml = Xml::fromArray($query, array('format' => 'tags'));
		return $Xml->asXml();
	}

/**
 * Parse a response from XML RPC Server
 *
 * @param string $response XML from Server
 * @return mixed Response as PHP
 */
	public function parseResponse($response) {
		$Xml = Xml::build(strval($response));
		$data = Xml::toArray($Xml);
		unset($Xml);
		if (isset($data['methodResponse']['fault'])) {
			return $this->__parseResponseError($data);
		}
		if (!isset($data['methodResponse']['params']['param']['value'])) {
			return $this->_error(-32700, __('Parse error. Not well formed', true));
		}
		$this->_error(0, '');
		return $this->__parseResponse($data['methodResponse']['params']['param']['value']);
	}

/**
 * Transform params in arrays to XML Class
 *
 * @param mixed $param Parameter
 * @return array Parameter to XML Class
 */
	protected function _normalizeParam($param) {
		if (is_array($param)) {
			if (empty($param) || isset($param[0])) { // Single consideration if is array or struct
				// Is array
				$data = array();
				foreach ($param as $item) {
					$normalized = $this->_normalizeParam($item);
					$data[] = $normalized['value'];
				}
				$return = array('value' => array('array' => array('data' => array())));
				if (!empty($data)) {
					$return['value']['array']['data']['value'] = $data;
				}
				return $return;
			}
			// Is struct
			$members = array();
			foreach ($param as $name => $value) {
				$members[] = array_merge(compact('name'), $this->_normalizeParam($value));
			}
			return array('value' => array('struct' => array('member' => $members)));
		} elseif (is_int($param)) {
			return array('value' => array('int' => $param));
		} elseif (is_bool($param)) {
			return array('value' => array('boolean' => $param ? '1' : '0'));
		} elseif (is_numeric($param)) {
			return array('value' => array('double' => $param));
		}
		return array('value' => array('string' => $param));
	}

/**
 * Parse a response if server response with error/fault
 *
 * @param array $data Response as array of XML Class
 * @return boolean Always false
 */
	private function __parseResponseError(&$data) {
		foreach ($data['methodResponse']['fault']['value']['struct']['member'] as $member) {
			if ($member['name'] === 'faultCode') {
				if (isset($member['value']['int'])) {
					$this->errno = (int)$member['value']['int'];
				} elseif (isset($member['value']['i4'])) {
					$this->errno = (int)$member['value']['i4'];
				}
			} elseif ($member['name'] === 'faultString' && isset($member['value']['string'])) {
				$this->error = $member['value']['string'];
			}
		}
		return false;
	}

/**
 * Parse a valid response from server
 *
 * @param array $value Value
 * @return mixed
 */
	private function __parseResponse($value) {
		$type = array_keys($value);
		$type = $type[0];
		$value = $value[$type];
		switch ($type) {
			case 'i4':
				return (int)$value;
			case 'double':
				return (float)$value;
			case 'array':
				$return = array();
				if (isset($value['data']['value']) && is_array($value['data']['value'])) {
					foreach ($value['data']['value'] as $key => $newValue) {
						// Reconstruct an array form, for arrays with only one entry.
						if (!is_array($newValue)) {
							$newValue = array($key => $newValue);
							$key = 0;
						}
						if ($key === 'struct' || $key === 'array') {
							$return[] = $this->__parseResponse(array($key => $newValue));
						} else {
							$return[] = $this->__parseResponse($newValue);
						}
					}
				}
				return $return;
			case 'struct':
				$return = array();
				foreach ($value['member'] as $member) {
					$return[$member['name']] = $this->__parseResponse($member['value']);
				}
				return $return;
			default:
				settype($value, $type);
				return $value;
		}
		return null;
	}

/**
 * Set a error message and number
 *
 * @param integer $number Number of error
 * @param string $text Description of error
 * @return boolean Always false
 */
	protected function _error($number, $text) {
		$this->errno = $number;
		$this->error = $text;
		return false;
	}
}
