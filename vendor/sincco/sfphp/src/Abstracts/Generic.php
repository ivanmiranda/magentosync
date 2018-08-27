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

use Sincco\Sfphp\Translations;
use Sincco\Sfphp\Logger;
use Sincco\Tools\Singleton;
use Sincco\Sfphp\Request;
use Sincco\Sfphp\Response;

/**
 * Clase abstracta para operaciones del framework
 */
abstract class Generic
{
	/**
	 * Devuelve un modelo
	 * @param  string $model Modelo a consumir
	 * @return object        Modelo
	 */
	public function getModel($model='')
	{
		$path = explode('\\', $model);
		array_push($path, $path[(count($path) - 1)]);
		$path[count($path) - 2] = 'Models';
		include_once(PATH_ROOT . '/app/' . implode('/', $path) . '.php');
		$class = $path[count($path) - 1]."Model";
		return Singleton::get($class);
	}

	/**
	 * Devuelve un helper
	 * @param  string $helper Helper a cargar
	 * @return object         Helper
	 */
	public function helper($helper='')
	{
		include_once(PATH_ROOT . '/app/Helpers/' . $helper . '.php');
		$class = $helper . "Helper";
		return Singleton::get($class);
	}

	/**
	 * Devuelve un parametro recibido en la petición
	 * @param  string $param Parametro a buscar
	 * @return mixed        Valor del parametro
	 */
	public function getParams($param = '')
	{
		return Request::getParams($param);
	}

	/**
	 * Escribe en el log de debug
	 * @param  string $data Mensaje
	 * @param  array $params Parametros
	 * @return none
	 */
	public function debug($data='', $params=[])
	{
		Logger::debug($data, $params);
	}

	/**
	 * Regresa la petición hecha desde el navegador
	 * @param  string $key Seccion a consultar
	 * @return array      Seccion
	 */
	public function getRequest($key = '')
	{
		return Request::get($key);
	}

	/**
	 * Crea una nueva respuesta al navegador
	 * @param  string $type Tipo de respuesta
	 * @param  mixed $data Datos en la respuesta
	 * @return none
	 */
	public function response($type='json', $data=[])
	{
		new Response($type, $data);
	}
}