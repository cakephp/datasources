<?php
/**
 * XML-RPC Datasource
 *
 * PHP versions 4 and 5
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @link          http://github.com/jrbasso/xmlrpc_datasource Project
 * @link          http://www.xmlrpc.com/spec Specification
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', array('HttpSocket', 'Xml'));

/**
 * XmlrpcSource
 *
 * Datasource for XML-RPC
 */
class XmlrpcSource extends Datasource {

/**
 * Version for this Data Source.
 *
 * @var string
 */
	var $version = '0.1';

/**
 * Description string for this Data Source.
 *
 * @var string
 */
	var $description = 'XmlRpc Datasource';

/**
 * HttpSocket Object
 *
 * @var object HttpSocket
 */
	var $HttpSocket = null;

/**
 * Configuration base
 *
 * @var array
 * @access private
 */
	var $_baseConfig = array(
		'host' => '127.0.0.1',
		'port' => 80,
		'url' => '/RPC2',
		'timeout' => 20
	);

/**
 * Constructor
 */
	function __construct($config = array()) {
		parent::__construct($config);
	}

/**
 * Perform a xmlrpc call
 *
 * @return mixed Response of XML-RPC Server. If return false, $this->error contain a error message.
 */
	function query() {
		$args = func_get_args();
		if (!isset($args[0]) || !is_string($args[0])) {
			return false;
		}
		$method = $args[0];
		unset($args[0]);
		return $this->_request($method, $args);
	}

/**
 * Perform a request via HTTP
 *
 * @param string $method Name of method
 * @param array $params List of methods
 * @return mixed Response of XML-RPC Server
 * @access private
 */
	function _request($method, $params) {
		$xmlRequest = $this->generateXML($method, $params);
		if (!$this->HttpSocket) {
			$this->HttpSocket =& new HttpSocket(array('timeout' => $this->config['timeout']));
		}
		$uri = array(
			'host' => $this->config['host'],
			'port' => $this->config['port'],
			'path' => $this->config['url']
		);
		$response = $this->HttpSocket->post($uri, $xmlRequest, array('header' => array('Content-Type' => 'text/xml')));
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
	function generateXML($method, $params = array()) {
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
		$xml = new Xml($query, array('format' => 'tags', 'tags' => array('methodCall' => array('name' => 'methodCall'))));
		return $xml->toString(array('cdata' => false, 'header' => true));
	}

/**
 * Parse a response from XML RPC Server
 *
 * @param string $response XML from Server
 * @return mixed Response as PHP
 */
	function parseResponse($response) {
		$xml = new Xml($response);
		$data = $xml->toArray(false);
		unset($xml);
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
 * @access private
 */
	function _normalizeParam($param) {
		if (is_array($param)) {
			if (empty($param) || isset($param[0])) { // Single consideration if is array or struct
				// Is array
				$data = array();
				foreach ($param as $item) {
					$normalized = $this->_normalizeParam($item);
					$data[] = $normalized['value'];
				}
				return array('value' => array('array' => array('data' => array('value' => $data))));
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
 * @access protected
 */
	function __parseResponseError(&$data) {
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
 * @access protected
 */
	function __parseResponse($value) {
		$type = array_keys($value);
		$type = $type[0];
		switch ($type) {
			case 'string':
				return (string)$value['string'];
			case 'int':
			case 'i4':
				return (int)$value[$type];
			case 'double':
				return (float)$value['double'];
			case 'boolean':
				return (bool)$value['boolean'];
			case 'array':
				$return = array();
				foreach ($value['array']['data']['value'] as $newValue) {
					$return[] = $this->__parseResponse($newValue);
				}
				return $return;
			case 'struct':
				$return = array();
				foreach ($value['struct']['member'] as $member) {
					$return[$member['name']] = $this->__parseResponse($member['value']);
				}
				return $return;
		}
		return null;
	}

/**
 * Set a error message and number
 *
 * @param integer $number Number of error
 * @param string $text Description of error
 * @return boolean Always false
 * @access private
 */
	function _error($number, $text) {
		$this->errno = $number;
		$this->error = $text;
		return false;
	}

}

?>