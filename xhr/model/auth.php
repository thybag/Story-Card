<?php
/**
 * Auth Driver
 * Alias's auth methods to auth driver specified in config.
 *
 * @author Carl Saggs
 * @version 0.2
 * @licence MIT
 */
class Auth{
	//auth provider
	private static $auth;
	//load auth driver
	public static function load($auth_method){
		//If file cannot be loaded, handle error.
		if((include'config/drivers/auth/'.$auth_method.'.php')==false){
			echo '{"setup":true, "error":true, "message":"There doesn\'t appear to be an auth driver called <strong>'.$auth_method.'</strong> in /config/drivers/auth/. Are you sure you have typed it correctly?"}';
			die();
		}
		self::$auth = new $auth_method;
	}
	//alias login
	public static function login($username, $password){
		return self::$auth->login($username, $password);
	}
	//alias logout
	public static function logout($username){
		return self::$auth->logout($username);
	}
}
	