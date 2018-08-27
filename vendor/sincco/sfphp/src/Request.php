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

namespace Sincco\Sfphp;

use Sincco\Sfphp\Config\Reader;

final class Request extends \stdClass {

	private $data;
	private $params;

	private static $_instance;

# La estructura de una peticion es:
#   modulo/controlador/accion/[parametros]
	private function __construct() {
		$this->data = array();
		$this->params = array();

		$this->data['method'] = strtoupper(trim($_SERVER['REQUEST_METHOD']));

		$this->data['content_type'] = 'html';
		if(isset($_SERVER['CONTENT_TYPE'])) {
			$this->data['content_type'] = $_SERVER['CONTENT_TYPE'];
		}

		if(isset($_SERVER['HTTP_REFERER']))
			$this->data['previous'] = $_SERVER['HTTP_REFERER'];
		else
			$this->data['previous'] = NULL;

		//Soporte para Autorización
		if ($_SERVER['REQUEST_METHOD'] != 'cli') {
			if (function_exists('apache_request_headers')) {
				$headers = apache_request_headers();
			}
			if (function_exists('getallheaders')) {
				$headers = getallheaders();
			}
			if (isset($headers['Authorization'])){
				$this->data['authorization'] = $headers['Authorization'];
			}
			if (isset($headers['x-access-token'])){
				$this->data['authorization'] = $headers['x-access-token'];
			}
		}

		if (!isset($_GET['url'])) {
			$_GET['url'] = false;
		}
		$_url = explode('/', $_GET['url']);
		
		if (!strstr($_SERVER['SERVER_SOFTWARE'], 'Apache')) {
			array_shift($_url);
		}

		$_dirs = [];
		$intDirs = 0;
		foreach ($_url as $dir) {
			$_path = implode('/', $_dirs) . '/';
			if (is_dir(PATH_ROOT . "/app/" . $_path . ucwords($dir))) {
				$_dirs[] = ucwords($dir);
				$intDirs++;
			}
		}
		$_url = array_slice($_url, $intDirs);

		if (isset($_url[0])) {
			$_segments['controller'] = ucwords($_url[0]);
		} else {
			$_segments['controller'] = 'Index';
		}

		if (isset($_url[1])) {
			$_segments['action'] = ucwords($_url[1]);
		} else {
			$_segments['action'] = 'Index';
		}

		$this->data['url'] = $_GET['url'];
		$this->data['controller'] = $_segments['controller'];
		$this->data['action'] = $_segments['action'];
		$this->data['path'] = $_dirs;
		
		$this->params = self::procesaParametros($_url);
		$this->data["params"] = $this->params;

		if(array_key_exists('__clearCache', $this->params)) {
			$this->clearCache(PATH_CACHE);
			Reader::restart();
		}
	}

	public static function setRedirect($url) {
		self::$_instance->data = array();
		self::$_instance->params = array();

		$_segments = array('module', 'controller', 'action');

		self::$_instance->data['method'] = strtoupper(trim($_SERVER['REQUEST_METHOD']));

		self::$_instance->data['content_type'] = 'html';
		if(isset($_SERVER['CONTENT_TYPE'])) {
			self::$_instance->data['content_type'] = $_SERVER['CONTENT_TYPE'];
		}

		if(isset($_SERVER['HTTP_REFERER']))
			self::$_instance->data['previous'] = $_SERVER['HTTP_REFERER'];
		else
			self::$_instance->data['previous'] = NULL;

		if(!isset($url))
			$url = false;
		$_url = explode('/', $url);
		
		if(!strstr($_SERVER['SERVER_SOFTWARE'], 'Apache'))
			array_shift($_url);

		$_segmentsTemp = $_segments;
		$_urlTemp = $_url;
		if(array_shift($_segmentsTemp) == "module" && !is_dir(PATH_ROOT . "/app/" . ucwords(array_shift($_urlTemp))))
			array_shift($_segments);

		while (count($_segments) > 0) {
			self::$_instance->data['segments'][array_shift($_segments)] = ucwords(array_shift($_url));
		}

		if(!isset(self::$_instance->data['segments']['module'])) {
			self::$_instance->data['segments']['module'] = '';
		}
	}

	public static function redirect( $url ) {
		header('Location: ' . BASE_URL . $url);
	}

