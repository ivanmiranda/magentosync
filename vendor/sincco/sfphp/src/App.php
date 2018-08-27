<?php
# NOTICE OF LICENSE
#
# This source file is subject to the Open Software License (OSL 3.0)
# that is available through the world-wide-web at this URL:
# http://opensource.org/licenses/osl-3.0.php
#
# -----------------------
# @author: Iván Miranda (@deivanmiranda)
# @version: 2.0.0
# -----------------------

namespace Sincco\Sfphp;

use Sincco\Sfphp\Launcher;
use Sincco\Sfphp\Logger;
use Sincco\Sfphp\Paths;
use Sincco\Sfphp\Session;
use Sincco\Sfphp\Translations;
use Sincco\Sfphp\Config\Reader;


final class App extends \stdClass
{
	static public function run()
	{
		Logger::register();
		try {
			Paths::init();
			if (is_null(Reader::get('app'))) {
				throw new \Exception('No existe el archivo de configuración', 0);
			}
			if (!defined('APP_KEY')) {
				define('APP_KEY', 'e77393ef-c24b-4ff5-81f7-ed9fa28b4fb8');
			}
			if (!defined('APP_NAME')) {
				define('APP_NAME', 'sfphp');
			}
			Translations::init();
			Session::get();
			new Launcher();
		}catch (\Exception $err) {
			errorException(new \ErrorException($err, 0, 0, $err->getFile(), $err->getLine()));
		}
	}
}