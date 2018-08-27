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
# Ejecución de eventos según la petición realizada desde la línea de comandos
# -----------------------

namespace Sincco\Sfphp;

use Sincco\Sfphp\Request;
use Sincco\Sfphp\Config\Reader;

final class Plugger extends \stdClass {

	public static function dispatchGlobal($event, $function) {
		$objClass = ClassLoader::load([], 'Global' , 'Observer');
		if(is_callable(array($objClass, $function . '_' . $event))) {
			call_user_func(array($objClass, $function . '_' . $event));
		}
	}

	public static function dispatchAction($event, $function) {
		$objClass = ClassLoader::load([], 'Actions', 'Observer');
		if(is_callable(array($objClass, $function . '_' . $event))) {
			call_user_func(array($objClass, $function . '_' . $event));
		}
	}
}