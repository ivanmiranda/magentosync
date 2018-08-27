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

namespace Sincco\Sfphp;

final class Paths extends \stdClass {

	public static function init() {
		if(!defined('PATH_ERROR_TEMPLATE')) {
			define('PATH_ERROR_TEMPLATE', PATH_ROOT . '/errors');
			define('PATH_CACHE', PATH_ROOT . '/var/cache');
			define('PATH_LOGS', PATH_ROOT . '/var/log');
			define('PATH_CONFIG', PATH_ROOT . '/etc/config');
			define('PATH_SESSION', PATH_ROOT . '/var/session');
			define('PATH_TMP', PATH_ROOT . '/var/tmp');
			define('PATH_LOCALE', PATH_ROOT . '/etc/locale');
			if (file_exists(PATH_ROOT . '/paths.php')) {
				require_once PATH_ROOT . '/paths.php';
			}
		}
	}
}