<?php
/**
 * SingleUser Auth Driver
 * An ultra simple auth method to allow a single user to login & out using details specified in the config file.
 *
 * @author Carl Saggs
 * @version 0.2
 * @licence MIT
 */
class SingleUser extends AuthAbstract{

	/**
	 * Login
	 * Deterimine whether or not a users username & password are correct.
	 *
	 * @param $username
	 * @param $password
	 * @return true|false Authentication success
	 */
	public function login($username,$password){
		if($username==Config::get('singleuser.name') && $password==Config::get('singleuser.password')){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * logout
	 * No additional actions need to be taken.
	 */
	public function logout(){
		//...
	}
}
	
		