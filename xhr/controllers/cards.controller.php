<?php
/**
 * StoryCard Card Controller
 * Defines functionalty for methods called via javascript.
 *
 * @author Carl Saggs
 * @version 0.2
 * @licence MIT
 */
class Cards {
	//constraints
	private $workflow;

	//Setup controller by initiating CardStore and Auth providers.
	public function __construct(){
		CardStore::load(Config::get('datastore'));
		Auth::load(Config::get('auth_method'));
		//Load in workflow config
		$this->workflow = json_decode(file_get_contents("config/workflow.json"));
	}

	/**
	 * login 
	 * Allow a user to login to the system. User credentals are validated using the auth driver.
	 * @return 1|0
	 */
	public function login(){
		//get username & password
		$username = trim($_POST['username']);
		$password = trim($_POST['password']);
		//Validate against auth driver.
		if(Auth::login($username,$password)){
			//Setup session
			$_SESSION['sc_auth'] = 1;
			$_SESSION['sc_username'] = $username;
			$_SESSION['sc_password'] = $password;//This is needed to auth with driver on users behalf
			echo 1;
		}else{
			echo 0;
		}
	}

	/**
	 * logout 
	 * Log user out of system. Destroy session & run any required code specified in Auth driver.
	 * @return 1
	 */
	public function logout(){
		Auth::logout(); 
		//Kill sess vars
		unset($_SESSION['sc_auth']);
		unset($_SESSION['sc_username']);
		unset($_SESSION['sc_password']);

		echo 1;
	}

	/**
	 * settings 
	 * Return JSON defining available products, workflow & other config options
	 * @return JSON Object
	 */
	public function settings(){

		$data = array();

		//Get products from CardStore driver.
		$products = CardStore::listProducts();

		//If product is null, initate system setup
		if($products === null){
			//return setup screen
			$data = $this->setup();
		}else{
			//Return normal data
			$data['products'] = $products;
			$data['workflow'] = $this->workflow;
			$data['refresh_time']	= Config::get("refresh_time");
			$data['default_product'] = Config::get("default_product");
		}
		//Return as json
		echo json_encode($data);
	}

	/**
	 * Setup
	 * If the system detects that this is a new installtion, run setup
	 * processes, and display setup information to the user
	 */
	private function setup(){
		//Run setup process
		$message = CardStore::setup();
		//Return details on setup status
		$data = array();
		$data['setup'] = true;
		$data['welcome'] = true;
		$data['message'] = $message;
		return $data;
	}

	/**
	 * showList
	 * Return JSON defining information needed to display a set of storycards for a given product and sprint
	 * @return JSON Object
	 */
	public function showlist(){
		
		//Set defaults
		$product = Config::get("default_product");
		$sprint = 'all';
		//Override is alternative is provided
		if(isset($_GET['product'])){$product = $_GET['product'];}
		if(isset($_GET['sprint'])){ $sprint = (strtolower($_GET['sprint'])!='all') ? (int)$_GET['sprint'] : 'all';}
		//Get list of storycards from cardstore driver.
		$data = CardStore::getCardsFor($product, $sprint);
		//Setup array (Associative arrays will become objects when stored in JSON)
		$json = array();
		//get users current auth status
		$json['authed'] = isset($_SESSION['sc_auth']) ? 1 : 0;
		//get users name (display purposes)
		$json['authed_user'] = isset($_SESSION['sc_username']) ? $_SESSION['sc_username'] : 0;
		//Get sprints list (see todo)
		$json['sprints'] = CardStore::getSprints($product);//$sprint_list;
		//Get StoryCards
		$json['data'] = $data;
		//get loadtime
		$json['loaded'] = time();
		//Send JSON to browser
		echo json_encode($json);
	}

