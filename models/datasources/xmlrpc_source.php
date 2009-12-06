<?php

App::import('Core', array('HttpSocket', 'Xml', 'Datasource'));

class XmlrpcSource extends Datasource {

	var $version = '0.1';
	var $description = 'XmlRpc Datasource';
	var $_baseConfig = array(
		'host' => '127.0.0.1',
		'port' => 80,
		'url' => '/RPC2',
		'timeout' => 20
	);

	function __construct($config = array()) {
		$config = array_merge($this->_baseConfig, (array)$config);
		parent::__construct($config);
	}

	function query() {
		$args = func_get_args();
		if (!isset($args[0])) {
			return false;
		}
		$method = $args[0];
		unset($args[0]);
		return $this->_request($method, $args);
	}

	function _request($method, $params) {
		$xmlRequest = $this->generateXML($method, $params);
		// @todo Make a request to server
	}

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

}

?>