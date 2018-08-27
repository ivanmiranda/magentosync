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
# Simplifies the data explotarion with PDO
# -----------------------

namespace Sincco\Sfphp\DB;

use Sincco\Sfphp\DB\Connector;
use Sincco\Sfphp\Logger;
use Desarrolla2\Cache\Cache;
use Desarrolla2\Cache\Adapter\File;


class DataManager extends Connector {

	private $sQuery;
	private $settings;
	private $bConnected = false;
	private $log;
	private $parameters;
	private $connectionData;
	private $cache;

	private function Init($query, $parameters = "") {
		try {
			$this->sQuery = $this->prepare($query);
			
			$this->bindMore($parameters);
			
			if (!empty($this->parameters)) {
				foreach ($this->parameters as $param => $value) {
					
					$type = self::PARAM_STR;
					switch ($value[1]) {
						case is_int($value[1]):
							$type = self::PARAM_INT;
							break;
						case is_bool($value[1]):
							$type = self::PARAM_BOOL;
							break;
						case is_null($value[1]):
							$type = self::PARAM_NULL;
							break;
					}
					$this->sQuery->bindValue($value[0], $value[1], $type);
				}
			}
			$this->sQuery->execute();
		} catch (\PDOException $err) {
			Logger::error('Base de Datos', [$err, $err, $err->getFile(), $err->getLine()]);
			return false;
		}
		
		$this->parameters = array();
	}
	
	public function bind($para, $value) {
		$this->parameters[sizeof($this->parameters)] = [":" . $para , $value];
	}

	public function bindMore($parray) {
		if (empty($this->parameters) && is_array($parray)) {
			$columns = array_keys($parray);
			foreach ($columns as $i => &$column) {
				$this->bind($column, $parray[$column]);
			}
		}
	}
	
	public function query($query, $params = null, $fetchmode = self::FETCH_ASSOC) {
		$response = false;
		$query = trim(str_replace("\r", " ", $query));
		$idQuery = md5($query . serialize($params));
		$adapter = new File(PATH_CACHE);
		$cache = new Cache($adapter);
		$rawStatement = explode(" ", preg_replace("/\s+|\t+|\n+/", " ", $query));
		$statement = strtolower($rawStatement[0]);
		if (DEV_CACHE) {
			if (!is_null($cache->get('qry_' . $this->connectionData['type'].$idQuery))) {
				$reponse = $cache->get('qry_' . $this->connectionData['type'].$idQuery);
			}
		}
		if (!$response) {
			$this->Init($query, $params);
			switch ( $statement ) {
				case 'select':
				case 'show':
					$response = $this->sQuery->fetchAll( $fetchmode );
					break;
				case 'insert':
					$response = $this->insertId();
					break;
				case 'update':
				case 'delete':
					$response = $this->sQuery->rowCount();
				default:
					$response = NULL;
					break;
			}
		}
		if (DEV_CACHE) {
			$cache->set('qry_' . $this->connectionData['type'].$idQuery, $response);
		}
		return $response;
	}
	
	public function insertId() {
		return $this->lastInsertId();
	}
	
	public function beginTransaction() {
		return $this->beginTransaction();
	}
	
	public function executeTransaction() {
		return $this->commit();
	}
	
	public function rollBack() {
		return $this->rollBack();
	}
	
	public function column($query, $params = null) {
		$this->Init($query, $params);
		$Columns = $this->sQuery->fetchAll(self::FETCH_NUM);
		
		$column = null;
		
		foreach ($Columns as $cells) {
			$column[] = $cells[0];
		}
		
		return $column;
	}

	public function row($query, $params = null, $fetchmode = self::FETCH_ASSOC) {
		$this->Init($query, $params);
		$result = $this->sQuery->fetch($fetchmode);
		$this->sQuery->closeCursor(); // Frees up the connection to the server so that other SQL statements may be issued,
		return $result;
	}

	public function single($query, $params = null) {
		$this->Init($query, $params);
		$result = $this->sQuery->fetchColumn();
		$this->sQuery->closeCursor(); // Frees up the connection to the server so that other SQL statements may be issued
		return $result;
	}
	
	public function direct($query, $params = null) {
		try {
			$this->Init($query, $params);
			$result = $this->sQuery->fetchColumn();
			$this->sQuery->closeCursor(); // Frees up the connection to the server so that other SQL statements may be issued
			return $result;
		} catch (\PDOException $err) {
			Logger::error('Base de Datos', [$err, $err, $err->getFile(), $err->getLine()]);
		}
	}
}
