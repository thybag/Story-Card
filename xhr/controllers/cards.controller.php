<?php

class Cards {
	private $constraints;

	public function __construct(){
		CardStore::load(Config::get('datastore'));
		Auth::load(Config::get('auth_method'));

		$this->constraints = json_decode(file_get_contents("config/constraints.json"));
	}


	public function login(){
		$username = trim($_POST['username']);
		$password = trim($_POST['password']);

		
		if(Auth::login($username,$password)){
			$_SESSION['sc_auth'] = 1;
			$_SESSION['sc_username'] = $username;
			$_SESSION['sc_password'] = $password;
			echo 1;
		}else{
			echo 0;
		}

	}

	public function logout(){
		Auth::logout(); 
		//Kill sess vars
		unset($_SESSION['sc_auth']);
		unset($_SESSION['sc_username']);
		unset($_SESSION['sc_password']);
	}


	public function settings(){


		$data = array();
		$data['products'] = CardStore::listProducts();
		$data['constraints'] = $this->constraints;

		echo json_encode($data);

	}

	public function showlist(){
		
		$product = 'Mobile';
		$sprint = '0';
		if(isset($_GET['product'])){$product = $_GET['product'];}
		if(isset($_GET['sprint'])){$sprint = (int)$_GET['sprint'];}

		$data = CardStore::getCardsFor($product, $sprint);



		$sprint_list = array();
		foreach($data as $d){
			$t_sp = isset($d->sprint) ? $d->sprint : '0';
			if(!in_array($t_sp,$sprint_list) && $t_sp != 0) $sprint_list[] =round($t_sp,1);	
		}

		


		$json = array();

		$json['authed'] = isset($_SESSION['sc_auth']) ? 1 : 0;
		$json['authed_user'] = isset($_SESSION['sc_username']) ? $_SESSION['sc_username'] : 0;
		$json['sprints'] = $sprint_list;
		$json['data'] = $data;
		$json['loaded'] = time();

		echo json_encode($json);
	}

	public function move(){
		if(isset($_SESSION['sc_auth'])){
			$id = (int) $_POST['id'];
			$status =  $_POST['status'];
			
			if(CardStore::moveCard($id,$status) == true){
				$this->updateTimeStamp();
				$this->lastchange();
			}else{
				echo 0;
			}
		}else{
			echo 0;
		}
	}

	public function lastchange(){
		$file = Config::get('cache.dir').'/cards.timestamp.txt';
		$html = @file_get_contents($file);
		if($html !== false){
			echo $html;
		}else{
			$this->updateTimeStamp();
			echo time();
		}
	}

	public function updateCard(){
		$id = $_POST['id'];
		unset($_POST['id']);

		if(CardStore::updateCard($id, $_POST)){
			$this->updateTimeStamp();
			$this->lastchange();
		}else{
			echo 0;
		}
		

	}

	private function updateTimeStamp(){
		file_put_contents(Config::get('cache.dir').'/cards.timestamp.txt',time());
	}

}