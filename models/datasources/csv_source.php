<?php
/**
 * CSV class
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author Siegfried Hirsch <siegfried.hirsch@gmail.com>
 * @copyright Copyright 2008-2009, Siegfried Hirsch
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @created Januar 21, 2009
 * @version 1.0
 **/
class CsvSource extends DataSource {

/**
 * Description string for this Data Source.
 *
 * @var unknown_type
 */
    var $description = "CSV Data Source";
    var $delimiter = ';'; // delimiter between the columns
    var $maxCol = 0;
    var $fields = null; //fieldnames
    var $handle = false; // handle of the open csv file
    var $page = 1; // start always on the first page
    var $limit = 99999; // just to make the chunks not too big

/**
 * Default configuration.
 *
 * @var unknown_type
 */
	var $__baseConfig = array(
            'datasource' => 'csv',
			'path' => '.', // local path on the server relative to WWW_ROOT
            'extension' => 'csv', // file extension of CSV Files
            'readonly' => true, // only for reading
            'recursive' => false, // only false is supported at the moment
			);

/**
 * Constructor
 */
	function __construct($config = null, $autoConnect = true) {
        // Configure::write('debug', 1);
        $this->debug = Configure::read('debug') > 0;
        $this->fullDebug = Configure::read('debug') > 1;
        // debug($config);
        parent::__construct($config);

        if ($autoConnect) {
            return $this->connect();
        } else {
            return true;
        }
	}

/**
 * Connects to the mailbox using options in the given configuration array.
 *
 * @return boolean True if the mailbox could be connected, else false
 */
	function connect() {
		$config = $this->config;
		$this->connected = false;

        uses('Folder');

        if ($config['readonly']) {
            $create = false;
            $mode = 0;
        } else {
            $create = true;
            $mode = 0777;
        }

        $config['path'] = WWW_ROOT . $config['path'];
        // debug($config['path']);
        $this->connection = &new Folder($path = $config['path'], $create, $mode);
        if ($this->connection) {
            $this->handle = array();
            $this->connected = true;
        }
		return $this->connected;
	}


    /**
     * listSources
     *
     * @author: SHirsch
     * @created: 21.01.2009
     * @return array of available CSV files
     */
	function listSources() {
		$config = $this->config;

        if ($this->_sources !== null) {
            return $this->_sources;
        }

        if ($config['recursive']) {
            // not supported yet -> has to use Folder::findRecursive()
        } else {
            // list all .csv files and remove the extension to get only "tablenames"
            $list = $this->connection->find('.*'.$config['extension'], false);
            foreach ($list as &$l) {
                if (stripos($l,  '.'.$config['extension']) > 0) {
                    $l = str_ireplace('.'.$config['extension'],  '',  $l);
                }
            }
            $this->_sources = $list;
        }
        // debug($list);
        return $list;
    }
/**
 * Convenience method for DboSource::listSources().  Returns source names in lowercase.
 *
 * @return array
 */
    function sources($reset = false) {
        if ($reset === true) {
            $this->_sources = null;
        }
        return array_map('strtolower', $this->listSources());
    }

/**
 * Returns a Model description (metadata) or null if none found.
 *
 * @return mixed
 **/
    function describe($model) {
        // debug($model->table);
        $this->__getDescriptionFromFirstLine($model);
        // debug($this->fields);
        return $this->fields;
    }

    /**
     * __getDescriptionFromFirstLine and store into class variables
     *
     * @author: SHirsch
     * @created: 21.01.2009
     *
     * @param $model
     * @set CsvSource::fields array with fieldnames from the first line
     * @set CsvSource::delimiter char the delimiter of this CSV file
     *
     * @return true
     */
    private function __getDescriptionFromFirstLine($model) {
        $config = $this->config;
        $filename = $model->table . "." . $config['extension'];
        $handle = fopen ($config['path'] . DS .  $filename,"r");
        $line = rtrim(fgets($handle)); // remove \n\r
        $data_comma = explode(",",$line);
        $data_semicolon = explode(";",$line);

        if (count($data_comma) > count($data_semicolon)) {
            $this->delimiter = ',';
            $this->fields = $data_comma;
            $this->maxCol = count($data_comma);
        } else {
            $this->delimiter = ";";
            $this->fields = $data_semicolon;
            $this->maxCol = count($data_semicolon);
        }
        fclose($handle);
        return true;
    }