	/**
	 * move
	 * Save result of Storycard been moved from one status to another.
	 * @return 0|timestamp
	 */
	public function move(){
		//If user is authenticated
		if(isset($_SESSION['sc_auth'])){
			//get id of storycard & status its been moved to.
			$id = (int) $_POST['id'];
			$status =  $_POST['status'];
			//Send data to CardStore
			if(CardStore::updateCard($id, array('status'=>$status))){
				//If save went ok, update timestamps and return new one to browser
				$this->updateTimeStamp();
				$this->lastchange();
			}else{
				//Else 0 = fail
				echo 0;
			}
		}else{
			echo 0;
		}
	}

	/**
	 * lastchanged
	 * Get Timestamp of last update. This is used to allow the front end to poll for changes without
	 * hitting the backend data source.
	 *
	 * @return timestamp
	 */
	public function lastchange(){
		//Load html
		$html = @file_get_contents(Config::get('cache.dir').'/cards.timestamp.txt');
		if($html !== false){
			//return timestamp if exists
			echo $html;
		}else{
			//create new timestamp if not.
			$this->updateTimeStamp();
			echo time();
		}
	}

	/**
	 * updateCard
	 * Update an existing storycard with an abitrary set of data.
	 *
	 * @return null
	 */
	public function updateCard(){
		//If user is not authenticated
		if(!isset($_SESSION['sc_auth'])) return 0;
		//get id
		$id = $_POST['id'];
		unset($_POST['id']);

		//Prevent people sneaking in JS
		foreach($_POST as $key=>$val){
			$_POST[$key] = htmLawed($val,array('keep_bad'=>0,'elements'=>'* -script -object'));
		}

		//Call save on datasoruce
		if(CardStore::updateCard($id, $_POST)){
			//if successful return new timestamp
			$this->updateTimeStamp();
			
			$json = array("id"=>$id,"data"=> $_POST,"timestamp"=>time());
			echo json_encode($json);
		}else{
			//else 0 for failure
			echo 0;
		}
	}

	/**
	 * addCard
	 * Add a new storycard
	 *
	 * @return null
	 */
	public function addCard(){
		//If user is not authenticated
		if(!isset($_SESSION['sc_auth'])) return 0;

		//Prevent people sneaking in JS
		foreach($_POST as $key=>$val){
			$_POST[$key] = htmLawed($val,array('keep_bad'=>0,'elements'=>'* -script -object'));
		}

		//Call save on datastore
		$newCard = CardStore::addCard($_POST);
		if($newCard !== null){
			//if successful return new timestamp
			$this->updateTimeStamp();
			$json = array("card"=>$newCard,"timestamp"=>time());
			echo json_encode($json);
		}else{
			//else 0 for failure
			echo 0;
		}
	}

	/**
	 * addSprint
	 * Create a new sprint
	 *
	 * @return null
	 */
	public function addSprint(){
		//Sprint
		$sprint = strip_tags($_POST['sprint']);
		//Add sprint to datastore
		$sprint_id = CardStore::addSprint($sprint, array(
					'start_date' => $_POST['start_date'],
					'end_date' =>$_POST['end_date'],
					'hours' =>$_POST['total_hours']
				));

		if($sprint_id==null){echo 0; die();} 

		//foreach card
		$updates=array(); $p=100;
		foreach($_POST['card'] as $i => $card_id){		
			//Add cards
			$updates[$i] = array(
				'id'=> $card_id,
				'sprint'=>$sprint_id
			);
			// If we are priortising, add priority 
			// (defualt interval is 2, user should be able to conifgure this)
			if($data['prioritise']==1){
				$updates[$i]['priority']= $p;
				$p = $p-2;
			}
		}
		//updateCards to reflect change (allow for batch methods)
		if(CardStore::updateCards($updates)){
			echo 1;
		}else{
			echo 0;
		}

	}

	/**
	 * updateTimeStamp
	 * Update Last updated timestamp (for browser polling)
	 * @return null
	 */
	private function updateTimeStamp(){
		file_put_contents(Config::get('cache.dir').'/cards.timestamp.txt',time());
	}

}