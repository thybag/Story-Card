<?php
/**
 * LDAP Auth Driver
 * Implements the auth API used by StoryCard to allow users to authenticate against LDAP
 *
 * @author Carl Saggs
 * @version 0.2
 * @licence MIT
 */
class Ldap extends AuthAbstract{

	/**
	 * Login
	 * Validate that the username and password credentals of the user are correct.
	 *
	 * @param $username
	 * @param $password
	 * @return true|false Authentication success
	 */
	public function login($username,$password){
		//Connect to LDAP with server & port specified in config
		$conn = ldap_connect(Config::get("ldap.host"), Config::get("ldap.port"));
		ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);//Use V3 API
		//ensure password is not empty (avoid anonymous binds)
		if(empty($password)) $password = " ";
		//Attempt bind with user DN & pass
		if(@ldap_bind($conn, "uid={$username},".Config::get("ldap.basedn"), $password)){
			//Bind success means user credentals are correct
			return true;
		}else{
			//Else user failed to authenticate
			return false;
		}
	}
	
	/**
	 * logout
	 * No additional actions need to be taken.
	 */
	public function logout(){
		// ...
	}
}
	
		