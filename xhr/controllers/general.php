<?php

class Config{
	private static $data;
	public static function store($data){self::$data = $data;}
	public static function get($ref){return self::$data[$ref];}
	public static function set($ref,$val){self::$data[$ref]=$val;}
}
abstract class AuthAbstract
{
    // Force Extending class to define this method
    abstract public function login($username,$password);
    abstract public function logout();
}
abstract class StoreAbstract
{
	abstract public function getCardsFor($product,$sprint=0);
    abstract public function listProducts();
    abstract public function moveCard($id,$status);
    abstract public function addCard($data);
    abstract public function updateCard($id,$data);
    abstract public function removeCard($id);

    public function reMap($data,$toInternal=false){
        $map = Config::get("column_mappings");
        if($toInternal) $map = array_flip($map); 

        $newData = new stdClass;
        foreach($data as $attr=>$val){
            if(isset($map[$attr])) $newData->{$map[$attr]} = $val;
        }
        return $newData;
    }
}