	private function clearCache($dir) {
		$files = glob($dir . '/*'); 
		foreach($files as $file){
			if (is_dir($file) and !in_array($file, array('..', '.')))  {
				$this->clearCache($file);
				rmdir($file);
			} else if(is_file($file) and ($file != __FILE__)) {
				unlink($file); 
			}
		}
	}

# Regresa la peticion
	public static function getInstance($segment = '') {
		if(!self::$_instance instanceof self)
			self::$_instance = new self();
		if(strlen(trim($segment)))
			return self::$_instance;
		else
			return self::$_instance;
	}

# Regresa los elementos de la petición
	public static function get($segment = '') {
		if(!self::$_instance instanceof self)
			self::$_instance = new self();
		if(strlen(trim($segment)))
			return self::$_instance->data[$segment];
		else
			return self::$_instance->data;
	}

# Regresa los parametros
	public static function getParams($atributo = '') {
		if(!self::$_instance instanceof self)
			self::$_instance = new self();
		if(strlen(trim($atributo)))
			return ( isset(self::$_instance->params[$atributo]) ? self::$_instance->params[$atributo] : NULL );
		else
			return self::$_instance->params;
	}

# Nombre del atributo a usarse en los __get __set
	private function nombreAtributo($atributo) {
		$atributo = str_replace("(", "", $atributo);
		$atributo = str_replace(")", "", $atributo);
		$atributo = "_".strtolower(substr($atributo, 3));
		return $atributo;
	}

# De los parametros recibidos se genera un arreglo único
	private function procesaParametros($segmentos) {
		$params = array();
	#GET
		foreach ($_GET as $key => $value) {
			if ($key != 'url') {
				$params[$key] = self::cleanGET($value);
			}
		}
	#FRAMEWORK
		foreach ($segmentos as $key => $value) {
			$segmentos[$key] = self::cleanGET($value);
		}
		while(count($segmentos)) {
			$params[array_shift($segmentos)] = array_shift($segmentos);
		}
	#POST
		$_contenido = file_get_contents("php://input");
		switch($this->data['content_type']) {
			case "application/json":
			case "application/json;":
			case "application/json; charset=UTF-8":
			if(trim($_contenido) != "") {
				foreach (json_decode($_contenido, TRUE) as $key => $value) {
					$params[$key] = self::cleanPOST($value);
				}
			}
			break;
			case "application/x-www-form-urlencoded":
				parse_str($_contenido, $postvars);
				foreach($postvars as $field => $value) {
					$params[$field] = self::cleanPOST($value);
				}
			break;
			default:
				parse_str($_contenido, $postvars);
				foreach($postvars as $field => $value) {
					$params[$field] = self::cleanPOST($value);
				}
			break;
		}
		return $params;
	}

	private function cleanGET($valor) {
		$_busquedas = array(
		'@<script[^>]*?>.*?</script>@si',   #Quitar javascript
		'@<[\/\!]*?[^<>]*?>@si',            #Quitar html
		'@<style[^>]*?>.*?</style>@siU',    #Quitar css
		'@<![\s\S]*?--[ \t\n\r]*>@'         #Quitar comentarios multilinea
		);
		if (is_array($valor)) {
			foreach ($valor as $_key => $_value)
				$valor[$_key] = self::cleanGET($_value); #Recursivo para arreglos
		}else {
			$valor = preg_replace($_busquedas, '', $valor);
			$valor = strip_tags($valor);
			$valor = filter_var($valor,FILTER_SANITIZE_STRING);
			if (get_magic_quotes_gpc())
				$valor = stripslashes($valor);
		}
		return $valor;
	}

	private function cleanPOST($valor) {
		$_busquedas = array(
		'@<script[^>]*?>.*?</script>@si',   #Quitar javascript
		'@<[\/\!]*?[^<>]*?>@si',            #Quitar html
		'@<style[^>]*?>.*?</style>@siU',    #Quitar css
		'@<![\s\S]*?--[ \t\n\r]*>@'         #Quitar comentarios multilinea
		);
		if (is_array($valor)) {
			foreach ($valor as $_key => $_value)
				$valor[$_key] = self::cleanPOST($_value); #Recursivo para arreglos
		}else {
			$valor = preg_replace($_busquedas, '', $valor);
			$valor = strip_tags($valor);
			$valor = filter_var($valor,FILTER_SANITIZE_STRING);
			if (get_magic_quotes_gpc())
				$valor = stripslashes($valor);	
		}
		return $valor;
	}

}

