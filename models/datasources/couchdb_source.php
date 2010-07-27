<?php
/**
 * Couchdb DataSource
 *
 * Usado para ler, escrever, atualiza e deletar documentos no Couchdb, atravé dos models.
 *
 * PHP Version 5.x
 * CAKEPHP Version 1.3.x
 *
 * Reference:
 * 	  gwoo couchsource datasource (http://bin.cakephp.org/view/925615535#modify)
 * 	  Trabalhando com o couchdb (http://www.botecounix.com.br/blog/?p=1375) 
 *
 * Copyright 2010, Maury M. Marques http://github.com/maurymmarques/ 
 *
 * Disponibilizado sob licença MIT.
 * Redistribuições dos arquivos devem manter a nota de copyright acima.
 * 
 * @package couchdb
 * @subpackage couchdb.models.datasources 
 * @filesource
 * @copyright Copyright 2010, Maury M. Marques http://github.com/maurymmarques/
 * @license http://www.opensource.org/licenses/mit-license.php A licença MIT
 * @author Maury M. Marques - maurymmarques@google.com
 */
App::import('Core', 'HttpSocket');
class CouchdbSource extends DataSource{

	/**
	 * Construtor.
	 *
	 * @param array $config Configuração de conexão com o couchdb.
	 * @param integer $autoConnect Auto conexão.
	 * @return boolean
	 * @access public
	 */
	public function __construct($config = null, $autoConnect = true){
		if(!isset($config['request'])){
			$config['request']['uri'] = $config;
		}
		parent::__construct($config);
		$this->fullDebug = Configure::read() > 1;

		if($autoConnect){
			return $this->connect();
		}else{
			return true;
		}
	}

	/**
	 * Reconecta ao servidor de dados com as novas configurações opcionais.
	 *
	 * @param array $config Define as novas configurações
	 * @return boolean true em sucesso, false em falha
	 * @access public
	 */
	public function reconnect($config = null){
		$this->disconnect();
		$this->setConfig($config);
		$this->_sources = null;

		return $this->connect();
	}

	/**
	 * Conecta ao banco de dados usando as opções do array determinado na configuração.
	 *
	 * @return boolean true se o banco estiver conectado, senão false
	 * @access public
	 */
	public function connect(){
		if($this->connected !== true){
			$this->Socket = new HttpSocket($this->config);
			if(strpos($this->Socket->get(), 'couchdb') !== false){
				$this->connected = true;
			}
		}
		return $this->connected;
	}

	/**
	 * Disconecta da base de dados, mata a conexão e informa que a conexão está fechada,
	 * e se o DEBUG estiver ligado(igual a 2) exibe o log dos dados armazenados.
	 *
	 * @return boolean true se a base de dados estiver desconectada, senão false
	 * @access public
	 */
	public function close(){
		if(Configure::read() > 1){//$this->showLog();
}
		$this->disconnect();
	}

	/**
	 * Disconecta da base de dados.
	 *
	 * @return boolean true se a base de dados estiver desconectada, senão false
	 * @access public
	 */
	public function disconnect(){
		if(isset($this->results) && is_resource($this->results)){
			$this->results = null;
		}
		$this->connected = false;
		return !$this->connected;
	}

	/**
	 * Lista de databases.
	 *
	 * @return array
	 * @access public
	 */
	public function listSources(){
		$databases = $this->decode($this->Socket->get($this->uri('_all_dbs')), true);
		return $databases;
	}

	/**
	 * Conveniência método para DboSource::listSources().
	 * Retorna os nomes das bases de dados em letras minúsculas.
	 *
	 * @return array
	 * @access public
	 */
	public function sources($reset = false){
		if($reset === true){
			$this->_sources = null;
		}
		return array_map('strtolower', $this->listSources());
	}

	/**
	 * Retorna uma descrição do model(metadados).
	 *
	 * @param Model $model
	 * @return array
	 * @access public
	 */
	public function describe($model){
		return $model->schema;
	}

	/**
	 * Cria um novo documento na base de dados.
	 * Se a primaryKey estiver declarada, cria o documento com o id específicado.
	 * Para criar uma nova database: $this->decode($this->Socket->put($this->uri('nomeDatabase')));
	 *
	 * @param Model $model
	 * @param array $fields Um array com os nomes dos campos para inserir. Se null, $model->data será utilizado para gerar os nomes dos campos.
	 * @param array $values Um array com valores chaves dos campos. Se null, $model->data será utilizado para gerar os nomes dos campos.
	 * @return boolean
	 * @access public
	 */
	public function create($model, $fields = null, $values = null){
		$data = $model->data;
		if($fields !== null && $values !== null){
			$data = array_combine($fields, $values);
		}

		$params = null;
		if(isset($data[$model->primaryKey]) && !empty($data[$model->primaryKey])){
			$params = $data[$model->primaryKey];
		}

		$result = $this->decode($this->Socket->post($this->uri($model, $params), $this->encode($data)));

		if($this->checkOk($result)){
			$model->id = $result->id;
			$model->rev = $result->rev;
			return true;
		}else{
			return false;
		}
	}

