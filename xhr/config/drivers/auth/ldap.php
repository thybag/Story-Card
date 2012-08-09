<?php
class Ldap extends AuthAbstract{

	public function login($username,$password){
		//Really really really basic ldap (not sure this will even work :p)
		$conn = ldap_connect(Config::get("ldap.host"), Config::get("ldap.port"));
		ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
		if(empty($password)) $password = " ";
		if(@ldap_bind($conn, "uid={$username},".Config::get("ldap.basedn"), $password)){
			return true;
		}else{
			return false;
		}
	}
	public function logout(){
		//La de dar
	}
}
	
		