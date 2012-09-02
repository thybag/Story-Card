<?php
/**
 * CardStore Driver
 * Alias's CardStore methods to data driver specified in config.
 *
 * @author Carl Saggs
 * @version 0.2
 * @licence MIT
 */
class CardStore{

	private static $store;

	public static function load($storeType){
		//If file cannot be loaded, handle error.
		if((include'config/drivers/data/'.$storeType.'.php')==false){
			echo '{"setup":true, "error":true, "message":"There doesn\'t appear to be an cardStore driver called <strong>'.$storeType.'</strong> in /config/drivers/data/. Are you sure you have typed it correctly?"}';
			die();
		}
		$class = $storeType.'Store';
		self::$store = new $class;
	}

	public static function getCardsFor($product,$sprint=0){
		return self::$store->getCardsFor($product,$sprint);
	}
	public static function addCard($data){
		return self::$store->addCard($data);
	}

	public static function updateCard($id,$data){
		return self::$store->updateCard($id,$data);
	}
	public static function updateCards($cards){
		return self::$store->updateCards($cards);
	}


	public static function removeCard($id){
		return self::$store->removeCard($id);
	}
	public static function setup(){
		return self::$store->setup();
	}
	public static function listProducts(){
		return self::$store->listProducts();
	}
	public static function addProduct($title,$data){
		return self::$store->addProduct($title,$data);
	}
	public static function addSprint($identifier,$data){
		return self::$store->addSprint($identifier,$data);
	}

}
	