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
# Clase para control de sesion en el framework
# -----------------------

namespace Sincco\Sfphp;

use \Sincco\Sfphp;
use \Sincco\Sfphp\Response;
use \Sincco\Sfphp\Config\Reader;

final class Session extends \stdClass {
    
    protected static $instance;

    protected function __construct() {
        $config = Reader::get();
        if (!isset($config['sesion'])) {
            $config['sesion']['type'] = 'DEFAULT';
            $config['sesion']['name'] = 'sfphp';
            $config['sesion']['ssl'] = 0;
            $config['sesion']['inactivity'] = 300;
        }
        $config = $config['sesion'];
        $httponly = true;
        $session_hash = 'sha512';
        if (in_array($session_hash, hash_algos()))
            ini_set('session.hash_function', $session_hash);
        ini_set('session.hash_bits_per_character', 5);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.gc_maxlifetime', $config['inactivity']);
        $cookieParams = session_get_cookie_params();
        if ($cookieParams["lifetime"] == 0)
            $cookieParams["lifetime"] = 28800; #Se mantiene una sesion activa hasta por 8 horas en el navegador
        session_set_cookie_params($cookieParams["lifetime"], $cookieParams["path"], $cookieParams["domain"], $config['ssl'], true); 
        session_save_path(PATH_SESSION);
        session_name($config['name']);
        session_start();
        $_cookie = ( isset( $_SERVER['HTTP_COOKIE'] ) ) ? $_SERVER['HTTP_COOKIE'] : "" ;
        if (trim($_cookie) == "")
            $_cookie = $config['name'] . "=" . session_id();
        if (is_null(self::get('sincco\sfphp\client\uuid'))) {
            self::set('sincco\sfphp\client\browser', $_SERVER['HTTP_USER_AGENT']);
            self::set('sincco\sfphp\client\address', $_SERVER['REMOTE_ADDR']);
            self::set('sincco\sfphp\client\uuid', md5(serialize(['address'=>$_SERVER['REMOTE_ADDR'], 'browser'=>$_SERVER['HTTP_USER_AGENT'], 'session_id'=>session_id()])));
        }
        $calculated = md5(serialize(['address'=>$_SERVER['REMOTE_ADDR'], 'browser'=>$_SERVER['HTTP_USER_AGENT'], 'session_id'=>session_id()]));
        if ($calculated !== self::get('sincco\sfphp\client\uuid')) {
            session_destroy();
            session_write_close();
            session_regenerate_id();
            new Response('htmlstatuscode', '403 Violación de seguridad');
        }
    }

    public static function get($section = '') {
        if (trim(session_id()) == "")
            self::$instance = new self();
        if (strlen(trim($section))) {
            if (isset($_SESSION[$section]))
                return trim(Crypt::decrypt($_SESSION[$section]));
            else
                return NULL;
        }
        else
            return $_SESSION;
    }

    public static function set($section, $valor) {
        if (trim(session_id()) == "")
            self::$instance = new self();
        $_SESSION[$section] = Crypt::encrypt($valor);
    }

    public static function del($section) {
        unset($_SESSION[$section]);
    }
}