<?php

App::import('Core', array('HttpSocket', 'Xml'));

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

	function parseResponse($response) {
		$xml = new Xml($response);
		$data = $xml->toArray(false);
		unset($xml);
		if (isset($data['methodResponse']['fault'])) {
			return $this->__parseResponseError($data);
		}
		if (!isset($data['methodResponse']['params']['param']['value'])) {
			$this->errno = -32700;
			$this->error = 'parse error. not well formed';
			return false;
		}
		return $this->__parseResponse($data['methodResponse']['params']['param']['value']);
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
}

?>