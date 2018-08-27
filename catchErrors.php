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

function errorFatal() {
	$error = error_get_last();
	if ($error["type"] == E_ERROR) {
		Sincco\Sfphp\Logger::error('FATAL', [$error["type"], $error["message"], $error["file"], $error["line"]]);
	}
}

function errorException($e) {
	Sincco\Sfphp\Logger::error('EXCEPTION', ['EXCEPTION', $e->getMessage(), $e->getFile(), $e->getLine()]);	
}

function error( $num, $str, $file, $line, $context = null ) {
	errorException( new ErrorException( $str, 0, $num, $file, $line ) );
}

function __($text){
	$translate = $text;
	if (defined('APP_TRANSLATE')) {
		$translate = \Sincco\Sfphp\Translations::get($translate, APP_TRANSLATE);
	}
	return $translate;
}