    /* close
    **
    ** @created: 21.01.2009 14:59:08
    **
    */
    function close()
    {
        if ($this->connected) {
            if ($this->handle) {
                foreach($this->handle as $h) {
                  @fclose($h);
                }
                $this->handle = false;
            }
            $this->connected = false;
        }
    }

/**
 * The "R" in CRUD
 *
 * @param Model $model
 * @param array $queryData
 * @param integer $recursive Number of levels of association
 * @return unknown
 */
    function read(&$model, $queryData = array(), $recursive = null) {
        $config = $this->config;
        $filename = $config['path'] . DS .  $model->table . "." . $config['extension'];
        if (!Set::extract($this->handle,$model->table)) {
            $this->handle[$model->table] = fopen($filename,  "r") ;
        } else {
          fseek($this->handle[$model->table], 0, SEEK_SET) ;
        }
        $queryData = $this->__scrubQueryData($queryData);

        // get the limit
        if (isset($queryData['limit']) && !empty($queryData['limit'])) {
            $this->limit = $queryData['limit'];
            // debug($this->limit);
        }

        // get the page#
        if (isset($queryData['page']) && !empty($queryData['page'])) {
            $this->page = $queryData['page'];
            // debug($this->page);
        }

        if (empty($queryData['fields'])) {
            $fields = $this->fields;
            $allFields = true;
        } else {
            $fields = $queryData['fields'];
            $allFields = false;
            $_fieldIndex = array();
            $index = 0;
            // generate an index array of all wanted fields
            foreach($this->fields as $field) {
                if (in_array($field,  $fields)) {
                    $_fieldIndex[] = $index;
                }
                $index++;
            }
        }

        $lineCount = 0;
        $recordCount = 0;
        $findCount = 0;
        $resultSet = array();

        // Daten werden aus der Datei in ein Array $data gelesen
        while ( ($data = fgetcsv ($this->handle[$model->table], 8192, $this->delimiter)) !== FALSE ) {
            if ($lineCount == 0) {
                // throw away the first line
                $lineCount++;
                continue;
                // $_page = 1;
            } else {
                // skip over records, that are not complete
                if (count($data) < $this->maxCol) {
                    $lineCount++;
                    continue;
                }

                $record = array();
                $i = 0;
                $record['id'] = $lineCount;
                foreach($this->fields as $field) {
                    $record[$field] = $data[$i++];
                }

                if ( $this->__checkConditions($record, $queryData['conditions']) ) {
                  // compute the virtual pagenumber
                  $_page = floor($findCount / $this->limit) + 1;
                  $lineCount++;
                  if ($this->page <= $_page) {
                    if (!$allFields) {
                      $record = array();
                        $record['id'] = $lineCount;
                        if (count($_fieldIndex) > 0) {
                            foreach($_fieldIndex as $i) {
                                $record[$this->fields[$i]] = $data[$i];
                            }
                        }
                    }
                    $resultSet[] = $record ;
                    $recordCount++;
                  }
                }
                unset($record);

                // now count every record
                $findCount++;

                // is our page filled with records, then stop
                if ($recordCount >= $this->limit) {
                    break;
                }
            }
        }

        
        if ($model->findQueryType === 'count') {
            return array(array(array('count' => count($resultSet)))) ;
        } else {
            return $resultSet;
        }
    }

/**
 * Private helper method to remove query metadata in given data array.
 *
 * @param array $data
 * @return array
 */
    function __scrubQueryData($data) {
        foreach (array('conditions', 'fields', 'joins', 'order', 'limit', 'offset', 'group') as $key) {
            if (!isset($data[$key]) || empty($data[$key])) {
                $data[$key] = array();
            }
        }
        return $data;
    }

/**
 * Private helper method to check conditions.
 *
 * @param array $record
 * @param array $conditions
 * @return bool
 */
    function __checkConditions($record, $conditions) 
    {
        $result = true ;
        foreach( $conditions as $name => $value ) {
            if ( strtolower($name) === 'or' ) {
                $cond = $value;
                $result = false ;
                foreach( $cond as $name => $value ) {
                    if ( Set::matches($this->__createRule($name, $value), $record) ) 
                        return true ;
                }
            } else {
                if ( !Set::matches($this->__createRule($name, $value), $record) ) 
                    return false ;
            }
        }
        return $result ;
    }
    
/**
 * Private helper method to crete rule.
 *
 * @param string $name
 * @param string $value
 * @return string
 */
    function __createRule($name, $value) 
    {
        if ( strpos($name,' ') !== false ) {
            return array(str_replace(' ','',$name).$value) ;
        } else {
            return array("{$name}={$value}") ;
        }
    }
  
    function calculate(&$model, $func, $params = array()) 
    {
        return array('count' => true);
    }
}