	/**
	 * Lê os dados de um documento.
	 *
	 * @param Model $model
	 * @param array $queryData um array de informações $queryData contendo as chaves, similar ao Model::find()
	 * @param integer $recursive Número do nível de associações
	 * @return mixed boolean false em erro/falha. Um array de resultados em secesso.
	 * @access public
	 */
	public function read($model, $queryData = array(), $recursive = null){
		if($recursive === null && isset($queryData['recursive'])){
			$recursive = $queryData['recursive'];
		}

		if(!is_null($recursive)){
			$model->recursive = $recursive;
		}

		$params = null;

		if(empty($queryData['conditions'])){
			$params = $params . '_all_docs?include_docs=true';
			if(!empty($queryData['limit'])){
				$params = $params . '&limit=' . $queryData['limit'];
			}
		}else{
			if(isset($queryData['conditions'][$model->alias . '.' . $model->primaryKey])){
				$params = $queryData['conditions'][$model->alias . '.' . $model->primaryKey];
			}else{
				$params = $queryData['conditions'][$model->primaryKey];
			}

			if($model->recursive > -1){
				$params = $params . '?revs_info=true';
			}
		}

		$result = array();
		$result[0][$model->alias] = $this->decode($this->Socket->get($this->uri($model, $params)), true);

		if(isset($result[0][$model->alias]['_id'])){
			if(isset($queryData['fields']) && $queryData['fields'] === true){
				$result[0][0]['count'] = 1;
			}

			$result[0][$model->alias]['id'] = $result[0][$model->alias]['_id'];
			$result[0][$model->alias]['rev'] = $result[0][$model->alias]['_rev'];

			unset($result[0][$model->alias]['_id']);
			unset($result[0][$model->alias]['_rev']);

			return $result;
		}else if(isset($result[0][$model->alias]['rows'])){
			$docs = array();
			foreach($result[0][$model->alias]['rows'] as $k => $doc){

				$docs[$k][$model->alias]['id'] = $doc['doc']['_id'];
				$docs[$k][$model->alias]['rev'] = $doc['doc']['_rev'];

				unset($doc['doc']['_id']);
				unset($doc['doc']['_rev']);
				unset($doc['doc']['id']);
				unset($doc['doc']['rev']);

				foreach($doc['doc'] as $field => $value){
					$docs[$k][$model->alias][$field] = $value;
				}
			}
			return $docs;
		}else{
			return false;
		}
	}

	/**
	 * Gera e executa uma instrução UPDATE para um determinado model, campos e valores.
	 *
	 * @param Model $model
	 * @param array $fields
	 * @param array $values
	 * @param mixed $conditions
	 * @return boolean true em sucesso, false em falha
	 * @access public
	 */
	public function update($model, $fields = null, $values = null, $conditions = null){
		$id = $model->id;
		$data = $model->data[$model->alias];
		if($fields !== null && $values !== null){
			$data = array_combine($fields, $values);
		}
		$data['_rev'] = $model->rev;
		if(!empty($id)){
			$result = $this->decode($this->Socket->put($this->uri($model, $id), $this->encode($data)));
			if($this->checkOk($result)){
				$model->rev = $result->rev;
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	/**
	 * Gera e executa uma instrução DELETE.
	 *
	 * @param Model $model
	 * @param mixed $conditions
	 * @return boolean true em sucesso, false em falha
	 * @access public
	 */
	public function delete($model, $conditions = null){
		$id = $model->id;
		$rev = $model->rev;

		if(!empty($id) && !empty($rev)){
			$id_rev = $id . '/?rev=' . $rev;
			$result = $this->decode($this->Socket->delete($this->uri($model, $id_rev)));
			return $this->checkOk($result);
		}
		return false;
	}

	/**
	 * Retorna uma instrução para contagem de dados. (em SQL, i.e. COUNT() ou MAX())
	 *
	 * @param model $model
	 * @param string $func Lowercase name of SQL function, i.e. 'count' or 'max'
	 * @param array $params Function parameters (any values must be quoted manually)
	 * @return string An SQL calculation function
	 * @access public
	 */
	public function calculate($model, $func, $params = array()){
		return true;
	}

	/**
	 * Obtém nome da tabela completa, incluindo o prefixo
	 *
	 * @param mixed $model
	 * @param boolean $quote
	 * @return string Nome da tabela completo
	 * @access public
	 */
	public function fullTableName($model = null, $quote = true){
		$table = null;
		if(is_object($model)){
			$table = $model->tablePrefix . $model->table;
		}elseif(isset($this->config['prefix'])){
			$table = $this->config['prefix'] . strval($model);
		}else{
			$table = strval($model);
		}
		return $table;
	}

	/**
	 * Pega a uri
	 *
	 * @param mixed $model
	 * @param string $params
	 * @return string uri
	 * @access private
	 */
	private function uri($model = null, $params = null){
		if(!is_null($params)){
			$params = '/' . $params;
		}
		return '/' . $this->fullTableName($model) . $params;
	}

	/**
	 * JSON encode
	 *
	 * @param string json $data
	 * @return string JSON
	 * @access private
	 */
	private function encode($data){
		return json_encode($data);
	}

	/**
	 * JSON decode
	 *
	 * @param string json $data
	 * @param boolean $assoc Se false retorna object, se true retorna array
	 * @return mixed object ou array
	 * @access private
	 */
	private function decode($data, $assoc = false){
		return json_decode($data, $assoc);
	}

	/**
	 * Verifica se o resultado retornou ok = true
	 *
	 * @param object $object
	 * @return boolean
	 * @access private
	 */
	private function checkOk($object = null){
		if(isset($object->ok) && $object->ok === true){
			return true;
		}else{
			return false;
		}
	}
}
?>