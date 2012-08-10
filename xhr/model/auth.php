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
		include('config/drivers/auth/'.$auth_method.'.php');
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
	