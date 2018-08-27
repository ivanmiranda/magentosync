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

namespace Sincco\Sfphp\Abstracts;

use Sincco\Sfphp\Session;
use Sincco\Sfphp\Translations;
use Sincco\Tools\Tokenizer;
use Sincco\Sfphp\Request;
use Desarrolla2\Cache\Cache;
use Desarrolla2\Cache\Adapter\File;

/**
 * Control de vistas en el sistema
 */
final class View extends \stdClass {
	private $file;
	private $template;
	
	/**
	 * Crea la vista
	 * @param string $file Archivo a cargar
	 */
	public function __construct($file='')
	{
		$path = explode('\\', $file);
		array_push($path, $path[(count($path) - 1)]);
		$path[count($path) - 2] = 'Views';
		$this->file = array_pop($path) . '.html';
		$loader = new \Twig_Loader_Filesystem(PATH_ROOT . '/app/' . implode('/', $path));
		$params = array('cache' => PATH_CACHE);
		if(defined('DEV_CACHE'))
			$params = array();
		$this->template = new \Twig_Environment($loader, $params);
		$this->_twigFunctions();
	}

	/**
	 * Envía a navegador el resultado de una vista procesada
	 * @param  array  $params Parametros que forman parte del proceso de la vista
	 * @return none
	 */
	public function render($params = array())
	{
		echo $this->getContent($params);
	}

	/**
	 * Crea el contenido de una vista
	 * @param  array  $params Parametros que forman parte del proceso
	 * @return string         Contenido HTML de la vista
	 */
	public function getContent($params = array())
	{
		$_parsed = $params;
		$params['_session'] = $_SESSION;
		foreach (get_object_vars($this) as $key => $value) {
			$params[$key] = $value;
		}
		return $this->template->render($this->file, $params);
	}

	/**
	 * Define las funciones a usar en las plantillas
	 * @return none
	 */
	private function _twigFunctions() 
	{
		$function = new \Twig_SimpleFunction('__', function ($text) {
			return $this->_translate($text);
		});
		$this->template->addFunction($function);
		$function = new \Twig_SimpleFunction('token', function ($type) {
			return $this->_token($type);
		});
		$this->template->addFunction($function);
		$function = new \Twig_SimpleFunction('initJS', function () {
			return $this->_initJS();
		});
		$this->template->addFunction($function);
		$function = new \Twig_SimpleFunction('loadJS', function () {
			return $this->_loadJS();
		});
		$this->template->addFunction($function);
		$function = new \Twig_SimpleFunction('urlSection', function () {
			return $this->_urlSection();
		});
		$this->template->addFunction($function);
		$function = new \Twig_SimpleFunction('getUrl', function ($url) {
			return $this->_getUrl($url);
		});
		$this->template->addFunction($function);
	}

	/**
	 * Traducciones
	 * @param  string $text Cadena a traducir
	 * @return string       Cadena traducida
	 */
	private function _translate($text)
	{
		$translate = $text;
		if (defined('APP_TRANSLATE')) {
			$translate = Translations::get($text, APP_TRANSLATE);
		}
		return $translate;
	}

	/**
	 * Crea un token generico
	 * @return string 	Cadena de token
	 */
	private function _token()
	{
		$adapter = new File(PATH_CACHE);
		$adapter->setOption('ttl', 10800);
		$cache = new Cache($adapter);
		$token = $cache->get('token');
		if(is_null($token)) {
			$token = Tokenizer::create(['GENERIC_API'=>true], APP_KEY, 180);
			$cache->set('token', $token, 10800);
		}
		return $token;
	}

	/**
	 * Ejecuta la funcion init de cada script asociado a la vista
	 * @return string Cadena de ejecución de script
	 */
	private function _initJS() {
		$file = $this->file;
		$file = str_replace("Views/", "", $file);
		$file = str_replace("/", "_", $file);
		$file = strtolower($file);
		$file = str_replace(".html", "", $file);
		if (file_exists(PATH_ROOT . "/public/js/pages/" . $file . '.js')) {
			return '<script src="' . BASE_URL . "public/js/pages/" . $file . '.js"></script><script>' . $file . '.init();</script>';
		} else {
			return '';
		}
	}

	/**
	 * Carga un script JS asociado a la vista
	 * @return string Cadena de carga de JS
	 */
	private function _loadJS() {
		$file = $this->file;
		$file = str_replace("Views/", "", $file);
		$file = str_replace("/", "_", $file);
		$file = strtolower($file);
		$file = str_replace(".html", "", $file);
		if (file_exists(PATH_ROOT . "public/js/pages/" . $file . '.js')) {
			return BASE_URL . "public/js/pages/" . $file . '.js';
		} else {
			return "";
		}
	}

	/**
	 * Devuelve la sección de la URL
	 * @return string Seccion
	 */
	private function _urlSection() {
		$section = implode(" ", Request::get('path'));
		if (Request::get('controller') != "Index") {
			$section .= " " . Request::get('controller');
		}
		if (Request::get('action') != "Index") {
			$section .= " " . Request::get('action');
		}
		return strtoupper($section);
	}

	/**
	 * Obtiene una url absoluta en base a una relativa
	 * @param  strinf $url URL relativa
	 * @return string      URL absoluta
	 */
	private function _getUrl($url) {
		return BASE_URL . $url;
	}

}