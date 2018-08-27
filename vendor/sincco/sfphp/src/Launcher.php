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

use Sincco\Tools\Tokenizer;
use Sincco\Sfphp\Config\Reader;
use Sincco\Sfphp\Request;
use Sincco\Sfphp\Response;
use Sincco\Sfphp\Translations;
use Sincco\Sfphp\Plugger;
use Sincco\Sfphp\ClassLoader;

final class Launcher extends \stdClass {
	public function __construct() {
		$_config = Reader::get('app');

		if(isset($_config['timezone'])) {
			date_default_timezone_set($_config['timezone']);
		}

		Plugger::dispatchGlobal('pre', 'ResolveUrl');

		$observer = implode('_', Request::get('path')) . '_' . Request::get('controller') . '_' . Request::get('action');

		$path = Request::get('path');
		$action = Request::get('action');

		if (!isset($path[0])) {
			$path[0] = '';
		}

		if (strtolower($path[0]) == 'api') {
			$action = strtolower(Request::get('method')) . $action;
		}

		$objClass = ClassLoader::load(Request::get('path'), Request::get('controller'));
		if (is_callable(array($objClass, $action))) {
			Plugger::dispatchAction('pre', $observer);
			call_user_func(array($objClass, $action));
			Plugger::dispatchAction('post', $observer);
		}
		else {
			if (DEV_SHOWERRORS) {
				$segments = Request::get('path');
				$segments[] = Request::get('controller');
				$segments[] = Request::get('action');
				throw new \Exception('No es posible lanzar ' . Request::get('controller') . '->' . Request::get('action'), 0);
			} else {
				new Response('htmlstatuscode', '404 Not Found');
			}
		}
	}
}