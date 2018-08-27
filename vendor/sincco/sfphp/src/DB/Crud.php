<?php
# NOTICE OF LICENSE
#
# This source file is subject to the Open Software License (OSL 3.0)
# that is available through the world-wide-web at this URL:
# http://opensource.org/licenses/osl-3.0.php
#
# -----------------------
# @author: Iván Miranda
# @version: 2.0.0
# -----------------------
# Ejecución de eventos según la petición realizada desde el navegador
# -----------------------

namespace Sincco\Sfphp\DB;

use Sincco\Tools\Singleton;

class Crud extends \stdClass {
// PDO
	protected $_;
// Query segments
	private $table 	= NULL;
	private $where 	= array();
	private $joins	= array();
	private $order 	= array();
	private $fields = array();
// Params to be parsed
	private $params = array();
// Actual query
	private $query 	= NULL;

	/**
	 * Conecta con una base de datos
	 * @param  array  $data Datos de conexion
	 * @return none
	 */
	public function connect($data = array()) {
		$this->_ =  Singleton::get( 'Sincco\Sfphp\DB\DataManager', ['connectionData'=>$data], $data[ 'dbname' ] );
		$this->init();
	}

	/**
	 * Tabla principal
	 * @param  string $name Tabla
	 * @return object       Tabla
	 */
	public function __get( $name ) {
		$this->init();
		return call_user_func_array( array( $this, 'table' ), [$name] );
	}

	/**
	 * Query armado
	 * @return string Query SQL
	 */
	public function __toString() {
		return $this->generateSql();
	}

	/**
	 * Reinicia el objeto
	 * @return object Crud
	 */
	public function init() {
		$this->where 	= array();
		$this->joins	= array();
		$this->order 	= array();
		$this->fields 	= array();
		$this->params = array();
		$this->query 	= NULL;
		return $this;
	}

	/**
	 * Hace un instert en la tabla principal
	 * @param  array  $data  Datos a insertar
	 * @return int         Id insertado (si existe autonumerico)
	 */
	public function insert($data) {
		$campos = [];
		$variables = [];
		foreach ($data as $campo => $valor){
			$campos[] = $campo;
			$variables[] = ":" . $campo;
		}
		$campos		= implode(",", $campos);
		$variables	= implode(",", $variables);
		$query = 'INSERT INTO ' . $this->table . ' (' . $campos . ') VALUES (' . $variables . ')';
		return $this->_->query($query, $data);
	}

	/**
	 * Ejecuta un update en la tabla principal
	 * @param  array  $set   Campos a actualizar
	 * @param  array  $where Campos de condicion
	 * @return int         Respuesta
	 */
	public function update($set,$where) {
		$campos = [];
		$condicion = [];
		foreach ($set as $campo => $valor)
			$campos[] = $campo . "=:" . $campo;
		foreach ($where as $campo => $valor)
			$condicion[] = $campo . "=:" . $campo;
		$campos = implode(",", $campos);
		$condicion = implode(" AND ", $condicion);
		$query = 'UPDATE ' . $this->table . ' 
			SET ' . $campos . ' WHERE ' . $condicion;
		$parametros = array_merge($set, $where);
		return $this->_->query($query, $parametros);
	}

	public function table( $name ) {
		$this->params = array();
		$this->table = $name;
		return $this;
	}

	public function fields( $fields = array(), $table = 'maintable' ) {
		array_push( $this->fields, $table . '.' . $fields );
		return $this;
	}

	public function join( $table, $on, $type = 'INNER', $onType = 'ON') {
		array_push( $this->joins, serialize( array( $table, $on, $type, $onType ) ) );
		return $this;
	}

	/**
	 * Crea una sentencia WHERE
	 * @param  array $fields    Campos
	 * @param  array $values    Valores
	 * @param  string $logical   Comparacion logica
	 * @param  string $table     Sobreescribe tabla principal
	 * @param  string $condition Condicion logica AND|ORD
	 * @return object            CRUD
	 */
	public function where( $fields, $values, $logical = ' = ', $table = 'maintable', $condition = ' AND ' ) {
		array_push( $this->where, serialize( array( $fields, $values, $table, ' ' . $logical . ' ', $condition ) ) );
		array_push( $this->params, array( 'where' . $fields=>$values ) );
		return $this;
	}

	/**
	 * Crea una senhtencia ORDER
	 * @param  array $fields Campos
	 * @param  string $order  Orden
	 * @return object         CRUD
	 */
	public function order( $fields, $order = ' ASC ' ) {
		array_push( $this->order, serialize( array( $fields, $order ) ) );
		return $this;
	}

	/**
	 * Devuelve los datos en modo objeto
	 * @param  string $query  Sobreescribe el query
	 * @param  array  $params Parametros a parsear en el query
	 * @return object         Datos
	 */
	public function getCollection($query=NULL, $params=[]) {
		$data = $this->getData($query,$params);
		$result = [];
		foreach ( $data as $row ) {
			$object = new \stdClass();
			foreach ( $row as $key => $value ) {
				$object->$key = $value;
			}
			array_push( $result, $object );
		}
		return $result;
	}

	/**
	 * Devuelve los datos en modo array
	 * @param  string $query  Sobreescribe el query
	 * @param  array  $params Parametros a parsear en el query
	 * @return array         Datos
	 */
	public function getData($query=NULL, $params=[]) {
		if (!is_null($query)) {
			return $this->_->query( $query, $params );
		} else {
			$params = array();
			foreach ( $this->params as $param ) {
				foreach ( $param as $key => $value ) {
					$params[ $key ] = $value;
				}
			}
			$this->generateSql();
			return $this->_->query( $this->query, $params );
		}
	}

	/**
	 * Devuelve los datos en modo array sin campos
	 * @param  string $query  Sobreescribe el query
	 * @param  array  $params Parametros a parsear en el query
	 * @return array         Datos
	 */
	public function getSimpleData($query=NULL, $params=[]) {
		$data = $this->getData($query,$params);
		$simple = [];
		foreach ($data as $row) {
			$simple[] = array_values($row);
		}
		return $simple;
	}

	/**
	 * Create an SQL instruction w/all segments defined
	 * @return string
	 */
	private function generateSql() {
		$query = 'SELECT ';
		if( array_count_values( $this->fields) )
			$query .= implode( ', ', $this->fields );
		else
			$query .= '*';
	// FROM
		if( ! is_null( $this->table ) )
			$query .= ' FROM ' . $this->table . ' AS maintable ';
	// JOINS
		$joins = '';
		foreach ( $this->joins as $join ) {
			$join = unserialize( $join );
			$joins .= ' ' . $join[2] . ' JOIN ' . $join[0] . ' ' . $join[3] . ' (' . $join[1] . ') ';
		}
		if( strlen( trim( $joins ) ) )
			$query .= $joins;
	// WHERE
		$wheres = '';
		foreach ( $this->where as $where ) {
			$where = unserialize( $where );
			$wheres .= ( strlen( trim( $wheres ) ) ? $where[4] : '' ) . $where[2] . '.' . $where[0] . $where[3] . ':where' . $where[0];
		}
		if( strlen( trim( $wheres ) ) )
			$query .= ' WHERE ' . $wheres;
	// ORDER
		$orders = '';
		foreach ( $this->order as $order ) {
			$order = unserialize( $order );
			$orders .= $order[0] . $order[1];
		}
		if( strlen( trim( $orders ) ) )
			$query .= ' ORDER BY ' . $orders;
		$this->query = $query;
		return $query;
	}

	public function run($query, $params = []) {
		return $this->_->query( $query, $params );
	}
}