<?php
/**
 * SOAP Datasource
 *
 * PHP Version 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP Datasources v 0.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * SoapSource
 *
 */
class SoapSource extends DataSource {

/**
 * Description
 *
 * @var string
 */
	public $description = 'Soap Client DataSource';

/**
 * SoapClient instance
 *
 * @var SoapClient
 */
	public $client = null;

/**
 * Connection status
 *
 * @var boolean
 */
	public $connected = false;

/**
 * Default configuration
 *
 * @var array
 */
	protected $_baseConfig = array(
		'wsdl' => null,
		'location' => '',
		'uri' => '',
		'login' => '',
		'password' => '',
		'authentication' => 'SOAP_AUTHENTICATION_BASIC');

/**
 * Constructor
 *
 * @param array $config An array defining the configuration settings
 */
	public function __construct($config = array()) {
		parent::__construct($config);
		$this->connect();
	}

/**
 * Setup Configuration options
 *
 * @return array Configuration options
 */
	protected function _parseConfig() {
		if (!class_exists('SoapClient')) {
			$this->error = 'Class SoapClient not found, please enable Soap extensions';
			$this->showError();
			return false;
		}
		$options = array('trace' => Configure::read('debug') > 0);
		if (!empty($this->config['location'])) {
			$options['location'] = $this->config['location'];
		}
		if (!empty($this->config['uri'])) {
			$options['uri'] = $this->config['uri'];
		}
		if (!empty($this->config['login'])) {
			$options['login'] = $this->config['login'];
			$options['password'] = $this->config['password'];
			$options['authentication'] = $this->config['authentication'];
		}
		return $options;
	}

/**
 * Connects to the SOAP server using the WSDL in the configuration
 *
 * @param array $config An array defining the new configuration settings
 * @return boolean True on success, false on failure
 */
	public function connect() {
		$options = $this->_parseConfig();
		try {
			$this->client = new SoapClient($this->config['wsdl'], $options);
		} catch(SoapFault $fault) {
			$this->error = $fault->faultstring;
			$this->showError();
		}

		if ($this->client) {
			$this->connected = true;
		}
		return $this->connected;
	}

/**
 * Sets the SoapClient instance to null
 *
 * @return boolean True
 */
	public function close() {
		$this->client = null;
		$this->connected = false;
		return true;
	}

/**
 * Returns the available SOAP methods
 *
 * @return array List of SOAP methods
 */
	public function listSources($data = null) {
		return $this->client->__getFunctions();
	}

/**
 * Query the SOAP server with the given method and parameters
 *
 * @return mixed Returns the result on success, false on failure
 */
	public function query() {
		$this->error = false;
		if (!$this->connected) {
			return false;
		}

		$args = func_get_args();
		$method = null;
		$queryData = null;

		if (count($args) === 2) {
			$method = $args[0];
			$queryData = $args[1];
		} elseif (count($args) > 2 && !empty($args[1])) {
			$method = $args[0];
			$queryData = $args[1][0];
		} else {
			return false;
		}

		try {
			$result = $this->client->__soapCall($method, $queryData);
		} catch (SoapFault $fault) {
			$this->error = $fault->faultstring;
			$this->showError();
			return false;
		}
		return $result;
	}

/**
 * Returns the last SOAP response
 *
 * @return string The last SOAP response
 */
	public function getResponse() {
		return $this->client->__getLastResponse();
	}

/**
 * Returns the last SOAP request
 *
 * @return string The last SOAP request
 */
	public function getRequest() {
		return $this->client->__getLastRequest();
	}

/**
 * Shows an error message and outputs the SOAP result if passed
 *
 * @param string $result A SOAP result
 * @return string The last SOAP response
 */
	public function showError($result = null) {
		if (Configure::read('debug') > 0) {
			if ($this->error) {
				trigger_error('<span style = "color:Red;text-align:left"><b>SOAP Error:</b> ' . $this->error . '</span>', E_USER_WARNING);
			}
			if (!empty($result)) {
				echo sprintf("<p><b>Result:</b> %s </p>", $result);
			}
		}
	}
}
