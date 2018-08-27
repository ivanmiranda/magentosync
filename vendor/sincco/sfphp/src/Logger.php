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

use Monolog\Logger as Monolog;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;
use Sincco\Tools\Singleton;
use League\CLImate\CLImate;

final class Logger extends \stdClass
{
	private static $instance;
	private $debug;
	private $error;

	private function __construct() {
		$firephp = new FirePHPHandler();
	// Debug
		$this->debug = new Monolog('sfphp_debug');
		$this->debug->pushHandler(new StreamHandler(PATH_LOGS . '/debug.log', Monolog::DEBUG));
		$this->debug->pushHandler($firephp);
	// Error
		$this->error = new Monolog('sfphp_error');
		$this->error->pushHandler(new StreamHandler(PATH_LOGS . '/error.log', Monolog::ERROR));
		$this->error->pushHandler($firephp);
	}

	public static function debug($message, $params = []) {
		if (!self::$instance instanceof self) {
			self::$instance = new self();
		}
		self::$instance->debug->debug($message, $params);
	}

	public static function error($message, $params = []) {
		if (!self::$instance instanceof self) {
			self::$instance = new self();
		}
		self::$instance->error->error($message, $params);
		if (DEV_SHOWERRORS) {
			if ($_SERVER['REQUEST_METHOD'] == 'cli') {
				self::console($message, $params);
			} else {
				self::html($message, $params);
			}
		}
	}

	public static function register() {
		ini_set('display_errors', true);
		set_error_handler('error');
		set_exception_handler('errorException');
		register_shutdown_function('errorFatal');
		if (!defined('DEV_SHOWERRORS')) {
			define('DEV_SHOWERRORS', false);
		}
	}

	public static function html($type, $params) {
		$style = '
			font-size: 0.75em;
			background: #eee;
			border: #ccc 1px solid;
			border-radius: 0.3em;
			padding: 0 1em;
			overflow: hidden;
			margin: 1em;';

		if ($type == 'EXCEPTION') {
			$style .= 'color: #00f;';
		} else {
			$style .= 'color: #f00;';
		}
		$message = str_replace('Stack trace', '<br>Stack trace', $params[1]);
		$html = '<div style="' . $style . '"><p><strong>' . $type . '</strong> ' . $message . ' in ' . $params[2] . ' line ' . $params[3] . '</p></div>';
		echo PHP_EOL . $html;
	}

	public static function console($type, $params) {
		$climate = new CLImate;
		$html = $type . ' ' . $params[1] . ' in ' . $params[2] . ' line ' . $params[3];
		$climate->red($html);
	}
}