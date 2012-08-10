<?php 

class MysqlStore extends StoreAbstract{

	private $dbconn;

	public function __construct(){

		$dsn = 'mysql:dbname=storycard;host=localhost';
		$user = 'root';
		$password = '';

		try {
		    $this->dbconn = new PDO($dsn, $user, $password);
		} catch (PDOException $e) {
		    echo 'Connection failed: ' . $e->getMessage();
		}

	}	

	public function addCard($data){


	}
	public function updateCard($id,$data){


	}
	public function removeCard($id){

	}

	public function listProducts(){

		$q = $this->dbconn->prepare('select product from cards');
		$q->execute();
		
		$result = $q->fetchAll(PDO::FETCH_ASSOC);
		print_r($result);

		die();

	}

	public function getCardsFor($product,$sprint=0){
		
	}

	public function moveCard($id,$status){
		
	}

}