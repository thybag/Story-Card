<?php

class Auth{

	private static $auth;

	public static function load($auth_method){
		include('config/drivers/auth/'.$auth_method.'.php');
		self::$auth = new $auth_method;
	}

	public static function login($username, $password){
		return self::$auth->login($username, $password);
	}
	public static function logout($username){
		return self::$auth->logout($username);
	}
}
	