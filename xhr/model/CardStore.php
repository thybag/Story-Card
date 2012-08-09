<?php

class CardStore{

	private static $store;

	public static function load($storeType){
		include('config/drivers/data/'.$storeType.'.php');


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
	public static function removeCard($id){
		return self::$store->removeCard($id);
	}



	public static function moveCard($id,$status){
		return self::$store->moveCard($id,$status);
	}

	public static function listProducts(){
		return self::$store->listProducts();
	}






}
	